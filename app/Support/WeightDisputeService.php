<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Recharge;
use App\Models\SellerList;
use App\Models\WeightDisputes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Weight dispute rules in one place.
 *
 *  Upload (admin Excel):  AWB, courier charged weight, Dead / Volumetric (+ optional courier L×B×H, manual charge, note)
 *    -> order + seller found from the AWB
 *    -> billed weight = higher of the seller's dead weight and the seller's L×B×H ÷ 5000
 *    -> extra 500 g slabs = slabs(charged) − slabs(billed); nothing to charge when 0
 *    -> excess = seller's own rate card price(charged) − price(billed)  (freight + 18% GST, same maths as booking)
 *       fallback when no rate card row exists: what the seller paid ÷ billed slabs × extra slabs
 *       a "Manual charge" filled in the Excel always wins
 *    -> wallet debited at once; seller can dispute within WEIGHT_DISPUTE_WINDOW_HOURS (default 48)
 *  Review (admin): accept -> excess credited back to the wallet; reject -> closed.
 */
class WeightDisputeService
{
    public const DIVISOR = 5000; // cm³ per kg, volumetric

    /** Excel header (lower-cased, non-alphanumerics removed) -> field */
    private const HEADERS = [
        'awb' => 'awb', 'awbnumber' => 'awb', 'awbno' => 'awb', 'trackingnumber' => 'awb',
        'chargedweightkg' => 'charged', 'chargedweight' => 'charged', 'couriercharged' => 'charged', 'courierchargedweightkg' => 'charged', 'courierweight' => 'charged',
        'weighttype' => 'type', 'deadorvolumetric' => 'type', 'deadvolumetric' => 'type', 'type' => 'type', 'deadvol' => 'type',
        'lengthcm' => 'l', 'length' => 'l', 'courierlengthcm' => 'l',
        'breadthcm' => 'b', 'breadth' => 'b', 'widthcm' => 'b', 'width' => 'b', 'courierbreadthcm' => 'b',
        'heightcm' => 'h', 'height' => 'h', 'courierheightcm' => 'h',
        'manualchargeoptional' => 'manual', 'manualcharge' => 'manual', 'excesscharge' => 'manual', 'manualcharge₹' => 'manual',
        'noteoptional' => 'note', 'note' => 'note', 'remarks' => 'note', 'remark' => 'note',
    ];

    /** Template heading (any reasonable spelling) -> field. */
    public static function headerField(string $h): ?string
    {
        $k = preg_replace('/[^a-z0-9]/', '', strtolower($h));
        if ($k === '') {
            return null;
        }
        if (isset(self::HEADERS[$k])) {
            return self::HEADERS[$k];
        }
        foreach ([
            'awb' => 'awb', 'tracking' => 'awb', 'manual' => 'manual', 'charged' => 'charged',
            'weighttype' => 'type', 'deadorvol' => 'type', 'length' => 'l', 'breadth' => 'b', 'width' => 'b',
            'height' => 'h', 'note' => 'note', 'remark' => 'note',
        ] as $prefix => $field) {
            if (str_starts_with($k, $prefix) || ($prefix === 'charged' && str_contains($k, 'charged'))) {
                return $field;
            }
        }
        return null;
    }

    public static function templateHeadings(): array
    {
        return ['AWB', 'Charged Weight (kg)', 'Weight Type (Dead / Volumetric)', 'Length (cm)', 'Breadth (cm)', 'Height (cm)', 'Manual Charge (optional)', 'Note (optional)'];
    }

    /**
     * Process one uploaded sheet (rows as arrays, first row = headings).
     * Returns ['batch', 'charged' => n, 'amount' => ₹, 'rows' => [[row, awb, result, message, amount]]]
     */
    public static function importRows(array $rows, ?int $adminId = null): array
    {
        $batch = 'WD' . now()->format('ymdHis') . Str::upper(Str::random(3));
        $out = ['batch' => $batch, 'charged' => 0, 'amount' => 0.0, 'rows' => []];

        $head = array_shift($rows) ?: [];
        $map = [];
        foreach ($head as $i => $h) {
            $f = self::headerField((string) $h);
            if ($f && !in_array($f, $map, true)) {
                $map[$i] = $f;
            }
        }
        if (!in_array('awb', $map, true)) {
            throw new \RuntimeException('The first row must contain the template headings (AWB, Charged Weight (kg), Weight Type …). Please use the downloaded template.');
        }

        $seen = [];
        foreach ($rows as $n => $raw) {
            $line = $n + 2;
            $r = ['awb' => null, 'charged' => null, 'type' => null, 'l' => null, 'b' => null, 'h' => null, 'manual' => null, 'note' => null];
            foreach ($map as $i => $f) {
                $r[$f] = is_string($raw[$i] ?? null) ? trim($raw[$i]) : ($raw[$i] ?? null);
            }
            $awb = trim((string) $r['awb']);
            if ($awb === '' && $r['charged'] === null) {
                continue; // blank line
            }
            if (is_float($r['awb']) || preg_match('/^\d+\.?\d*E\+\d+$/i', $awb)) {
                $awb = number_format((float) $r['awb'], 0, '', ''); // Excel turned a long AWB into a number
            }
            if (isset($seen[$awb])) {
                $out['rows'][] = [$line, $awb, 'skipped', 'Same AWB appears twice in this file (row ' . $seen[$awb] . ')', 0];
                continue;
            }
            $seen[$awb] = $line;

            try {
                $res = self::chargeOne($awb, $r, $batch, $adminId);
                $out['rows'][] = [$line, $awb, $res['result'], $res['message'], $res['amount'] ?? 0];
                if ($res['result'] === 'charged') {
                    $out['charged']++;
                    $out['amount'] += $res['amount'];
                }
            } catch (\Throwable $e) {
                Log::error('Weight dispute row failed', ['awb' => $awb, 'error' => $e->getMessage()]);
                $out['rows'][] = [$line, $awb, 'error', 'Could not process: ' . $e->getMessage(), 0];
            }
        }
        $out['amount'] = round($out['amount'], 2);
        return $out;
    }

    /** Validate, price and debit one AWB. */
    public static function chargeOne(string $awb, array $r, string $batch, ?int $adminId = null): array
    {
        if ($awb === '') {
            return ['result' => 'error', 'message' => 'AWB is empty'];
        }
        $order = Order::where('awb_number', $awb)->orderByDesc('id')->first();
        if (!$order) {
            return ['result' => 'error', 'message' => 'AWB not found in any order'];
        }
        $seller = SellerList::find($order->seller_id);
        if (!$seller) {
            return ['result' => 'error', 'message' => 'Order has no seller'];
        }

        $type = self::parseType($r['type'] ?? null);
        $l = self::num($r['l'] ?? null);
        $b = self::num($r['b'] ?? null);
        $h = self::num($r['h'] ?? null);
        $charged = self::parseWeightGrams($r['charged'] ?? null);
        if ($charged === null && $l && $b && $h) {
            $charged = round($l * $b * $h / self::DIVISOR * 1000, 2);
            $type = $type ?: 'volumetric';
        }
        if ($charged === null || $charged <= 0) {
            return ['result' => 'error', 'message' => 'Charged weight missing or not a number'];
        }
        if (!$type) {
            return ['result' => 'error', 'message' => 'Weight Type must be Dead or Volumetric'];
        }

        $applied = self::appliedWeight($order);
        $billed = $applied['billed'];
        $slabsBilled = max(1, (int) ceil($billed / RateCard::SLAB_GRAMS));
        $slabsCharged = max(1, (int) ceil($charged / RateCard::SLAB_GRAMS));
        $extra = $slabsCharged - $slabsBilled;

        $manual = self::num($r['manual'] ?? null);
        if ($extra <= 0 && !($manual > 0)) {
            return ['result' => 'skipped', 'message' => 'No excess — courier weight ' . self::kg($charged) . ' kg is within the billed ' . self::kg($billed) . ' kg slab'];
        }

        $calc = $manual > 0
            ? ['amount' => round($manual, 2), 'method' => 'manual']
            : self::excessCharge($order, (int) $seller->id, $billed, $charged, $extra);
        if (!$calc || $calc['amount'] <= 0) {
            return ['result' => 'error', 'message' => 'No rate card found for this courier/zone — fill the Manual Charge column for this AWB'];
        }

        return DB::transaction(function () use ($order, $seller, $awb, $type, $l, $b, $h, $charged, $applied, $extra, $calc, $batch, $r) {
            // one live discrepancy per AWB (lock the order row so a double upload cannot charge twice)
            Order::whereKey($order->id)->lockForUpdate()->first();
            $dup = WeightDisputes::where('awb', $awb)->where(function ($q) {
                $q->whereNull('dispute_status')->orWhere('dispute_status', '!=', WeightDisputes::VOID);
            })->first();
            if ($dup) {
                return ['result' => 'skipped', 'message' => 'Already charged on ' . optional($dup->deducted_at ?? $dup->created_at)->format('d M Y')];
            }

            $now = now();
            $courier = self::courierLabel($order);
            $d = WeightDisputes::create([
                'awb' => $awb,
                'courier' => $courier,
                'Mentionedweight' => self::kg($applied['billed']),
                'chargedweight' => self::kg($charged),
                'weightmissmatched' => self::kg(max(0, $charged - $applied['billed'])),
                'weightdisputecharges' => $calc['amount'],
                'status' => '1',
                'seller_id' => $seller->id,
                'order_id' => $order->id,
                'order_number' => $order->customer_order_id ?: $order->order_number,
                'applied_weight' => $applied['billed'],
                'applied_dead_weight' => $applied['dead'],
                'applied_vol_weight' => $applied['vol'],
                'charged_weight_g' => $charged,
                'weight_type' => $type,
                'courier_length' => $l ?: null,
                'courier_breadth' => $b ?: null,
                'courier_height' => $h ?: null,
                'extra_slabs' => max(0, $extra),
                'excess_charge' => $calc['amount'],
                'calc_method' => $calc['method'],
                'admin_note' => $r['note'] ? mb_substr((string) $r['note'], 0, 500) : null,
                'dispute_status' => WeightDisputes::CHARGED,
                'deducted_at' => $now,
                'dispute_deadline' => $now->copy()->addHours(WeightDisputes::windowHours()),
                'upload_batch' => $batch,
            ]);

            $rc = Recharge::create([
                'seller_id' => $seller->id,
                'type' => 'Debit',
                'amount' => $calc['amount'],
                'status' => 1,
                'description' => 'Weight discrepancy charge - AWB ' . $awb,
                'weight_id' => $d->id,
                'weight_awb' => $awb,
                'weight_courier' => $courier,
                'weight_mentionedweight' => self::kg($applied['billed']),
                'weight_chargedweight' => self::kg($charged),
                'weight_weightmissmatched' => self::kg(max(0, $charged - $applied['billed'])),
                'weight_seller_name' => $seller->name ?? null,
            ]);
            $d->update(['debit_recharge_id' => $rc->id]);

            return ['result' => 'charged', 'message' => '₹' . number_format($calc['amount'], 2) . ' debited (' . self::kg($applied['billed']) . ' → ' . self::kg($charged) . ' kg, ' . (['rate_card' => 'seller rate card', 'proportional' => 'proportional to freight paid', 'manual' => 'manual amount'][$calc['method']] ?? $calc['method']) . ')', 'amount' => $calc['amount']];
        });
    }

    /** Seller raises a dispute (files already validated). $files = [UploadedFile] */
    public static function raise(WeightDisputes $d, string $remark, array $files): void
    {
        DB::transaction(function () use ($d, $remark, $files) {
            $d = WeightDisputes::whereKey($d->id)->lockForUpdate()->firstOrFail();
            if (!$d->canRaise()) {
                throw new \RuntimeException('The dispute window for this AWB has closed or a dispute was already raised.');
            }
            $proofs = [];
            foreach ($files as $f) {
                $mime = (string) $f->getMimeType();
                $ext = strtolower($f->getClientOriginalExtension() ?: $f->extension() ?: 'bin');
                $path = $f->storeAs('weight-disputes/' . $d->id, Str::random(24) . '.' . $ext, 'local');
                $proofs[] = [
                    'path' => $path,
                    'name' => mb_substr($f->getClientOriginalName(), 0, 120),
                    'type' => str_starts_with($mime, 'video') ? 'video' : 'image',
                    'mime' => $mime,
                    'size' => $f->getSize(),
                ];
            }
            $d->update([
                'seller_remark' => mb_substr($remark, 0, 1000),
                'seller_proofs' => $proofs,
                'raised_at' => now(),
                'dispute_status' => WeightDisputes::DISPUTED,
            ]);
        });
    }

    /** Admin decision. accept -> refund to wallet; reject -> closed. */
    public static function review(int $id, bool $accept, ?string $remark, ?int $adminId): WeightDisputes
    {
        return DB::transaction(function () use ($id, $accept, $remark, $adminId) {
            $d = WeightDisputes::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($d->dispute_status !== WeightDisputes::DISPUTED) {
                throw new \RuntimeException('This dispute is not waiting for review (status: ' . WeightDisputes::statusLabel($d->liveStatus()) . ').');
            }
            $upd = [
                'admin_remark' => $remark ? mb_substr($remark, 0, 1000) : null,
                'reviewed_at' => now(),
                'reviewed_by' => $adminId,
                'dispute_status' => $accept ? WeightDisputes::ACCEPTED : WeightDisputes::REJECTED,
            ];
            if ($accept && (float) $d->excess_charge > 0) {
                $rc = Recharge::create([
                    'seller_id' => $d->seller_id,
                    'type' => 'Credit',
                    'amount' => $d->excess_charge,
                    'status' => 1,
                    'description' => 'Weight dispute refund - AWB ' . $d->awb,
                    'weight_id' => $d->id,
                    'weight_awb' => $d->awb,
                    'weight_courier' => $d->courier,
                ]);
                $upd['refund_recharge_id'] = $rc->id;
                $upd['refunded_at'] = now();
            }
            $d->update($upd);
            return $d;
        });
    }

    /** ['dead' => g, 'vol' => g|null, 'billed' => g] from what the seller entered on the order. */
    public static function appliedWeight(Order $order): array
    {
        $dead = (float) ($order->package_weight ?? 0);
        if ($dead > 0 && $dead < 10) {
            $dead *= 1000; // entered in kg
        }
        $l = (float) $order->package_length;
        $b = (float) $order->package_breadth;
        $h = (float) $order->package_height;
        $vol = ($l > 0 && $b > 0 && $h > 0) ? round($l * $b * $h / self::DIVISOR * 1000, 2) : null;
        return ['dead' => $dead ?: null, 'vol' => $vol, 'billed' => max($dead, (float) $vol, 1)];
    }

    /** Excess price for the extra weight: rate card difference, else proportional to what the seller paid. */
    public static function excessCharge(Order $order, int $sellerId, float $billed, float $charged, int $extraSlabs): ?array
    {
        try {
            $key = self::rateKey($order);
            $zone = RateCard::zoneForOrder($order)['zone'] ?? null;
            if ($key && $zone && ($card = RateCard::find($sellerId, $zone, $key))) {
                $hi = RateCard::price($card, $charged, false, 0);
                $lo = RateCard::price($card, $billed, false, 0);
                if ($hi && $lo && $hi['courierCharge'] > $lo['courierCharge']) {
                    return ['amount' => round($hi['courierCharge'] - $lo['courierCharge'], 2), 'method' => 'rate_card'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Weight dispute rate card lookup failed', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        $paid = (float) ($order->seller_amount_walate ?? 0);
        if ($paid > 0 && $extraSlabs > 0) {
            $slabs = max(1, (int) ceil($billed / RateCard::SLAB_GRAMS));
            return ['amount' => round($paid / $slabs * $extraSlabs, 2), 'method' => 'proportional'];
        }
        return null;
    }

    /** Seller-facing courier name (never an aggregator name). */
    public static function courierLabel(Order $order): string
    {
        $code = strtolower((string) ($order->courier_id ?: $order->all_courier_name));
        if ($code === 'delhivery_b2c') {
            return 'Delhivery Surface';
        }
        if (in_array($code, ['delhivery_b2c_express', 'delhivery air', 'delhivery_air'], true)) {
            return 'Delhivery Air';
        }
        $name = $order->courier_name ?: ($order->zone_courier_name ?: $order->all_courier_name);
        return CourierBrand::cleanName(str_replace('_', ' ', (string) $name), 'Courier');
    }

    /** Rate-card key of the courier the order was booked on. */
    public static function rateKey(Order $order): ?string
    {
        $code = strtolower((string) ($order->courier_id ?: $order->all_courier_name));
        $name = $order->courier_name ?: ($order->zone_courier_name ?: $order->all_courier_name);
        if ($code === 'delhivery_b2c') {
            return 'Delhivery';
        }
        if (in_array($code, ['delhivery_b2c_express', 'delhivery air', 'delhivery_air'], true)) {
            return 'Delhivery_Air';
        }
        if ($code === 'shipclues' && class_exists(\App\Services\ShipcluesService::class)) {
            return \App\Services\ShipcluesService::rateKey($name);
        }
        if ($code === 'fship' && class_exists(\App\Services\FshipService::class) && $name) {
            return \App\Services\FshipService::rateKey($name);
        }
        $z = trim((string) $order->zone_courier_name);
        return $z !== '' ? $z : null;
    }

    public static function parseType($v): ?string
    {
        $s = strtolower(trim((string) $v));
        if ($s === '') {
            return null;
        }
        if (str_starts_with($s, 'v')) {
            return 'volumetric';
        }
        if (str_starts_with($s, 'd') || str_starts_with($s, 'a')) { // dead / actual
            return 'dead';
        }
        return null;
    }

    /** "1.5", "1.5 kg", "1500 g", "1500gm" -> grams. A bare number above 100 is read as grams. */
    public static function parseWeightGrams($v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        $s = strtolower(str_replace(',', '.', trim((string) $v)));
        if (!preg_match('/^(\d+(?:\.\d+)?)\s*(kg|kgs|g|gm|gms|gram|grams)?$/', $s, $m)) {
            return null;
        }
        $n = (float) $m[1];
        $unit = $m[2] ?? '';
        if ($unit === '' ) {
            return $n > 100 ? $n : $n * 1000;
        }
        return str_starts_with($unit, 'k') ? $n * 1000 : $n;
    }

    private static function num($v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        $s = preg_replace('/[^0-9.]/', '', str_replace(',', '', (string) $v));
        return $s === '' ? null : (float) $s;
    }

    public static function kg($g): string
    {
        return rtrim(rtrim(number_format(((float) $g) / 1000, 3, '.', ''), '0'), '.');
    }
}
