<?php

namespace App\Support;

use App\Models\Order;
use App\Models\SellerList;
use App\Services\WhatsApp\OrderStatusNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shipment report: one place that turns orders into report rows, used by the
 * admin page AND the Excel export so both always show the same numbers.
 *
 * Money (all ₹, per shipment):
 *  - shipping_charged = seller_amount_walate, the amount debited from the seller
 *    wallet at booking (freight + COD charge, GST included). Split into taxable + 18% GST.
 *  - rto_charge       = RTO debit (same as the forward charge) once the RTO is billed.
 *  - refund           = credit back to the wallet when the shipment was cancelled.
 *  - weight_charge    = weight-discrepancy debits minus refunds for this AWB (wallet ledger).
 *  - whatsapp_charge  = WhatsApp buyer alerts billed for this order.
 *  - net              = what the seller paid in the end for this shipment.
 */
class ShipmentReport
{
    public const GST = 0.18;

    public const STATUS_LABELS = [
        'booked'           => 'Booked',
        'picked_up'        => 'Picked up',
        'in_transit'       => 'In transit',
        'out_for_delivery' => 'Out for delivery',
        'ndr'              => 'NDR',
        'delivered'        => 'Delivered',
        'rto'              => 'RTO in transit',
        'rto_delivered'    => 'RTO delivered',
        'cancelled'        => 'Cancelled',
        'lost'             => 'Lost / damaged',
    ];

    /** Filter groups shown in the status dropdown. */
    public const STATUS_FILTERS = [
        'booked'     => 'Booked / pickup pending',
        'in_transit' => 'Picked up / in transit / out for delivery',
        'ndr'        => 'NDR',
        'delivered'  => 'Delivered',
        'rto'        => 'RTO (in transit + delivered)',
        'cancelled'  => 'Cancelled',
        'lost'       => 'Lost / damaged',
    ];

    private static ?array $cols = null;

    /** Live DB tables are partly hand-made: only touch columns that really exist. */
    public static function has(string $col, string $table = 'orders'): bool
    {
        if ($table !== 'orders') {
            return Schema::hasColumn($table, $col);
        }
        self::$cols ??= array_map('strtolower', Schema::getColumnListing('orders'));
        return in_array(strtolower($col), self::$cols, true);
    }

    private static function existing(array $cols): array
    {
        return array_values(array_filter($cols, fn ($c) => self::has($c)));
    }

    public static function filters(Request $r): array
    {
        return [
            'seller_id' => $r->filled('seller_id') ? (int) $r->seller_id : null,
            'from'      => $r->filled('start_date') ? $r->start_date : null,
            'to'        => $r->filled('end_date') ? $r->end_date : null,
            'status'    => array_key_exists((string) $r->status, self::STATUS_FILTERS) ? $r->status : null,
            'payment'   => in_array(strtolower((string) $r->payment), ['cod', 'prepaid'], true) ? strtolower($r->payment) : null,
            'courier'   => $r->filled('courier') ? trim($r->courier) : null,
            'q'         => $r->filled('q') ? trim($r->q) : null,
        ];
    }

    public static function query(array $f): Builder
    {
        $q = Order::query()->with('seller')->whereNotNull('awb_number')->where('awb_number', '!=', '');

        if ($f['seller_id']) {
            $q->where('seller_id', $f['seller_id']);
        }
        if ($f['from']) {
            $q->whereDate('created_at', '>=', $f['from']);
        }
        if ($f['to']) {
            $q->whereDate('created_at', '<=', $f['to']);
        }
        if ($f['payment'] && self::has('payment_type')) {
            $q->whereRaw('LOWER(payment_type) = ?', [$f['payment']]);
        }
        if ($f['courier'] && ($cols = self::existing(['courier_name', 'zone_courier_name', 'all_courier_name', 'courier_id']))) {
            $like = '%' . $f['courier'] . '%';
            $q->where(function ($w) use ($cols, $like) {
                foreach ($cols as $c) {
                    $w->orWhere($c, 'like', $like);
                }
            });
        }
        if ($f['q'] && ($cols = self::existing(['awb_number', 'order_number', 'customer_order_id', 'consignee']))) {
            $like = '%' . $f['q'] . '%';
            $q->where(function ($w) use ($cols, $like) {
                foreach ($cols as $c) {
                    $w->orWhere($c, 'like', $like);
                }
            });
        }
        if ($f['status']) {
            self::whereStatus($q, $f['status']);
        }

        return $q->orderByDesc('created_at')->orderByDesc('id');
    }

    /** SQL version of statusKey(), so filtering and counting match the labels. */
    private static function whereStatus(Builder $q, string $group): void
    {
        $ss = self::has('shipping_status') ? "LOWER(COALESCE(shipping_status,''))" : "''";
        $os = self::has('order_status') ? "LOWER(COALESCE(order_status,''))" : "''";
        $cancelled = "({$os} = 'cancelled' OR {$ss} LIKE '%cancel%')";
        $rto = "({$ss} LIKE '%rto%' OR {$ss} LIKE '%return%')";
        $lost = "({$ss} LIKE '%lost%' OR {$ss} LIKE '%damage%' OR {$ss} LIKE '%destroy%')";
        $ndr = "({$ss} LIKE '%ndr%' OR {$ss} LIKE '%undeliver%' OR {$ss} LIKE '%not delivered%' OR {$ss} LIKE '%delivery fail%' OR {$ss} LIKE '%failed delivery%' OR {$ss} LIKE '%delivery attempt%')";
        $ofd = "({$ss} LIKE '%out for delivery%' OR {$ss} LIKE '%out_for_delivery%' OR {$ss} = 'ofd')";
        $delivered = "({$ss} LIKE '%deliver%' AND NOT {$ndr} AND NOT {$ofd})";
        $moving = "({$ss} LIKE '%picked%' OR {$ss} LIKE '%transit%' OR {$ss} LIKE '%dispatch%' OR {$ss} LIKE '%shipped%' OR {$ss} LIKE '%reached%' OR {$ofd})";

        $sql = match ($group) {
            'cancelled'  => $cancelled,
            'rto'        => "NOT {$cancelled} AND {$rto}",
            'lost'       => "NOT {$cancelled} AND NOT {$rto} AND {$lost}",
            'ndr'        => "NOT {$cancelled} AND NOT {$rto} AND NOT {$lost} AND {$ndr}",
            'delivered'  => "NOT {$cancelled} AND NOT {$rto} AND NOT {$lost} AND {$delivered}",
            'in_transit' => "NOT {$cancelled} AND NOT {$rto} AND NOT {$lost} AND NOT {$ndr} AND NOT {$delivered} AND {$moving}",
            'booked'     => "NOT {$cancelled} AND NOT {$rto} AND NOT {$lost} AND NOT {$ndr} AND NOT {$delivered} AND NOT {$moving}",
        };
        $q->whereRaw("({$sql})");
    }

    public static function statusKey(Order $o): string
    {
        if (strtolower((string) $o->order_status) === 'cancelled') {
            return 'cancelled';
        }
        $e = OrderStatusNotifier::eventFor($o->shipping_status);
        return match ($e) {
            null, 'booked'   => 'booked',
            'rto_initiated'  => 'rto',
            default          => $e,
        };
    }

    /**
     * Ledger amounts that are stored per AWB / order, loaded for a batch of orders.
     * @return array{weight: array<string,float>, whatsapp: array<int,float>, fixed: array<int,bool>}
     */
    public static function extras(Collection $orders): array
    {
        $awbs = $orders->pluck('awb_number')->filter()->unique()->values()->all();
        $ids = $orders->pluck('id')->all();

        $weight = [];
        if ($awbs && Schema::hasTable('recharges') && Schema::hasColumn('recharges', 'weight_awb')) {
            $rq = DB::table('recharges')->whereIn('weight_awb', $awbs);
            if (Schema::hasColumn('recharges', 'deleted_at')) {
                $rq->whereNull('deleted_at');
            }
            if (Schema::hasColumn('recharges', 'status')) {
                $rq->where('status', 1);
            }
            $rows = $rq->selectRaw("weight_awb, SUM(CASE WHEN type = 'Debit' THEN amount ELSE -amount END) AS net")
                ->groupBy('weight_awb')->get();
            foreach ($rows as $r) {
                $weight[(string) $r->weight_awb] = round((float) $r->net, 2);
            }
        }

        $wa = [];
        if ($ids && Schema::hasTable('whatsapp_messages')) {
            $wa = DB::table('whatsapp_messages')->whereIn('order_id', $ids)->where('status', 'sent')
                ->selectRaw('order_id, SUM(charge) AS c')->groupBy('order_id')->pluck('c', 'order_id')
                ->map(fn ($v) => round((float) $v, 2))->all();
        }

        return ['weight' => $weight, 'whatsapp' => $wa];
    }

    public static function row(Order $o, array $extras): array
    {
        $c = self::json($o->consignee);
        $p = self::json($o->pickup);
        $items = self::json($o->order_items);
        if ($items && !array_is_list($items)) {
            $items = [$items]; // single item stored as an object
        }

        $status = self::statusKey($o);
        $charged = round((float) ($o->seller_amount_walate ?? 0), 2);
        $taxable = round($charged / (1 + self::GST), 2);
        $gst = round($charged - $taxable, 2);

        $refund = $status === 'cancelled' && $charged > 0 ? $charged : 0.0;
        $sellerFixed = (float) ($o->seller->fixed_price ?? 0) > 0;
        $rtoCharge = in_array($status, ['rto', 'rto_delivered'], true) && (string) $o->rto_amount === '1' && $sellerFixed ? $charged : 0.0;
        $weight = $extras['weight'][(string) $o->awb_number] ?? 0.0;
        $wa = $extras['whatsapp'][$o->id] ?? 0.0;
        $net = round($charged + $rtoCharge + $weight + $wa - $refund, 2);

        $names = $skus = [];
        $qty = 0;
        foreach ($items ?: [] as $it) {
            if (!is_array($it)) {
                continue;
            }
            $q = (int) ($it['qty'] ?? $it['quantity'] ?? 1);
            $qty += $q;
            $n = trim((string) ($it['name'] ?? $it['sku_name'] ?? $it['description'] ?? ''));
            if ($n !== '') {
                $names[] = $n . ($q > 1 ? " x{$q}" : '');
            }
            if (filled($it['sku'] ?? null)) {
                $skus[] = $it['sku'];
            }
        }

        $wKg = self::kg($o->package_weight);
        $l = (float) $o->package_length;
        $b = (float) $o->package_breadth;
        $h = (float) $o->package_height;
        $vol = $l && $b && $h ? round($l * $b * $h / 5000, 3) : null;
        $isCod = strtolower((string) $o->payment_type) === 'cod';

        return [
            'id'              => $o->id,
            'order_date'      => optional($o->created_at)->format('Y-m-d H:i'),
            'shipped_date'    => $o->shipping_date ? self::date($o->shipping_date) : null,
            'delivered_date'  => $o->delivered_date ? self::date($o->delivered_date) : null,
            'order_number'    => $o->order_number,
            'channel_order'   => $o->customer_order_id ?: ($o->shopify_order_number ?? null),
            'awb'             => $o->awb_number,
            'seller_id'       => $o->seller_id,
            'seller_name'     => $o->seller->name ?? '',
            'seller_email'    => $o->seller->email ?? '',
            'seller_phone'    => self::phoneOnly($o->seller->phone_number ?? ''),
            'courier'         => $o->courier_name ?: ($o->zone_courier_name ?: ($o->all_courier_name ?: $o->courier_id)),
            'courier_account' => $o->courier_id,
            'zone'            => $o->zone,
            'payment'         => $isCod ? 'COD' : (strtolower((string) $o->payment_type) === 'prepaid' ? 'Prepaid' : (string) $o->payment_type),
            'order_value'     => round((float) ($o->order_amount ?? 0), 2),
            'cod_amount'      => $isCod ? round((float) ($o->collectable_amount ?: $o->order_amount), 2) : 0.0,
            'cod_remittance'  => $isCod ? ($o->payment_status ?: ($status === 'delivered' ? 'Pending' : '')) : '',
            'status_key'      => $status,
            'status'          => self::STATUS_LABELS[$status] ?? ucfirst($status),
            'courier_status'  => (string) $o->shipping_status,
            'rto_reason'      => (string) ($o->rto_reasen ?? ''),
            'buyer_name'      => $c['name'] ?? '',
            'buyer_phone'     => $c['phone'] ?? '',
            'buyer_alt_phone' => $c['alternate_phone'] ?? '',
            'buyer_email'     => $c['email'] ?? '',
            'buyer_address'   => trim(($c['address'] ?? '') . ' ' . ($c['address_2'] ?? '')),
            'buyer_landmark'  => $c['landmark'] ?? '',
            'buyer_city'      => $c['city'] ?? '',
            'buyer_state'     => $c['state'] ?? '',
            'buyer_pincode'   => $c['pincode'] ?? '',
            'pickup_name'     => $p['warehouse_name'] ?? ($p['name'] ?? ''),
            'pickup_contact'  => $p['contact_person_name'] ?? ($p['name'] ?? ''),
            'pickup_phone'    => $p['phone'] ?? '',
            'pickup_address'  => $p['address'] ?? ($p['add'] ?? ''),
            'pickup_city'     => $p['city'] ?? '',
            'pickup_state'    => $p['state'] ?? '',
            'pickup_pincode'  => $p['pincode'] ?? ($p['pin_code'] ?? ''),
            'products'        => implode(', ', $names),
            'skus'            => implode(', ', array_unique($skus)),
            'qty'             => $qty ?: null,
            'weight_kg'       => $wKg,
            'length_cm'       => $l ?: null,
            'breadth_cm'      => $b ?: null,
            'height_cm'       => $h ?: null,
            'vol_weight_kg'   => $vol,
            'freight_taxable' => $taxable,
            'gst'             => $gst,
            'shipping_charged'=> $charged,
            'rto_charge'      => $rtoCharge,
            'refund'          => $refund,
            'weight_charge'   => $weight,
            'whatsapp_charge' => $wa,
            'net'             => $net,
        ];
    }

    /** Totals + status counts over the whole filtered set (not just one page). */
    public static function summary(array $f): array
    {
        $t = ['count' => 0, 'charged' => 0.0, 'gst' => 0.0, 'refund' => 0.0, 'rto' => 0.0, 'weight' => 0.0, 'whatsapp' => 0.0, 'net' => 0.0, 'cod_value' => 0.0, 'by_status' => []];
        self::query($f)->reorder()->chunkById(500, function ($chunk) use (&$t) {
            $ex = self::extras($chunk);
            foreach ($chunk as $o) {
                $r = self::row($o, $ex);
                $t['count']++;
                $t['charged'] += $r['shipping_charged'];
                $t['gst'] += $r['gst'];
                $t['refund'] += $r['refund'];
                $t['rto'] += $r['rto_charge'];
                $t['weight'] += $r['weight_charge'];
                $t['whatsapp'] += $r['whatsapp_charge'];
                $t['net'] += $r['net'];
                $t['cod_value'] += $r['cod_amount'];
                $t['by_status'][$r['status_key']] = ($t['by_status'][$r['status_key']] ?? 0) + 1;
            }
        }, 'id');
        foreach (['charged', 'gst', 'refund', 'rto', 'weight', 'whatsapp', 'net', 'cod_value'] as $k) {
            $t[$k] = round($t[$k], 2);
        }
        return $t;
    }

    public static function couriers(): array
    {
        $cols = self::existing(['courier_name', 'zone_courier_name', 'all_courier_name', 'courier_id']);
        if (!$cols) {
            return [];
        }
        $expr = count($cols) === 1 ? $cols[0] : 'COALESCE(' . implode(', ', array_map(fn ($c) => "NULLIF({$c}, '')", $cols)) . ')';
        return Order::query()->whereNotNull('awb_number')->selectRaw("{$expr} AS c")
            ->distinct()->pluck('c')->filter()->map(fn ($v) => (string) $v)->unique()->sort()->values()->all();
    }

    public static function sellers()
    {
        return SellerList::select('id', 'name', 'email')->orderBy('name')->get();
    }

    private static function json($v): array
    {
        if (is_array($v)) {
            return $v;
        }
        $d = json_decode((string) $v, true);
        return is_array($d) ? $d : [];
    }

    /** Weight is stored in kg by some flows (0.5) and grams by others (500). Report always uses kg. */
    private static function kg($w): ?float
    {
        $w = (float) $w;
        if ($w <= 0) {
            return null;
        }
        return round($w >= 50 ? $w / 1000 : $w, 3);
    }

    private static function date($v): ?string
    {
        try {
            return \Illuminate\Support\Carbon::parse($v)->format('Y-m-d');
        } catch (\Throwable $e) {
            return (string) $v;
        }
    }

    private static function phoneOnly(?string $v): string
    {
        $v = (string) $v;
        return str_contains($v, '@') ? '' : $v; // some old accounts have the email saved in the phone field
    }
}
