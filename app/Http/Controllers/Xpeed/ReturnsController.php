<?php

namespace App\Http\Controllers\Xpeed;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\DelhiveryB2CService;
use App\Support\Xpeed\Kit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reverse orders (returns) page.
 * Tabs: delivered forward orders you can create a return for · to be picked up · in progress · received · cancelled.
 * "Auto-assign" books the return with Delhivery reverse pickup (RVP) in one click when Delhivery can pick up
 * from the buyer's pincode; otherwise the seller picks a courier on the existing reverse courier page.
 */
class ReturnsController extends Controller
{
    public const REASONS = [
        'Customer return', 'Damaged product', 'Wrong item delivered', 'Size / fit issue',
        'Quality issue', 'Exchange', 'Other',
    ];

    private function seller()
    {
        return Auth::guard('seller')->user();
    }

    private function hasLinks(): bool
    {
        return Kit::ready('xp_returns');
    }

    private function search($q, string $s)
    {
        $like = '%' . addcslashes($s, '%_\\') . '%';
        return $q->where(function ($w) use ($s, $like) {
            $w->where('awb_number', 'like', $like)->orWhere('order_number', 'like', $like)
              ->orWhere('consignee', 'like', $like);
            if (Kit::hasCol('orders', 'customer_order_id')) {
                $w->orWhere('customer_order_id', 'like', $like);
            }
        });
    }

    public function index(Request $request)
    {
        $seller = $this->seller();
        $tab = $request->get('tab', 'pending');
        $s = trim((string) $request->get('q'));

        // reverse orders, bucketed
        $rq = Order::where('seller_id', $seller->id)->where('reverse', 'reverse');
        if ($s !== '') {
            $this->search($rq, $s);
        }
        $reverse = $rq->orderByDesc('id')->limit(500)->get();
        $group = $reverse->groupBy(function ($o) {
            $b = Kit::bucket($o);
            return match (true) {
                empty($o->awb_number) && $b !== 'cancelled' => 'pending',
                $b === 'delivered' => 'received',
                $b === 'cancelled' => 'cancelled',
                default => 'transit',
            };
        });

        // delivered forward orders without a return yet
        $linked = $this->hasLinks()
            ? DB::table('xp_returns')->where('seller_id', $seller->id)->pluck('original_order_id', 'return_order_id')
            : collect();
        $fq = Order::where('seller_id', $seller->id)
            ->where(fn ($w) => $w->whereNull('reverse')->orWhere('reverse', '!=', 'reverse'))
            ->whereNotNull('awb_number')
            ->whereRaw("LOWER(COALESCE(shipping_status,'')) LIKE '%deliver%'")
            ->whereRaw("LOWER(COALESCE(shipping_status,'')) NOT LIKE '%undeliver%'")
            ->whereRaw("LOWER(COALESCE(shipping_status,'')) NOT LIKE 'rto%'")
            ->where('updated_at', '>=', now()->subDays(90));
        if ($s !== '') {
            $this->search($fq, $s);
        }
        $already = $linked->values()->flip();
        $eligible = $fq->orderByDesc('updated_at')->limit(300)->get()
            ->filter(fn ($o) => Kit::bucket($o) === 'delivered' && !$already->has($o->id))->values();

        $counts = [
            'eligible' => $eligible->count(),
            'pending' => count($group->get('pending', [])),
            'transit' => count($group->get('transit', [])),
            'received' => count($group->get('received', [])),
            'cancelled' => count($group->get('cancelled', [])),
        ];
        $rows = $tab === 'eligible' ? $eligible : collect($group->get($tab, []))->values();

        $meta = $this->hasLinks()
            ? DB::table('xp_returns')->whereIn('return_order_id', $reverse->pluck('id'))->get()->keyBy('return_order_id')
            : collect();
        $origRefs = Order::whereIn('id', $meta->pluck('original_order_id'))->get()->keyBy('id');

        return view('sellerdashboard.returns.index', [
            'seller' => $seller, 'totalAmount' => Kit::balance((int) $seller->id),
            'tab' => $tab, 'rows' => $rows, 'counts' => $counts, 'meta' => $meta, 'origRefs' => $origRefs,
            'reasons' => self::REASONS, 'hasLinks' => $this->hasLinks(),
        ]);
    }

    /** Create return orders from delivered forward orders; optionally auto-assign Delhivery reverse pickup. */
    public function create(Request $request)
    {
        $seller = $this->seller();
        $data = $request->validate([
            'order_ids' => 'required|array|max:100', 'order_ids.*' => 'integer',
            'reason' => 'nullable|string|max:120',
            'auto' => 'nullable|in:0,1',
        ]);
        if (!$this->hasLinks()) {
            return back()->with('error', 'Returns are being set up. Please try again in a few minutes.');
        }
        $reason = in_array($data['reason'] ?? '', self::REASONS, true) ? $data['reason'] : 'Customer return';
        $done = ['created' => 0, 'assigned' => 0, 'manual' => 0, 'skipped' => 0];
        $notes = [];

        foreach (Order::where('seller_id', $seller->id)->whereIn('id', $data['order_ids'])->get() as $orig) {
            if ($orig->reverse === 'reverse' || Kit::bucket($orig) !== 'delivered'
                || DB::table('xp_returns')->where('original_order_id', $orig->id)->exists()) {
                $done['skipped']++;
                continue;
            }
            try {
                $ret = DB::transaction(fn () => $this->cloneAsReturn($orig, $reason));
                $done['created']++;
            } catch (\Throwable $e) {
                Log::error('Return create failed', ['order' => $orig->id, 'e' => $e->getMessage()]);
                $done['skipped']++;
                $notes[] = Kit::ref($orig) . ': could not create (' . mb_strimwidth($e->getMessage(), 0, 60, '…') . ')';
                continue;
            }
            if (($data['auto'] ?? '0') === '1') {
                $r = $this->autoAssign($ret);
                $r['ok'] ? $done['assigned']++ : $done['manual']++;
                if (!$r['ok'] && count($notes) < 8) {
                    $notes[] = Kit::ref($ret) . ': ' . $r['message'];
                }
            }
        }

        $msg = "{$done['created']} return(s) created";
        if (($data['auto'] ?? '0') === '1') {
            $msg .= ", {$done['assigned']} booked with Delhivery reverse pickup, {$done['manual']} need a courier chosen";
        }
        if ($done['skipped']) {
            $msg .= ", {$done['skipped']} skipped (not delivered or return already exists)";
        }
        $msg .= '.' . ($notes ? ' ' . implode(' · ', $notes) : '');
        $to = ($data['auto'] ?? '0') === '1' && $done['assigned'] && !$done['manual'] ? 'transit' : 'pending';
        return redirect()->route('seller.reverse.order', ['tab' => $to])->with($done['created'] ? 'success' : 'error', $msg);
    }

    /** Auto-assign selected "to be picked up" returns with Delhivery reverse pickup. */
    public function assign(Request $request)
    {
        $seller = $this->seller();
        $data = $request->validate(['order_ids' => 'required|array|max:100', 'order_ids.*' => 'integer']);
        $ok = 0; $failed = 0; $notes = [];
        foreach (Order::where('seller_id', $seller->id)->where('reverse', 'reverse')->whereNull('awb_number')->whereIn('id', $data['order_ids'])->get() as $o) {
            $r = $this->autoAssign($o);
            if ($r['ok']) {
                $ok++;
                continue;
            }
            $failed++;
            if (count($notes) < 8) {
                $notes[] = Kit::ref($o) . ': ' . $r['message'];
            }
        }
        $msg = "$ok booked with Delhivery reverse pickup" . ($failed ? ", $failed not booked. " . implode(' · ', $notes) : '.');
        return back()->with($ok ? 'success' : 'error', $msg);
    }

    /** Copy a delivered forward order into a new prepaid reverse order (buyer -> same warehouse). */
    private function cloneAsReturn(Order $orig, string $reason): Order
    {
        $ret = $orig->replicate();
        $lastId = (int) Order::max('id');
        $ret->order_number = 'SPX#' . ($lastId + 1);
        $ret->reverse = 'reverse';
        if (Kit::hasCol('orders', 'customer_order_id')) {
            $ret->customer_order_id = 'R-' . Kit::ref($orig);
        }
        $ret->payment_type = 'prepaid';
        $ret->collectable_amount = 0;
        $ret->cod_charges = 0;
        $ret->courier_id = '';
        foreach (['awb_number', 'shipping_status', 'order_status', 'status', 'label', 'manifest', 'courier_order_id', 'shipment_id',
                  'co_courier_id', 'courier_name', 'all_courier_name', 'shipping_date', 'seller_amount_walate', 'tracking_url',
                  'smartship_tracking_url', 'additional_info', 'co_payment_type', 'fwd_destination_code', 'booking_error',
                  'booking_error_at', 'smarship_courier_id'] as $col) {
            if (Kit::hasCol('orders', $col)) {
                $ret->{$col} = null;
            }
        }
        // stored courier payloads: new order number, prepaid, no COD
        foreach (['delhivery_b2c', 'delhivery_b2c_air', 'encode_data', 'shadowfax_json', 'DelhiveryServiceb2b'] as $col) {
            if (!Kit::hasCol('orders', $col) || empty($orig->{$col})) {
                continue;
            }
            $ret->{$col} = str_replace(json_encode((string) $orig->order_number), json_encode($ret->order_number), (string) $orig->{$col});
        }
        if (!empty($ret->delhivery_b2c)) {
            $p = json_decode($ret->delhivery_b2c, true) ?: [];
            foreach (($p['shipments'] ?? []) as $i => $sh) {
                $p['shipments'][$i]['order'] = $ret->order_number;
                $p['shipments'][$i]['payment_mode'] = 'Prepaid';
                $p['shipments'][$i]['cod_amount'] = '0';
            }
            $ret->delhivery_b2c = json_encode($p);
        }
        $ret->created_at = now();
        $ret->updated_at = now();
        $ret->save();

        DB::table('xp_returns')->insert([
            'seller_id' => $orig->seller_id, 'return_order_id' => $ret->id, 'original_order_id' => $orig->id,
            'reason' => $reason, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $ret;
    }

    /** @return array{ok:bool, message:string} */
    private function autoAssign(Order $o): array
    {
        $note = function (string $status, string $msg) use ($o) {
            if ($this->hasLinks()) {
                DB::table('xp_returns')->where('return_order_id', $o->id)->update(['assign_status' => $status, 'assign_message' => mb_substr($msg, 0, 500), 'updated_at' => now()]);
            }
        };
        try {
            if (empty($o->delhivery_b2c) || !app()->bound('courier.delhivery_b2c')) {
                $note('manual', 'Delhivery details missing on this order');
                return ['ok' => false, 'message' => 'choose a courier manually'];
            }
            $svc = app('courier.delhivery_b2c');
            $rows = $svc->reversegetServiceability(['order_id' => $o->id]);
            if (!$rows) {
                $note('manual', 'Delhivery cannot pick up from this pincode or no Delhivery rate is set');
                return ['ok' => false, 'message' => 'Delhivery cannot pick up here, choose another courier'];
            }
            $charge = (float) $rows[0]['courierCharge'];
            $o->seller_amount_walate = $charge;
            $o->save();

            $res = $svc->assignOrder(DelhiveryB2CService::toRvpPayload(json_decode($o->delhivery_b2c)));
            $awb = $res['packages'][0]['waybill'] ?? null;
            if (!$awb) {
                $msg = $res['message'] ?? 'Delhivery did not return an AWB';
                $o->seller_amount_walate = null;
                $o->save();
                $note('failed', $msg);
                return ['ok' => false, 'message' => mb_strimwidth($msg, 0, 90, '…')];
            }
            $o->refresh();
            $o->awb_number = $awb;
            $o->courier_id = 'delhivery_b2c';
            if (Kit::hasCol('orders', 'courier_name')) {
                $o->courier_name = 'Delhivery Reverse';
            }
            $o->save();
            $note('auto_assigned', 'Delhivery reverse pickup ' . $awb . ' · ' . Kit::money($charge));
            return ['ok' => true, 'message' => $awb];
        } catch (\Throwable $e) {
            Log::error('Return auto-assign failed', ['order' => $o->id, 'e' => $e->getMessage()]);
            $note('failed', $e->getMessage());
            return ['ok' => false, 'message' => 'booking error, try choosing the courier manually'];
        }
    }
}
