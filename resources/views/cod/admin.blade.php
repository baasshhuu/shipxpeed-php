@extends('layouts.app')

@section('content')
@php
    use App\Support\Cod\CodRemittance as Cod;
    $money = fn ($v) => '₹' . number_format((float) $v, 2);
    $d = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y') : '—';
    $tabs = ['due' => 'Due to pay', 'upcoming' => 'Upcoming', 'payouts' => 'Paid (payouts)', 'orders' => 'All COD orders'];
    $base = array_filter(['seller' => $f['seller']]);
    $exportQ = array_filter(['tab' => $f['tab'], 'from' => $f['from'], 'to' => $f['to'], 'seller' => $f['seller'], 'status' => $f['status'], 'by' => $f['by'] === 'delivered_on' ? 'delivered' : null, 'q' => $f['q']]);
@endphp
<style>
    .cod-pill{display:inline-block;padding:2px 9px;border-radius:999px;font-size:12px;font-weight:600}
    .cod-ok{background:var(--sx-success-soft,#e6f6ec);color:var(--sx-success,#137333)}
    .cod-warn{background:var(--sx-warning-soft,#fff4e5);color:var(--sx-warning,#8a5300)}
    .cod-blue{background:rgba(59,123,255,.14);color:#3b7bff}
    .cod-muted{background:rgba(120,130,150,.15);color:var(--bs-secondary-color,#6c757d)}
    .cod-bad{background:var(--sx-danger-soft,#fdecea);color:var(--sx-danger,#b3261e)}
    .cod-mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px}
    .cod-t td,.cod-t th{vertical-align:middle;font-size:13px;white-space:nowrap}
    .cod-t .r{text-align:right}
    .cod-neg{color:#dc3545}
    .cod-bank{font-size:12px;line-height:1.35}
    .cod-sticky{position:sticky;bottom:0;z-index:5;background:var(--bs-body-bg,#fff);border-top:1px solid var(--bs-border-color,#dee2e6)}
</style>

<div class="sx-page-head">
    <div>
        <h2>COD remittance</h2>
        <p>Delivered COD becomes payable on its plan date (D+7 standard, D+4 / D+2 early). Low wallets are topped up to ₹{{ (int) Cod::MIN_WALLET }} from due COD first.</p>
    </div>
    <div class="d-flex gap-2">
        <form method="POST" action="{{ route('codremittance.refresh') }}">@csrf<button class="btn btn-outline-secondary"><i class="fa-solid fa-rotate me-1"></i>Refresh</button></form>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

@unless($ready)
    <div class="alert alert-warning">COD tables are missing. Run the COD installer on the server.</div>
@else

<div class="row g-3 mb-3">
    @foreach([
        ['Due to pay now', $money($kpi['due']), $kpi['due_sellers'] . ' sellers · ' . $kpi['due_orders'] . ' orders', 'fa-solid fa-indian-rupee-sign', 'amber'],
        ['Upcoming (next 7 days)', $money($kpi['up7']), $kpi['up7_sellers'] . ' sellers', 'fa-solid fa-calendar-days', 'blue'],
        ['Paid this month', $money($kpi['paid_month']), $kpi['paid_month_n'] . ' payouts', 'fa-solid fa-circle-check', 'green'],
        ['Early COD fee earned', $money($kpi['fee']), $money($kpi['fee_month']) . ' this month · ' . $money($kpi['adj']) . ' to wallets', 'fa-solid fa-bolt', 'red'],
    ] as [$l, $v, $s, $i, $t])
        <div class="col-6 col-lg-3">
            <div class="sx-kpi">
                <div class="sx-kpi-top"><span class="sx-kpi-label">{{ $l }}</span><span class="sx-kpi-icon sx-tone-{{ $t }}"><i class="{{ $i }}"></i></span></div>
                <div class="sx-kpi-value">{{ $v }}</div>
                <div class="sx-kpi-sub">{{ $s }}</div>
            </div>
        </div>
    @endforeach
</div>

<ul class="nav nav-tabs mb-3">
    @foreach($tabs as $k => $label)
        <li class="nav-item"><a class="nav-link {{ $f['tab'] === $k ? 'active' : '' }}" href="{{ route('codremittance.index', $base + ['tab' => $k]) }}">{{ $label }}</a></li>
    @endforeach
</ul>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('codremittance.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="{{ $f['tab'] }}">
            <div class="col-md-3">
                <label class="form-label small mb-1">Seller</label>
                <select name="seller" class="form-select form-select-sm">
                    <option value="">All sellers</option>
                    @foreach($sellers as $id => $n)<option value="{{ $id }}" @selected($f['seller'] === (int) $id)>{{ $n }} (#{{ $id }})</option>@endforeach
                </select>
            </div>
            @if($f['tab'] === 'orders')
                <div class="col-md-2"><label class="form-label small mb-1">Date type</label>
                    <select name="by" class="form-select form-select-sm"><option value="due" @selected($f['by'] === 'due_on')>Payable date</option><option value="delivered" @selected($f['by'] === 'delivered_on')>Delivered date</option></select></div>
            @endif
            <div class="col-md-2"><label class="form-label small mb-1">{{ $f['tab'] === 'payouts' ? 'Paid from' : ($f['tab'] === 'orders' ? 'From' : 'Payable from') }}</label><input type="date" name="from" value="{{ $f['from'] }}" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label small mb-1">{{ $f['tab'] === 'payouts' ? 'Paid to' : ($f['tab'] === 'orders' ? 'To' : 'Payable up to') }}</label><input type="date" name="to" value="{{ $f['to'] }}" class="form-control form-control-sm" @if($f['tab'] === 'due') max="{{ now()->toDateString() }}" @endif></div>
            @if($f['tab'] === 'orders')
                <div class="col-md-2"><label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm"><option value="">All</option>@foreach(Cod::STATUS as $k => [$l]) <option value="{{ $k }}" @selected($f['status'] === $k)>{{ $l }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small mb-1">AWB / order</label><input name="q" value="{{ $f['q'] }}" class="form-control form-control-sm"></div>
            @endif
            <div class="col-auto"><button class="btn btn-sm btn-primary">Apply</button> <a href="{{ route('codremittance.index', ['tab' => $f['tab']]) }}" class="btn btn-sm btn-link">Clear</a></div>
            <div class="col-auto ms-auto d-flex gap-2">
                @if(in_array($f['tab'], ['due', 'upcoming'], true))
                    <a class="btn btn-sm btn-success" href="{{ route('codremittance.export', $exportQ + ['type' => 'payout']) }}"><i class="fa-solid fa-file-excel me-1"></i>Payout Excel (bank)</a>
                @endif
                <a class="btn btn-sm btn-outline-success" href="{{ route('codremittance.export', $exportQ + ['type' => $f['tab'] === 'payouts' ? 'payouts' : 'orders']) }}"><i class="fa-solid fa-file-excel me-1"></i>{{ $f['tab'] === 'payouts' ? 'Payouts Excel' : 'Order-wise Excel' }}</a>
            </div>
        </form>
    </div>
</div>

@if(in_array($f['tab'], ['due', 'upcoming'], true))
    @if($byDate->isNotEmpty())
    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">By payable date</h5></div>
        <div class="card-body p-0"><div class="table-responsive">
            <table class="table table-sm table-hover mb-0 cod-t">
                <thead><tr><th>Payable date</th><th class="r">Sellers</th><th class="r">Orders</th><th class="r">COD amount</th><th class="r">Deduction</th><th class="r">Adjusted to wallet</th><th class="r">Net payable</th></tr></thead>
                <tbody>
                @foreach($byDate as $r)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($r->due_on)->format('d M Y, D') }}</td>
                        <td class="r">{{ $r->sellers }}</td><td class="r">{{ $r->orders }}</td>
                        <td class="r">{{ $money($r->cod) }}</td>
                        <td class="r {{ $r->fee > 0 ? 'cod-neg' : '' }}">{{ $r->fee > 0 ? '−' . $money($r->fee) : '—' }}</td>
                        <td class="r {{ $r->adj > 0 ? 'cod-neg' : '' }}">{{ $r->adj > 0 ? '−' . $money($r->adj) : '—' }}</td>
                        <td class="r fw-semibold">{{ $money($r->net) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div></div>
    </div>
    @endif

    <form method="POST" action="{{ route('codremittance.markPaid') }}" id="codPayForm">
        @csrf
        <input type="hidden" name="up_to" value="{{ $f['to'] }}">
        @if($f['from'])<input type="hidden" name="from" value="{{ $f['from'] }}">@endif
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $f['tab'] === 'due' ? 'Payable to sellers' : 'Upcoming by seller' }} <small class="text-muted fw-normal">· payable {{ $f['from'] ? $d($f['from']) . ' – ' : 'up to ' }}{{ $d($f['to']) }}</small></h5>
                <span class="small text-muted">{{ count($lines) }} sellers · {{ $money(array_sum(array_column($lines, 'net'))) }}</span>
            </div>
            <div class="card-body p-0"><div class="table-responsive">
                <table class="table table-hover mb-0 cod-t">
                    <thead><tr>
                        @if($f['tab'] === 'due')<th style="width:34px"><input type="checkbox" class="form-check-input" id="codAll"></th>@endif
                        <th>Seller</th><th>Bank details</th><th class="r">Orders</th><th class="r">COD amount</th><th class="r">Early COD deduction</th><th class="r">Adjusted to wallet</th><th class="r">Net payable</th><th>Payable dates</th><th></th>
                    </tr></thead>
                    <tbody>
                    @forelse($lines as $l)
                        @php $b = $l['bank'] ?? []; $missing = empty($b['account']) || empty($b['ifsc']); @endphp
                        <tr>
                            @if($f['tab'] === 'due')<td><input type="checkbox" class="form-check-input cod-chk" name="sellers[]" value="{{ $l['seller_id'] }}" data-net="{{ $l['net'] }}"></td>@endif
                            <td><div class="fw-semibold">{{ $b['name'] ?? '#' . $l['seller_id'] }}</div><div class="small text-muted">#{{ $l['seller_id'] }} · {{ $b['email'] ?? '' }}</div></td>
                            <td class="cod-bank">
                                @if($missing)
                                    <span class="cod-pill cod-bad">Bank details missing</span>
                                @else
                                    <div class="fw-semibold">{{ $b['holder'] }}</div>
                                    <div>{{ $b['bank'] ?: '—' }}</div>
                                    <div class="cod-mono">{{ $b['account'] }} · {{ $b['ifsc'] }}</div>
                                    @unless($b['verified'])<span class="cod-pill cod-warn">Not verified</span>@endunless
                                @endif
                            </td>
                            <td class="r">{{ $l['orders'] }}</td>
                            <td class="r">{{ $money($l['cod']) }}</td>
                            <td class="r {{ $l['fee'] > 0 ? 'cod-neg' : '' }}">{{ $l['fee'] > 0 ? '−' . $money($l['fee']) : '—' }}</td>
                            <td class="r {{ $l['adj'] > 0 ? 'cod-neg' : '' }}">{{ $l['adj'] > 0 ? '−' . $money($l['adj']) : '—' }}</td>
                            <td class="r fw-bold">{{ $money($l['net']) }}</td>
                            <td class="small">{{ $d($l['first_due']) }}{{ $l['first_due'] !== $l['last_due'] ? ' – ' . $d($l['last_due']) : '' }}</td>
                            <td><a class="btn btn-sm btn-outline-secondary py-0" href="{{ route('codremittance.seller', ['sellerId' => $l['seller_id'], 'status' => $f['tab'] === 'due' ? 'due' : 'upcoming']) }}">Orders</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">{{ $f['tab'] === 'due' ? 'Nothing payable for these dates.' : 'No upcoming COD for these dates.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div></div>

            @if($f['tab'] === 'due' && count($lines))
            <div class="card-footer cod-sticky">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2"><label class="form-label small mb-1">Paid on</label><input type="date" name="paid_on" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" class="form-control form-control-sm" required></div>
                    <div class="col-md-3"><label class="form-label small mb-1">UTR / reference (optional)</label><input name="reference" maxlength="120" class="form-control form-control-sm" placeholder="e.g. bank batch no."></div>
                    <div class="col-md-auto small text-muted pb-1">Selected: <b id="codSelN">0</b> · <b id="codSelAmt">₹0.00</b></div>
                    <div class="col-md-auto ms-auto d-flex gap-2">
                        <button type="submit" formaction="{{ route('codremittance.export', $exportQ + ['type' => 'payout']) }}" formmethod="GET" class="btn btn-sm btn-outline-success" id="codExpSel" disabled>Excel of selected</button>
                        <button name="scope" value="selected" class="btn btn-sm btn-primary" id="codPaySel" disabled
                            onclick="return confirm('Mark the selected sellers as PAID and email them? Do this only after the bank transfer is done.')">Mark selected paid</button>
                        <button name="scope" value="all" class="btn btn-sm btn-success"
                            onclick="return confirm('Mark ALL {{ count($lines) }} sellers ({{ $money(array_sum(array_column($lines, 'net'))) }}) as PAID and email them? Do this only after the bank transfers are done.')">Mark all paid</button>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </form>

@elseif($f['tab'] === 'payouts')
    <div class="card">
        <div class="card-body p-0"><div class="table-responsive">
            <table class="table table-hover mb-0 cod-t">
                <thead><tr><th>Paid on</th><th>Seller</th><th>Paid to</th><th class="r">Orders</th><th class="r">COD amount</th><th class="r">Deduction</th><th class="r">Adjusted to wallet</th><th class="r">Paid</th><th>Reference</th><th>Email</th><th></th></tr></thead>
                <tbody>
                @php $names = $payouts ? \Illuminate\Support\Facades\DB::table('seller_lists')->whereIn('id', collect($payouts->items())->pluck('seller_id'))->pluck('name', 'id') : collect(); @endphp
                @forelse($payouts as $p)
                    <tr>
                        <td>{{ $d($p->paid_on) }}</td>
                        <td><div class="fw-semibold">{{ $names[$p->seller_id] ?? '#' . $p->seller_id }}</div><div class="small text-muted">#{{ $p->seller_id }}</div></td>
                        <td class="cod-bank">{{ $p->account_holder }}<div class="cod-mono">{{ $p->account_number }} · {{ $p->ifsc }}</div></td>
                        <td class="r">{{ $p->orders }}</td>
                        <td class="r">{{ $money($p->cod_amount) }}</td>
                        <td class="r {{ $p->fee_amount > 0 ? 'cod-neg' : '' }}">{{ $p->fee_amount > 0 ? '−' . $money($p->fee_amount) : '—' }}</td>
                        <td class="r {{ $p->wallet_adjusted > 0 ? 'cod-neg' : '' }}">{{ $p->wallet_adjusted > 0 ? '−' . $money($p->wallet_adjusted) : '—' }}</td>
                        <td class="r fw-bold">{{ $money($p->amount) }}</td>
                        <td class="cod-mono">{{ $p->reference ?: '—' }}</td>
                        <td>@if($p->mailed_at)<span class="cod-pill cod-ok">Sent</span>@else<span class="cod-pill cod-bad" title="{{ $p->mail_error }}">Not sent</span>@endif</td>
                        <td class="d-flex gap-1">
                            <a class="btn btn-sm btn-outline-secondary py-0" href="{{ route('codremittance.seller', ['sellerId' => $p->seller_id, 'status' => 'paid']) }}">Orders</a>
                            <form method="POST" action="{{ route('codremittance.resend', $p->id) }}">@csrf<button class="btn btn-sm btn-link py-0">Resend email</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">No payouts yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div></div>
    </div>
    <div class="mt-3">{{ $payouts->links() }}</div>

@else
    <div class="card">
        <div class="card-body p-0"><div class="table-responsive">
            <table class="table table-sm table-hover mb-0 cod-t">
                <thead><tr><th>Seller</th><th>AWB / Order</th><th>Courier</th><th>Delivered</th><th class="r">COD amount</th><th>Plan</th><th class="r">Deduction</th><th class="r">Adjusted to wallet</th><th class="r">Net payable</th><th>Payable date</th><th>Status</th></tr></thead>
                <tbody>
                @php $today = now()->toDateString(); @endphp
                @forelse($orders as $r)
                    @php [$sl, $sc] = Cod::STATUS[Cod::state($r, $today)]; @endphp
                    <tr>
                        <td>{{ $sellers[$r->seller_id] ?? '#' . $r->seller_id }}</td>
                        <td><div class="cod-mono">{{ $r->awb }}</div><div class="small text-muted">{{ $r->order_number }}</div></td>
                        <td>{{ $r->courier }}</td>
                        <td>{{ $d($r->delivered_on) }}</td>
                        <td class="r">{{ $money($r->cod_amount) }}</td>
                        <td>D+{{ $r->plan_days }}</td>
                        <td class="r {{ $r->fee_amount > 0 ? 'cod-neg' : '' }}">{{ $r->fee_amount > 0 ? '−' . $money($r->fee_amount) . ' (' . Cod::pct($r->fee_pct) . ')' : '—' }}</td>
                        <td class="r {{ $r->wallet_adjusted > 0 ? 'cod-neg' : '' }}">{{ $r->wallet_adjusted > 0 ? '−' . $money($r->wallet_adjusted) : '—' }}</td>
                        <td class="r fw-semibold">{{ $money($r->net_payable) }}</td>
                        <td>{{ $d($r->due_on) }}</td>
                        <td><span class="cod-pill cod-{{ $sc }}">{{ $sl }}</span>@if($r->note)<div class="small text-muted">{{ $r->note }}</div>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">No COD orders for these filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div></div>
    </div>
    <div class="mt-3">{{ $orders->links() }}</div>
@endif

@endunless

<script>
(function () {
    var all = document.getElementById('codAll');
    var boxes = document.querySelectorAll('.cod-chk');
    var n = document.getElementById('codSelN'), amt = document.getElementById('codSelAmt');
    var paySel = document.getElementById('codPaySel'), expSel = document.getElementById('codExpSel');
    function upd() {
        var c = 0, s = 0;
        boxes.forEach(function (b) { if (b.checked) { c++; s += parseFloat(b.dataset.net || 0); } });
        if (n) n.textContent = c;
        if (amt) amt.textContent = '₹' + s.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (paySel) paySel.disabled = c === 0;
        if (expSel) expSel.disabled = c === 0;
        if (all) all.checked = c > 0 && c === boxes.length;
    }
    if (all) all.addEventListener('change', function () { boxes.forEach(function (b) { b.checked = all.checked; }); upd(); });
    boxes.forEach(function (b) { b.addEventListener('change', upd); });
    upd();
})();
</script>
@endsection
