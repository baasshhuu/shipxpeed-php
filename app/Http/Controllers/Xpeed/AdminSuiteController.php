<?php

namespace App\Http\Controllers\Xpeed;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SellerList;
use App\Services\WhatsApp\NdrActionDispatcher;
use App\Support\Xpeed\Kit;
use App\Support\Xpeed\Protection;
use App\Support\Xpeed\RtoRisk;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Admin › Control Center: NDR desk, courier performance, RTO risk,
 * shipment protection / claims / early COD.
 */
class AdminSuiteController extends Controller
{
    private function sellerNames($ids): array
    {
        return SellerList::whereIn('id', collect($ids)->filter()->unique()->values())->pluck('name', 'id')->all();
    }

    // ================================================================= NDR desk

    public function ndr(Request $request)
    {
        $ready = Kit::ready('ndr_responses');
        $status = $request->get('status', 'open');
        $rows = collect();
        $counts = [];
        if ($ready) {
            $q = DB::table('ndr_responses')->whereNotNull('action');
            if ($status === 'open') {
                $q->whereIn('status', ['needs_manual', 'failed', 'processing']);
            } elseif ($status !== 'all') {
                $q->where('status', $status);
            }
            if ($s = trim((string) $request->get('q'))) {
                $q->where('awb_number', $s);
            }
            $rows = $q->orderByDesc('responded_at')->orderByDesc('id')->limit(300)->get();
            $counts = DB::table('ndr_responses')->whereNotNull('action')->where('responded_at', '>=', now()->subDays(30))
                ->select('status', DB::raw('COUNT(*) c'))->groupBy('status')->pluck('c', 'status')->all();
        }
        $orders = Order::whereIn('id', $rows->pluck('order_id')->unique())->get()->keyBy('id');
        $awaiting = $ready ? Order::whereNotNull('awb_number')->where('updated_at', '>=', now()->subDays(15))
            ->whereRaw("LOWER(COALESCE(shipping_status,'')) = 'ndr'")->count() : 0;

        return view('admin.xpeed.ndr', [
            'ready' => $ready, 'rows' => $rows, 'orders' => $orders, 'status' => $status, 'counts' => $counts,
            'sellers' => $this->sellerNames($rows->pluck('seller_id')), 'awaiting' => $awaiting,
        ]);
    }

    public function ndrRetry(int $id, NdrActionDispatcher $dispatcher)
    {
        $row = DB::table('ndr_responses')->where('id', $id)->first();
        $order = $row ? Order::find($row->order_id) : null;
        if (!$row || !$order || !$row->action) {
            return back()->with('error', 'NDR request not found.');
        }
        $res = $dispatcher->dispatch($order, $row->action, [
            'reattempt_date' => $row->reattempt_date && Carbon::parse($row->reattempt_date)->isFuture() ? $row->reattempt_date : now()->addDay()->toDateString(),
            'address' => $row->address, 'landmark' => $row->landmark, 'phone' => $row->phone, 'remark' => $row->remark,
        ]);
        DB::table('ndr_responses')->where('id', $id)->update([
            'status' => $res['status'], 'courier_response' => mb_substr(json_encode($res['response'] ?? $res['message']), 0, 60000), 'updated_at' => now(),
        ]);
        return back()->with($res['status'] === 'failed' ? 'error' : 'success', 'AWB ' . $order->awb_number . ': ' . $res['message']);
    }

    public function ndrDone(Request $request, int $id)
    {
        $note = mb_substr(strip_tags((string) $request->input('note')), 0, 255);
        $upd = ['status' => 'actioned_by_ops', 'updated_at' => now()];
        if (Kit::hasCol('ndr_responses', 'ops_note')) {
            $upd['ops_note'] = $note ?: null;
            $upd['handled_by'] = Auth::id();
        }
        DB::table('ndr_responses')->where('id', $id)->update($upd);
        return back()->with('success', 'Marked as actioned with the courier.');
    }

    // ================================================================= Courier performance

    public function performance(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $days = in_array($days, [7, 30, 90, 180], true) ? $days : 30;
        $sellerId = (int) $request->get('seller_id', 0);
        $hasDelivered = Kit::hasCol('orders', 'delivered_date');

        $cols = ['id', 'seller_id', 'courier_id', 'courier_name', 'awb_number', 'shipping_status', 'order_status', 'payment_type', 'created_at'];
        foreach (['zone_courier_name', 'all_courier_name', 'zone', 'delivered_date', 'shipping_date'] as $c) {
            if (Kit::hasCol('orders', $c)) {
                $cols[] = $c;
            }
        }
        $q = Order::whereNotNull('awb_number')->where('awb_number', '!=', '')
            ->where('created_at', '>=', now()->subDays($days))
            ->when($sellerId, fn ($w) => $w->where('seller_id', $sellerId));

        $ndrOrders = Kit::ready('ndr_responses')
            ? DB::table('ndr_responses')->where('created_at', '>=', now()->subDays($days + 15))->pluck('order_id')->flip()->all() : [];

        $stats = [];
        $zoneStats = [];
        $total = 0;
        foreach ($q->select($cols)->orderBy('id')->limit(60000)->cursor() as $o) {
            $b = Kit::bucket($o);
            if ($b === 'cancelled') {
                continue;
            }
            $total++;
            $label = Kit::courierLabel($o);
            $s = &$stats[$label];
            $s ??= ['total' => 0, 'delivered' => 0, 'rto' => 0, 'ndr_now' => 0, 'ndr_ever' => 0, 'open' => 0, 'cod' => 0, 'days_sum' => 0, 'days_n' => 0, 'stuck' => 0];
            $s['total']++;
            $s['cod'] += strtolower((string) $o->payment_type) === 'cod' ? 1 : 0;
            if ($b === 'delivered') {
                $s['delivered']++;
                if ($hasDelivered && $o->delivered_date) {
                    $start = $o->shipping_date ?? $o->created_at;
                    $d = Carbon::parse($start)->diffInHours(Carbon::parse($o->delivered_date)) / 24;
                    if ($d >= 0 && $d < 30) {
                        $s['days_sum'] += $d;
                        $s['days_n']++;
                    }
                }
            } elseif ($b === 'rto') {
                $s['rto']++;
            } else {
                $s['open']++;
                if ($o->created_at && $o->created_at->lt(now()->subDays(10))) {
                    $s['stuck']++;
                }
            }
            if ($b === 'ndr') {
                $s['ndr_now']++;
            }
            if ($b === 'ndr' || isset($ndrOrders[$o->id])) {
                $s['ndr_ever']++;
            }
            $zone = strtoupper((string) ($o->zone ?? '')) ?: '—';
            $z = &$zoneStats[$label][$zone];
            $z ??= ['total' => 0, 'delivered' => 0, 'rto' => 0];
            $z['total']++;
            $z['delivered'] += $b === 'delivered' ? 1 : 0;
            $z['rto'] += $b === 'rto' ? 1 : 0;
            unset($s, $z);
        }
        uasort($stats, fn ($a, $b) => $b['total'] <=> $a['total']);

        return view('admin.xpeed.performance', [
            'stats' => $stats, 'zoneStats' => $zoneStats, 'days' => $days, 'total' => $total, 'sellerId' => $sellerId,
            'sellers' => SellerList::orderBy('name')->limit(2000)->pluck('name', 'id'),
            'hasDelivered' => $hasDelivered,
        ]);
    }

    // ================================================================= RTO risk (all sellers)

    public function risk(Request $request)
    {
        $orders = Order::where(fn ($q) => $q->whereNull('awb_number')->orWhere('awb_number', ''))
            ->where('payment_type', 'cod')->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('id')->limit(300)->get()->filter(fn ($o) => Kit::bucket($o) === 'new');
        $rows = $orders->map(fn ($o) => ['order' => $o, 'risk' => RtoRisk::score($o)])
            ->filter(fn ($r) => $r['risk']['score'] >= (int) $request->get('min', 35))
            ->sortByDesc(fn ($r) => $r['risk']['score'])->values();

        // seller RTO rates (last 60 days, closed shipments only)
        $sellerRto = [];
        foreach (Order::whereNotNull('awb_number')->where('created_at', '>=', now()->subDays(60))
                     ->select(['id', 'seller_id', 'shipping_status', 'order_status', 'awb_number'])->limit(60000)->cursor() as $o) {
            $b = Kit::bucket($o);
            if (!in_array($b, ['delivered', 'rto'], true)) {
                continue;
            }
            $sellerRto[$o->seller_id] ??= ['closed' => 0, 'rto' => 0];
            $sellerRto[$o->seller_id]['closed']++;
            $sellerRto[$o->seller_id]['rto'] += $b === 'rto' ? 1 : 0;
        }
        $sellerRto = collect($sellerRto)->filter(fn ($v) => $v['closed'] >= 10)
            ->map(fn ($v) => $v + ['rate' => round($v['rto'] * 100 / $v['closed'], 1)])->sortByDesc('rate')->take(30);

        return view('admin.xpeed.risk', [
            'rows' => $rows, 'sellerRto' => $sellerRto, 'min' => (int) $request->get('min', 35),
            'sellers' => $this->sellerNames($rows->pluck('order.seller_id')->merge($sellerRto->keys())),
        ]);
    }

    // ================================================================= Protection, claims, early COD

    public function services()
    {
        $ready = Kit::ready('xp_settings', 'xp_seller_services', 'xp_protection_charges', 'xp_claims');
        $settings = [];
        foreach (array_keys(Kit::DEFAULTS) as $k) {
            $settings[$k] = Kit::setting($k);
        }
        $claims = $ready ? DB::table('xp_claims')->orderByRaw("status = 'pending' DESC")->orderByDesc('id')->limit(200)->get() : collect();
        $services = $ready ? DB::table('xp_seller_services')->where(fn ($q) => $q->where('protection_on', 1)->orWhereNotNull('early_cod_status'))->orderByDesc('updated_at')->get() : collect();
        $earlyDue = [];
        foreach ($services as $s) {
            if ($s->early_cod_status === 'active' && $s->early_cod_plan) {
                $due = Protection::earlyCodDue((int) $s->seller_id, $s->early_cod_plan);
                $pct = Protection::earlyCodPlans()[$s->early_cod_plan]['pct'] ?? 0;
                $amt = (float) $due->sum('collectable_amount');
                $earlyDue[$s->seller_id] = ['count' => $due->count(), 'amount' => $amt, 'fee' => round($amt * $pct / 100, 2), 'pct' => $pct];
            }
        }
        $month = $ready ? DB::table('xp_protection_charges')->where('status', 'charged')->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('COUNT(*) c, COALESCE(SUM(fee),0) f')->first() : null;
        $paid = $ready ? (float) DB::table('xp_claims')->where('status', 'approved')->where('reviewed_at', '>=', now()->startOfMonth())->sum('approved_amount') : 0;

        return view('admin.xpeed.services', [
            'ready' => $ready, 'settings' => $settings, 'claims' => $claims, 'services' => $services, 'earlyDue' => $earlyDue,
            'plans' => Protection::earlyCodPlans(), 'month' => $month, 'paid' => $paid,
            'sellers' => $this->sellerNames($claims->pluck('seller_id')->merge($services->pluck('seller_id'))),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'protection_enabled' => 'required|in:0,1',
            'protection_fee_pct' => 'required|numeric|min:0|max:10',
            'protection_min_fee' => 'required|numeric|min:0|max:1000',
            'protection_min_value' => 'required|numeric|min:0|max:500000',
            'protection_max_cover' => 'required|numeric|min:0|max:1000000',
            'early_cod_enabled' => 'required|in:0,1',
            'early_cod_d2_pct' => 'required|numeric|min:0|max:10',
            'early_cod_d3_pct' => 'required|numeric|min:0|max:10',
            'early_cod_d4_pct' => 'required|numeric|min:0|max:10',
            'claim_window_days' => 'required|integer|min:1|max:180',
        ]);
        Kit::saveSettings($data);
        return back()->with('success', 'Settings saved.');
    }

    public function reviewClaim(Request $request, int $id)
    {
        $data = $request->validate([
            'decision' => 'required|in:approve,reject',
            'amount' => 'nullable|numeric|min:0|max:1000000',
            'remark' => 'nullable|string|max:500',
        ]);
        $msg = DB::transaction(function () use ($id, $data) {
            $c = DB::table('xp_claims')->where('id', $id)->lockForUpdate()->first();
            if (!$c || $c->status !== 'pending') {
                return ['error', 'This claim is not pending.'];
            }
            $upd = ['status' => $data['decision'] === 'approve' ? 'approved' : 'rejected', 'admin_remark' => $data['remark'] ?? null,
                'reviewed_by' => Auth::id(), 'reviewed_at' => now(), 'updated_at' => now()];
            if ($data['decision'] === 'approve') {
                $cap = $c->protected ? min((float) $c->declared_value ?: (float) $c->claim_amount, Kit::num('protection_max_cover')) : (float) $c->claim_amount;
                $amount = round(min((float) ($data['amount'] ?? $c->claim_amount), max($cap, 0)), 2);
                if ($amount > 0) {
                    $rc = Kit::credit((int) $c->seller_id, $amount, 'Shipment claim settled (' . str_replace('_', ' ', $c->type) . ') - AWB ' . $c->awb);
                    $upd['recharge_id'] = $rc->id;
                }
                $upd['approved_amount'] = $amount;
            }
            DB::table('xp_claims')->where('id', $id)->update($upd);
            return ['success', $data['decision'] === 'approve' ? 'Claim approved, ' . Kit::money($upd['approved_amount']) . ' credited to the seller wallet.' : 'Claim rejected.'];
        });
        return back()->with($msg[0], $msg[1]);
    }

    public function claimProof(int $id, int $n)
    {
        $c = DB::table('xp_claims')->where('id', $id)->first();
        $p = $c ? (json_decode((string) $c->proofs, true)[$n] ?? null) : null;
        abort_unless($p && Storage::disk('local')->exists($p['path']), 404);
        return Storage::disk('local')->response($p['path'], $p['name']);
    }

    public function decideEarlyCod(Request $request, int $sellerId)
    {
        $data = $request->validate(['decision' => 'required|in:approve,reject,stop', 'note' => 'nullable|string|max:255']);
        $status = ['approve' => 'active', 'reject' => 'rejected', 'stop' => 'stopped'][$data['decision']];
        Protection::saveService($sellerId, ['early_cod_status' => $status, 'early_cod_decided_at' => now(), 'early_cod_note' => $data['note'] ?? null]);
        return back()->with('success', 'Early COD ' . $status . ' for seller #' . $sellerId . '.');
    }

    /** Admin has paid the due COD to the seller's bank: mark those orders Paid and debit the early-COD fee. */
    public function remitEarlyCod(Request $request, int $sellerId)
    {
        $data = $request->validate(['utr' => 'required|string|max:60']);
        $svc = Protection::service($sellerId);
        if ($svc->early_cod_status !== 'active' || !$svc->early_cod_plan) {
            return back()->with('error', 'Early COD is not active for this seller.');
        }
        $due = Protection::earlyCodDue($sellerId, $svc->early_cod_plan);
        if ($due->isEmpty()) {
            return back()->with('error', 'Nothing due right now.');
        }
        $pct = Protection::earlyCodPlans()[$svc->early_cod_plan]['pct'] ?? 0;
        $amount = (float) $due->sum('collectable_amount');
        $fee = round($amount * $pct / 100, 2);
        DB::transaction(function () use ($due, $sellerId, $fee, $data, $amount) {
            Order::whereIn('id', $due->pluck('id'))->update(['payment_status' => 'Paid']);
            if ($fee > 0) {
                Kit::debit($sellerId, $fee, 'Early COD fee on ' . Kit::money($amount) . ' remitted (UTR ' . strip_tags($data['utr']) . ')');
            }
        });
        return back()->with('success', $due->count() . ' COD order(s) marked remitted (' . Kit::money($amount) . '), fee ' . Kit::money($fee) . ' debited from wallet.');
    }

    public function exportEarlyCod(int $sellerId)
    {
        $svc = Protection::service($sellerId);
        $due = $svc->early_cod_plan ? Protection::earlyCodDue($sellerId, $svc->early_cod_plan) : collect();
        $bank = Kit::ready('seller_bank_details') ? DB::table('seller_bank_details')->where('seller_id', $sellerId)->first() : null;
        return Kit::csv('early-cod-seller-' . $sellerId . '-' . now()->format('Ymd') . '.csv',
            ['AWB', 'Order No.', 'COD amount', 'Delivered on', 'Account number', 'IFSC', 'Account holder'],
            $due->map(fn ($o) => [$o->awb_number, Kit::ref($o), $o->collectable_amount, $o->delivered_date,
                $bank->account_number ?? '', $bank->ifsc_code ?? '', $bank->account_holder_name ?? '']));
    }
}
