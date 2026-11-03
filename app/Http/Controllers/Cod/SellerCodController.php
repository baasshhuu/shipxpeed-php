<?php

namespace App\Http\Controllers\Cod;

use App\Http\Controllers\Controller;
use App\Support\Cod\CodRemittance as Cod;
use App\Support\Xpeed\Kit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Seller › COD remittance: what was generated, when it is payable, deductions, payouts, and the plan switch. */
class SellerCodController extends Controller
{
    private function seller()
    {
        $s = Auth::guard('seller')->user();
        abort_unless($s, 403);
        return $s;
    }

    private function filters(Request $r): array
    {
        $tab = in_array($r->tab, ['all', 'upcoming', 'due', 'paid', 'adjusted', 'payouts'], true) ? $r->tab : 'all';
        $by = $r->by === 'due' ? 'due_on' : 'delivered_on';
        return [
            'tab'  => $tab,
            'by'   => $by,
            'from' => $this->date($r->from),
            'to'   => $this->date($r->to),
            'q'    => trim((string) $r->q),
        ];
    }

    private function date($v): ?string
    {
        return $v && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? (string) $v : null;
    }

    private function rows(int $sid, array $f)
    {
        $today = now()->toDateString();
        return DB::table('cod_remittances')->where('seller_id', $sid)
            ->when($f['from'], fn ($q) => $q->where($f['by'], '>=', $f['from']))
            ->when($f['to'], fn ($q) => $q->where($f['by'], '<=', $f['to']))
            ->when($f['q'] !== '', fn ($q) => $q->where(fn ($w) => $w->where('awb', 'like', '%' . $f['q'] . '%')->orWhere('order_number', 'like', '%' . $f['q'] . '%')))
            ->when($f['tab'] === 'upcoming', fn ($q) => $q->where('status', 'pending')->where('due_on', '>', $today))
            ->when($f['tab'] === 'due', fn ($q) => $q->where('status', 'pending')->where('due_on', '<=', $today))
            ->when(in_array($f['tab'], ['paid', 'adjusted'], true), fn ($q) => $q->where('status', $f['tab']));
    }

    public function index(Request $request)
    {
        $seller = $this->seller();
        $sid = (int) $seller->id;
        $ready = Kit::ready('cod_remittances', 'cod_payouts');
        $f = $this->filters($request);
        $plans = Cod::plans();
        $plan = Cod::sellerPlan($sid);

        $sum = ['generated' => 0, 'fee' => 0, 'adj' => 0, 'paid' => 0, 'upcoming' => 0, 'due' => 0, 'orders' => 0];
        $next = null;
        $rows = null;
        $payouts = collect();
        $schedule = collect();
        $balance = 0.0;

        if ($ready) {
            Cod::refresh($sid);
            $today = now()->toDateString();
            $all = DB::table('cod_remittances')->where('seller_id', $sid)->selectRaw(
                "COUNT(*) AS orders, COALESCE(SUM(cod_amount),0) AS cod_total, COALESCE(SUM(fee_amount),0) AS fee, COALESCE(SUM(wallet_adjusted),0) AS adj,
                 COALESCE(SUM(CASE WHEN status='paid' THEN net_payable END),0) AS paid,
                 COALESCE(SUM(CASE WHEN status='pending' AND due_on > ? THEN net_payable END),0) AS upcoming,
                 COALESCE(SUM(CASE WHEN status='pending' AND due_on <= ? THEN net_payable END),0) AS due", [$today, $today])->first();
            $sum = array_map('floatval', (array) $all);
            $sum['generated'] = $sum['cod_total'];

            $next = DB::table('cod_remittances')->where('seller_id', $sid)->where('status', 'pending')->where('net_payable', '>', 0)
                ->selectRaw('due_on, SUM(net_payable) AS amount, COUNT(*) AS orders')->groupBy('due_on')->orderBy('due_on')->first();

            // upcoming payable dates (next 10)
            $schedule = DB::table('cod_remittances')->where('seller_id', $sid)->where('status', 'pending')->where('net_payable', '>', 0)
                ->selectRaw('due_on, COUNT(*) AS orders, SUM(cod_amount) AS cod, SUM(fee_amount) AS fee, SUM(wallet_adjusted) AS adj, SUM(net_payable) AS net')
                ->groupBy('due_on')->orderBy('due_on')->limit(10)->get();

            if ($f['tab'] === 'payouts') {
                $payouts = DB::table('cod_payouts')->where('seller_id', $sid)
                    ->when($f['from'], fn ($q) => $q->where('paid_on', '>=', $f['from']))
                    ->when($f['to'], fn ($q) => $q->where('paid_on', '<=', $f['to']))
                    ->orderByDesc('paid_on')->orderByDesc('id')->paginate(25)->appends(\App\Support\CleanQuery::get());
            } else {
                $rows = $this->rows($sid, $f)->orderByDesc('delivered_on')->orderByDesc('id')->paginate(50)->appends(\App\Support\CleanQuery::get());
            }
            $balance = Kit::balance($sid);
        }

        return view('cod.seller', compact('ready', 'f', 'plans', 'plan', 'sum', 'next', 'rows', 'payouts', 'schedule', 'balance'));
    }

    public function plan(Request $request)
    {
        $seller = $this->seller();
        $data = $request->validate(['plan' => 'required|in:d7,d4,d2']);
        $plans = Cod::plans();
        Cod::setPlan((int) $seller->id, $data['plan']);
        $p = $plans[$data['plan']];
        $msg = $data['plan'] === 'd7'
            ? 'Back on D+7 standard remittance. No deduction on COD delivered from today.'
            : $p['label'] . ' is active. COD delivered from today is payable ' . $p['days'] . ' days after delivery, with ' . Cod::pct($p['pct']) . ' deducted.';
        return back()->with('success', $msg . ' COD already generated keeps its original date and deduction.');
    }

    public function export(Request $request)
    {
        $seller = $this->seller();
        abort_unless(Kit::ready('cod_remittances'), 404);
        $f = $this->filters($request);
        if ($f['tab'] === 'payouts') {
            $f['tab'] = 'paid';
        }
        $today = now()->toDateString();
        $rows = $this->rows((int) $seller->id, $f)->orderByDesc('delivered_on')->limit(20000)->get();
        $payouts = DB::table('cod_payouts')->whereIn('id', $rows->pluck('payout_id')->filter()->unique())->get()->keyBy('id');
        $plans = Cod::plans();

        return Kit::csv('cod-remittance-' . now()->format('Ymd') . '.csv',
            ['AWB', 'Order', 'Courier', 'Delivered on', 'COD amount', 'Plan', 'Deduction %', 'Deduction', 'Adjusted to wallet', 'Net payable', 'Payable date', 'Status', 'Paid on', 'Reference'],
            $rows->map(function ($r) use ($today, $payouts, $plans) {
                $p = $r->payout_id ? $payouts->get($r->payout_id) : null;
                return [$r->awb, $r->order_number, $r->courier, $r->delivered_on, $r->cod_amount, $plans[$r->plan]['label'] ?? $r->plan,
                    $r->fee_pct, $r->fee_amount, $r->wallet_adjusted, $r->net_payable, $r->due_on,
                    Cod::STATUS[Cod::state($r, $today)][0], $p->paid_on ?? '', $p->reference ?? ''];
            }));
    }
}
