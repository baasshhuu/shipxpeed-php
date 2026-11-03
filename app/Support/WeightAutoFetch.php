<?php

namespace App\Support;

use App\Models\Order;
use App\Models\WeightDisputes;
use App\Support\Xpeed\Kit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Pulls the courier's re-weighed weight for shipped orders and, when it lands in a
 * higher 500 g slab than the seller was billed on, charges the excess through the same
 * WeightDisputeService::chargeOne() the Excel upload uses (seller rate card, wallet debit,
 * 48-hour dispute window, never twice for one AWB).
 *
 *  Shipclues: GET order/{id}/ -> shipments[].updated_weight_in_gm / updated_vol_weight_in_gm,
 *             updated L×B×H and the courier's package_*_image photos (shown to seller + admin).
 *  Delhivery (direct): tracking API Shipment.ChargedWeight, when Delhivery reports it.
 *  Other couriers: not available by API — use the Excel upload.
 *
 * Admin switch: xp_settings key "weight_auto_fetch" ('0' = off, anything else = on).
 */
class WeightAutoFetch
{
    public const RECHECK_HOURS = 6;   // how often one order is asked again while no weight is reported
    public const LOOKBACK_DAYS = 25;  // only shipments booked in this window
    public const PER_RUN = 150;       // API calls per run

    public static function ready(): bool
    {
        try {
            return Schema::hasTable('weight_fetch_checks') && Schema::hasColumn('weight_disputes', 'courier_proofs');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function enabled(): bool
    {
        return Kit::setting('weight_auto_fetch') !== '0';
    }

    public static function setEnabled(bool $on): void
    {
        DB::table('xp_settings')->updateOrInsert(['key' => 'weight_auto_fetch'], ['value' => $on ? '1' : '0', 'updated_at' => now(), 'created_at' => now()]);
        Cache::forget('xp_settings_all');
    }

    /** Which courier API can tell us the weight for this order (null = none). */
    public static function sourceFor(Order $order): ?string
    {
        $info = self::arr($order->additional_info);
        if (!empty($info['shipclues_order_id'])) {
            return 'shipclues';
        }
        $cid = strtolower((string) $order->courier_id);
        // Delhivery's ChargedWeight field is not documented: only used when switched on in .env (WEIGHT_AUTO_DELHIVERY=1)
        if (str_starts_with($cid, 'delhivery') && self::delhiveryToken() && env('WEIGHT_AUTO_DELHIVERY')) {
            return 'delhivery';
        }
        return null;
    }

    /** Run once (cron / admin "Fetch now"). */
    public static function run(?int $limit = null, bool $force = false): array
    {
        $out = ['checked' => 0, 'charged' => 0, 'amount' => 0.0, 'no_data' => 0, 'within_slab' => 0, 'errors' => 0, 'skipped' => 0];
        if (!self::ready() || (!$force && !self::enabled())) {
            return $out + ['note' => self::ready() ? 'Auto-fetch is switched off' : 'Not installed yet'];
        }
        $limit = $limit ?: self::PER_RUN;

        $charged = WeightDisputes::where('dispute_status', '!=', 'void')->pluck('awb')->filter()->flip();
        $orders = Order::query()
            ->whereNotNull('awb_number')->where('awb_number', '!=', '')
            ->where('created_at', '>=', now()->subDays(self::LOOKBACK_DAYS))
            ->whereNotIn('id', DB::table('weight_fetch_checks')->where(function ($q) {
                $q->where('done', true)->orWhere('last_checked_at', '>', now()->subHours(self::RECHECK_HOURS));
            })->select('order_id'))
            ->orderBy('id')
            ->limit($limit * 4)
            ->get();

        foreach ($orders as $order) {
            if ($out['checked'] >= $limit) {
                break;
            }
            $b = Kit::bucket($order);
            if (in_array($b, ['cancelled', 'new', 'booked'], true)) {
                continue; // not picked up yet / cancelled: nothing to weigh
            }
            if (isset($charged[$order->awb_number])) {
                self::mark($order, null, true, 'charged', 'Already has a weight charge');
                continue;
            }
            $src = self::sourceFor($order);
            if (!$src) {
                self::mark($order, null, true, 'unsupported', 'Courier gives no re-weigh data by API — use Excel upload');
                $out['skipped']++;
                continue;
            }
            $out['checked']++;
            try {
                $res = self::checkOne($order, $src);
                $k = ['charged' => 'charged', 'within_slab' => 'within_slab', 'no_data' => 'no_data', 'needs_review' => 'needs_review'][$res['result']] ?? 'errors';
                $out[$k] = ($out[$k] ?? 0) + 1;
                $out['amount'] += $res['amount'] ?? 0;
            } catch (\Throwable $e) {
                $out['errors']++;
                self::mark($order, $src, false, 'error', mb_substr($e->getMessage(), 0, 250));
                Log::warning('WeightAutoFetch failed', ['order' => $order->id, 'error' => $e->getMessage()]);
            }
        }
        $out['amount'] = round($out['amount'], 2);
        Cache::put('weight_auto_fetch_last', ['at' => now()->toDateTimeString()] + $out, now()->addDays(7));
        return $out;
    }

    /** Ask the courier for one order and charge if needed. */
    public static function checkOne(Order $order, string $src): array
    {
        $w = $src === 'shipclues' ? self::fromShipclues($order) : self::fromDelhivery($order);
        if (!$w) {
            $final = in_array(Kit::bucket($order), ['delivered', 'rto'], true) && $order->updated_at && $order->updated_at->lt(now()->subDays(7));
            self::mark($order, $src, $final, 'no_data', 'Courier has not reported a re-weigh yet');
            return ['result' => 'no_data'];
        }

        // sanity check: a reading far above what was booked is more likely a data error than a real parcel
        $billed = WeightDisputeService::appliedWeight($order)['billed'];
        if ($w['grams'] > 5000 && $w['grams'] > $billed * 10) {
            self::mark($order, $src, true, 'needs_review', 'Courier reported ' . WeightDisputeService::kg($w['grams']) . ' kg vs ' . WeightDisputeService::kg($billed) . ' kg booked — not charged, check and use Excel upload if correct', $w['grams']);
            return ['result' => 'needs_review'];
        }

        $res = WeightDisputeService::chargeOne((string) $order->awb_number, [
            'charged' => round($w['grams'], 2) . ' g',
            'type' => $w['type'],
            'l' => $w['l'] ?? null, 'b' => $w['b'] ?? null, 'h' => $w['h'] ?? null,
            'manual' => null,
            'note' => 'Courier re-weighed this parcel at ' . WeightDisputeService::kg($w['grams']) . ' kg (' . $w['type'] . '). Fetched automatically from the courier.',
            'source' => 'auto',
            'courier_proofs' => $w['proofs'] ?? [],
        ], 'AUTO' . now()->format('ymd'), null);

        $result = $res['result'] === 'charged' ? 'charged' : ($res['result'] === 'skipped' ? 'within_slab' : 'error');
        self::mark($order, $src, $result !== 'error', $result, mb_substr($res['message'] ?? '', 0, 250), $w['grams']);
        return ['result' => $result, 'amount' => $res['amount'] ?? 0, 'message' => $res['message'] ?? ''];
    }

    /** ['grams', 'type', 'l', 'b', 'h', 'proofs'] or null when the courier has not re-weighed yet. */
    public static function fromShipclues(Order $order): ?array
    {
        $info = self::arr($order->additional_info);
        $id = $info['shipclues_order_id'] ?? null;
        $key = self::shipcluesKey();
        if (!$id || !$key) {
            return null;
        }
        $r = Http::withHeaders(['Authorization' => 'Token ' . $key])->acceptJson()->timeout(30)
            ->get('https://api.shipclues.in/seller/v1/order/' . rawurlencode((string) $id) . '/');
        if (!$r->successful()) {
            throw new \RuntimeException('Shipclues HTTP ' . $r->status());
        }
        $awbs = array_filter([(string) $order->awb_number, (string) ($info['shipclues_awb'] ?? '')]);
        $ship = null;
        foreach ((array) ($r->json('shipments') ?? []) as $s) {
            if (in_array((string) ($s['awb_number'] ?? ''), $awbs, true)) {
                $ship = $s;
                break;
            }
        }
        $ship = $ship ?: ((array) ($r->json('shipments') ?? []))[0] ?? null;
        if (!$ship) {
            return null;
        }
        $num = fn ($v) => is_numeric($v) && (float) $v > 0 ? (float) $v : null;
        $dead = $num($ship['updated_weight_in_gm'] ?? null);
        $vol = $num($ship['updated_vol_weight_in_gm'] ?? null);
        $l = $num($ship['updated_length_in_cm'] ?? null);
        $b = $num($ship['updated_breadth_in_cm'] ?? null);
        $h = $num($ship['updated_height_in_cm'] ?? null);
        if (!$vol && $l && $b && $h) {
            $vol = round($l * $b * $h / WeightDisputeService::DIVISOR * 1000, 2);
        }
        if (!$dead && !$vol) {
            return null;
        }
        $proofs = [];
        foreach (['package_weight_image' => 'Courier weight photo', 'package_length_image' => 'Courier length photo',
                  'package_breadth_image' => 'Courier breadth photo', 'package_height_image' => 'Courier height photo'] as $k => $label) {
            if (!empty($ship[$k]) && is_string($ship[$k])) {
                $proofs[] = ['url' => $ship[$k], 'kind' => 'image', 'label' => $label];
            }
        }
        return [
            'grams' => max($dead ?? 0, $vol ?? 0),
            'type' => ($vol ?? 0) > ($dead ?? 0) ? 'Volumetric' : 'Dead',
            'l' => $l, 'b' => $b, 'h' => $h,
            'proofs' => $proofs,
        ];
    }

    /** Delhivery tracking: Shipment.ChargedWeight (grams; small values are read as kg). */
    public static function fromDelhivery(Order $order): ?array
    {
        $r = Http::withHeaders(['Authorization' => 'Token ' . self::delhiveryToken()])->acceptJson()->timeout(30)
            ->get('https://track.delhivery.com/api/v1/packages/json/', ['waybill' => $order->awb_number]);
        if (!$r->successful()) {
            throw new \RuntimeException('Delhivery HTTP ' . $r->status());
        }
        $s = $r->json('ShipmentData.0.Shipment') ?? [];
        $v = $s['ChargedWeight'] ?? $s['chargedWeight'] ?? $s['charged_weight'] ?? null;
        if (!is_numeric($v) || (float) $v <= 0) {
            return null;
        }
        $g = (float) $v <= 50 ? (float) $v * 1000 : (float) $v;
        return ['grams' => $g, 'type' => 'Dead', 'proofs' => []];
    }

    private static function shipcluesKey(): string
    {
        $k = env('SHIPCLUES_API_KEY');
        if (!$k) {
            $k = Cache::remember('shipclues_api_key', now()->addMinutes(5), fn () => (string) \App\Models\LogisticProvider::where('code', 'shipclues')->value('api_key'));
        }
        return trim((string) $k);
    }

    private static function delhiveryToken(): string
    {
        return trim((string) (env('DELHIVERY_B2C_TOKEN') ?: env('DELHIVERY_TOKEN') ?: ''));
    }

    private static function mark(Order $order, ?string $src, bool $done, string $result, string $msg, ?float $grams = null): void
    {
        $row = DB::table('weight_fetch_checks')->where('order_id', $order->id)->first();
        $data = [
            'awb' => $order->awb_number, 'source' => $src, 'done' => $done, 'result' => $result, 'message' => $msg,
            'courier_weight_g' => $grams, 'last_checked_at' => now(), 'updated_at' => now(),
        ];
        if ($row) {
            DB::table('weight_fetch_checks')->where('id', $row->id)->update($data + ['attempts' => $row->attempts + 1]);
        } else {
            DB::table('weight_fetch_checks')->insert($data + ['order_id' => $order->id, 'attempts' => 1, 'created_at' => now()]);
        }
    }

    /** Numbers for the admin card. */
    public static function stats(): array
    {
        if (!self::ready()) {
            return ['ready' => false];
        }
        $g = DB::table('weight_fetch_checks')->selectRaw('result, COUNT(*) c')->groupBy('result')->pluck('c', 'result')->all();
        return [
            'ready' => true,
            'enabled' => self::enabled(),
            'counts' => $g,
            'last' => Cache::get('weight_auto_fetch_last'),
            'auto_charged' => (float) WeightDisputes::where('source', 'auto')->whereNotNull('deducted_at')->sum('excess_charge'),
            'auto_count' => WeightDisputes::where('source', 'auto')->count(),
            'delhivery' => self::delhiveryToken() !== '' && (bool) env('WEIGHT_AUTO_DELHIVERY'),
            'needs_review' => DB::table('weight_fetch_checks')->where('result', 'needs_review')->orderByDesc('id')->limit(20)->get(['awb', 'message']),
            'shipclues' => self::shipcluesKey() !== '',
        ];
    }

    private static function arr($v): array
    {
        return is_array($v) ? $v : (json_decode((string) $v, true) ?: []);
    }
}
