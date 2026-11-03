<?php

namespace App\Http\Controllers\Cod;

use App\Exports\CodPayoutExport;
use App\Http\Controllers\Controller;
use App\Support\Cod\CodRemittance as Cod;
use App\Support\Xpeed\Kit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Admin › COD remittance: what is payable to whom (date-wise, with bank details), Excel for the bank
 * upload, mark paid (all or selected sellers) which emails each seller, payout history, upcoming dates.
 */
class AdminCodController extends Controller
{
    private function date($v): ?string
    {
        return $v && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? (string) $v : null;
    }

    private function filters(Request $r): array
    {
        $tab = in_array($r->tab, ['due', 'upcoming', 'payouts', 'orders'], true) ? $r->tab : 'due';
        $today = now()->toDateString();
        $from = $this->date($r->from);
        $to = $this->date($r->to);
        if ($tab === 'due') {
            $to = $to && $to <= $today ? $to : $today;    // only what is payable by today (or an earlier cut-off)
        }
        if ($tab === 'upcoming') {
            $from = $from && $from > $today ? $from : now()->addDay()->toDateString();
            $to ??= now()->addDays(14)->toDateString();
        }
        return [
            'tab'    => $tab,
            'from'   => $from,
            'to'     => $to,
            'seller' => $r->filled('seller') ? (int) $r->seller : null,
            'status' => in_array($r->status, ['upcoming', 'due', 'paid', 'adjusted'], true) ? $r->status : null,
            'by'     => $r->by === 'delivered' ? 'delivered_on' : 'due_on',
            'q'      => trim((string) $r->q),
        ];
    }

    private function sellers(): array
    {
        $ids = Kit::ready('cod_remittances') ? DB::table('cod_remittances')->distinct()->pluck('seller_id')->all() : [];
        return DB::table('seller_lists')->whereIn('id', $ids)->orderBy('name')->pluck('name', 'id')->all();
    }

    /** Order-level query for the "All COD orders" tab, seller page and order-wise export. */
    private function ordersQuery(array $f)
    {
        $today = now()->toDateString();
        return DB::table('cod_remittances')
            ->when($f['seller'], fn ($q) => $q->where('seller_id', $f['seller']))
            ->when($f['from'], fn ($q) => $q->where($f['by'], '>=', $f['from']))
            ->when($f['to'], fn ($q) => $q->where($f['by'], '<=', $f['to']))
            ->when($f['q'] !== '', fn ($q) => $q->where(fn ($w) => $w->where('awb', 'like', '%' . $f['q'] . '%')->orWhere('order_number', 'like', '%' . $f['q'] . '%')))
            ->when($f['status'] === 'upcoming', fn ($q) => $q->where('status', 'pending')->where('due_on', '>', $today))
            ->when($f['status'] === 'due', fn ($q) => $q->where('status', 'pending')->where('due_on', '<=', $today))
            ->when(in_array($f['status'], ['paid', 'adjusted'], true), fn ($q) => $q->where('status', $f['status']));
    }

    public function index(Request $request)
    {
        $ready = Kit::ready('cod_remittances', 'cod_payouts');
        $f = $this->filters($request);
        $sellers = $ready ? $this->sellers() : [];
        $data = ['ready' => $ready, 'f' => $f, 'sellers' => $sellers, 'kpi' => [], 'lines' => [], 'byDate' => collect(),
                 'payouts' => null, 'orders' => null, 'plans' => Cod::plans()];
        if (!$ready) {
            return view('cod.admin', $data);
        }

        $refresh = Cod::refresh();
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $due = Cod::payableQuery(null, $today)->selectRaw('COUNT(*) AS orders, COUNT(DISTINCT seller_id) AS sellers, COALESCE(SUM(net_payable),0) AS net')->first();
        $up = Cod::payableQuery(now()->addDay()->toDateString(), now()->addDays(7)->toDateString())->selectRaw('COUNT(DISTINCT seller_id) AS sellers, COALESCE(SUM(net_payable),0) AS net')->first();
        $paidMonth = DB::table('cod_payouts')->where('paid_on', '>=', $monthStart)->selectRaw('COUNT(*) AS n, COALESCE(SUM(amount),0) AS amt')->first();
        $fee = DB::table('cod_remittances')->selectRaw("COALESCE(SUM(fee_amount),0) AS total, COALESCE(SUM(CASE WHEN delivered_on >= ? THEN fee_amount END),0) AS month, COALESCE(SUM(wallet_adjusted),0) AS adj", [$monthStart])->first();
        $data['kpi'] = [
            'due' => (float) $due->net, 'due_sellers' => (int) $due->sellers, 'due_orders' => (int) $due->orders,
            'up7' => (float) $up->net, 'up7_sellers' => (int) $up->sellers,
            'paid_month' => (float) $paidMonth->amt, 'paid_month_n' => (int) $paidMonth->n,
            'fee' => (float) $fee->total, 'fee_month' => (float) $fee->month, 'adj' => (float) $fee->adj,
            'refresh' => $refresh,
        ];

        if ($f['tab'] === 'due' || $f['tab'] === 'upcoming') {
            $data['lines'] = Cod::payableBySeller($f['from'], $f['to'], $f['seller']);
            $data['byDate'] = Cod::payableQuery($f['from'], $f['to'], $f['seller'])
                ->selectRaw('due_on, COUNT(DISTINCT seller_id) AS sellers, COUNT(*) AS orders, SUM(cod_amount) AS cod, SUM(fee_amount) AS fee, SUM(wallet_adjusted) AS adj, SUM(net_payable) AS net')
                ->groupBy('due_on')->orderBy('due_on')->get();
        } elseif ($f['tab'] === 'payouts') {
            $data['payouts'] = DB::table('cod_payouts')
                ->when($f['seller'], fn ($q) => $q->where('seller_id', $f['seller']))
                ->when($f['from'], fn ($q) => $q->where('paid_on', '>=', $f['from']))
                ->when($f['to'], fn ($q) => $q->where('paid_on', '<=', $f['to']))
                ->orderByDesc('paid_on')->orderByDesc('id')->paginate(50)->withQueryString();
        } else {
            $data['orders'] = $this->ordersQuery($f)->orderByDesc('delivered_on')->orderByDesc('id')->paginate(100)->withQueryString();
        }

        return view('cod.admin', $data);
    }

    /** One seller: every COD line with filters (opens from the due list). */
    public function seller(Request $request, int $sellerId)
    {
        $request->merge(['tab' => 'orders', 'seller' => $sellerId]);
        return $this->index($request);
    }

    public function refresh()
    {
        $r = Cod::refresh();
        return back()->with('success', sprintf('Refreshed: %d new COD generated, %d wallets topped up (₹%s).', $r['created'], $r['sellers'], number_format($r['amount'], 2)));
    }

    public function export(Request $request)
    {
        abort_unless(Kit::ready('cod_remittances', 'cod_payouts'), 404);
        if (!$request->filled('to') && $request->filled('up_to')) {
            $request->merge(['to' => $request->up_to]);   // "Excel of selected" posts the mark-paid form fields
        }
        $f = $this->filters($request);
        $type = $request->type === 'orders' ? 'orders' : ($f['tab'] === 'payouts' ? 'payouts' : 'payout');
        $stamp = now()->format('Ymd-Hi');

        if ($type === 'payout') {
            // one row per seller: what to transfer and where
            $only = array_filter(array_map('intval', (array) $request->input('sellers', [])));
            $lines = collect(Cod::payableBySeller($f['from'], $f['to'], $f['seller']))
                ->when($only, fn ($c) => $c->whereIn('seller_id', $only))->values();
            $rows = $lines->map(fn ($l) => [
                $l['seller_id'], $l['bank']['name'] ?? '', $l['bank']['email'] ?? '', $l['bank']['phone'] ?? '',
                $l['bank']['holder'] ?? '', $l['bank']['bank'] ?? '', $l['bank']['account'] ?? '', $l['bank']['ifsc'] ?? '',
                ($l['bank']['verified'] ?? false) ? 'Yes' : 'No',
                $l['orders'], $l['cod'], $l['fee'], $l['adj'], $l['net'],
                $l['first_due'], $l['last_due'], 'COD ' . now()->format('d-m-Y'),
            ])->all();
            $rows[] = ['', 'TOTAL', '', '', '', '', '', '', '', $lines->sum('orders'), round($lines->sum('cod'), 2), round($lines->sum('fee'), 2), round($lines->sum('adj'), 2), round($lines->sum('net'), 2), '', '', ''];
            $head = ['Seller ID', 'Seller', 'Email', 'Phone', 'Account holder', 'Bank', 'Account number', 'IFSC', 'Bank verified',
                     'Orders', 'COD amount', 'Early COD deduction', 'Adjusted to wallet', 'Net payable', 'Payable from', 'Payable up to', 'Narration'];
            return Excel::download(new CodPayoutExport($head, $rows, ['Account number', 'IFSC', 'Phone'], 'COD payout'), "cod-payout-$stamp.xlsx");
        }

        if ($type === 'payouts') {
            $p = DB::table('cod_payouts')
                ->when($f['seller'], fn ($q) => $q->where('seller_id', $f['seller']))
                ->when($f['from'], fn ($q) => $q->where('paid_on', '>=', $f['from']))
                ->when($f['to'], fn ($q) => $q->where('paid_on', '<=', $f['to']))
                ->orderByDesc('paid_on')->limit(20000)->get();
            $names = DB::table('seller_lists')->whereIn('id', $p->pluck('seller_id'))->pluck('name', 'id');
            $rows = $p->map(fn ($x) => [$x->id, $x->paid_on, $x->seller_id, $names[$x->seller_id] ?? '', $x->account_holder, $x->bank_name, $x->account_number, $x->ifsc,
                $x->orders, $x->cod_amount, $x->fee_amount, $x->wallet_adjusted, $x->amount, $x->reference, $x->mailed_at ? 'Sent' : ($x->mail_error ?: 'Not sent')])->all();
            $head = ['Payout ID', 'Paid on', 'Seller ID', 'Seller', 'Account holder', 'Bank', 'Account number', 'IFSC', 'Orders', 'COD amount', 'Deduction', 'Adjusted to wallet', 'Paid', 'Reference', 'Email'];
            return Excel::download(new CodPayoutExport($head, $rows, ['Account number', 'IFSC', 'Reference'], 'Payouts'), "cod-payouts-$stamp.xlsx");
        }

        // order-wise
        if ($f['tab'] === 'due' || $f['tab'] === 'upcoming') {
            $q = Cod::payableQuery($f['from'], $f['to'], $f['seller']);
        } else {
            $q = $this->ordersQuery($f);
        }
        $only = array_filter(array_map('intval', (array) $request->input('sellers', [])));
        $list = $q->when($only, fn ($x) => $x->whereIn('seller_id', $only))->orderBy('seller_id')->orderBy('due_on')->limit(50000)->get();
        $names = DB::table('seller_lists')->whereIn('id', $list->pluck('seller_id')->unique())->pluck('name', 'id');
        $today = now()->toDateString();
        $plans = Cod::plans();
        $rows = $list->map(fn ($r) => [$r->seller_id, $names[$r->seller_id] ?? '', $r->awb, $r->order_number, $r->courier, $r->delivered_on,
            $r->cod_amount, $plans[$r->plan]['label'] ?? $r->plan, $r->fee_pct, $r->fee_amount, $r->wallet_adjusted, $r->net_payable, $r->due_on,
            Cod::STATUS[Cod::state($r, $today)][0], $r->paid_at])->all();
        $head = ['Seller ID', 'Seller', 'AWB', 'Order', 'Courier', 'Delivered on', 'COD amount', 'Plan', 'Deduction %', 'Deduction', 'Adjusted to wallet', 'Net payable', 'Payable date', 'Status', 'Paid at'];
        return Excel::download(new CodPayoutExport($head, $rows, ['AWB', 'Order'], 'COD orders'), "cod-orders-$stamp.xlsx");
    }

    public function markPaid(Request $request)
    {
        abort_unless(Kit::ready('cod_remittances', 'cod_payouts'), 404);
        $data = $request->validate([
            'scope'     => 'required|in:all,selected',
            'sellers'   => 'array',
            'sellers.*' => 'integer',
            'paid_on'   => 'required|date_format:Y-m-d|before_or_equal:today',
            'reference' => 'nullable|string|max:120',
            'up_to'     => 'nullable|date_format:Y-m-d',
            'from'      => 'nullable|date_format:Y-m-d',
        ]);
        $upTo = $data['up_to'] ?? now()->toDateString();
        if ($upTo > now()->toDateString()) {
            $upTo = now()->toDateString();   // never pay COD before its payable date
        }
        $from = $data['from'] ?? null;

        $ids = $data['scope'] === 'all'
            ? Cod::payableQuery($from, $upTo)->distinct()->pluck('seller_id')->map(fn ($v) => (int) $v)->all()
            : array_values(array_unique(array_map('intval', $data['sellers'] ?? [])));
        if (!$ids) {
            return back()->with('error', 'Select at least one seller to mark paid.');
        }

        $res = Cod::markPaid($ids, $upTo, $data['paid_on'], $data['reference'] ?? null, Auth::id(), $from);
        if (!$res) {
            return back()->with('error', 'Nothing was payable for the selected sellers (it may have been adjusted to their wallet).');
        }
        $amt = array_sum(array_column($res, 'amount'));
        $failed = array_filter($res, fn ($r) => !$r['mailed']);
        $msg = sprintf('Marked paid: %d seller(s), %d orders, ₹%s. Email sent to %d.', count($res), array_sum(array_column($res, 'orders')),
            number_format($amt, 2), count($res) - count($failed));
        if ($failed) {
            $msg .= ' Email not sent to ' . count($failed) . ' (see Payouts tab, you can resend).';
        }
        return redirect()->route('codremittance.index', ['tab' => 'payouts'])->with('success', $msg);
    }

    public function resend(int $id)
    {
        $p = DB::table('cod_payouts')->where('id', $id)->first();
        abort_unless($p, 404);
        [$ok, $err] = Cod::mailPayout($p);
        return back()->with($ok ? 'success' : 'error', $ok ? 'Email sent again.' : 'Email failed: ' . $err);
    }
}
