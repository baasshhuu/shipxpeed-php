@extends('layouts.sellerdash')

@section('content')
@include('sellerdashboard.vas._style')
@php
    use App\Support\Cod\CodRemittance as Cod;
    $money = fn ($v) => '₹' . number_format((float) $v, 2);
    $cur = $plans[$plan];
    $tabs = ['all' => 'All COD', 'upcoming' => 'Upcoming', 'due' => 'Due to pay', 'paid' => 'Paid', 'adjusted' => 'Adjusted to wallet', 'payouts' => 'Payouts'];
    $q = fn (array $over) => route('seller.cod', array_filter(array_merge(request()->only('from', 'to', 'by', 'q', 'tab'), $over), fn ($v) => $v !== null && $v !== ''));
@endphp
<style>
    .cod-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px}
    @media(max-width:991px){.cod-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    .cod-k{background:var(--v-raised);border:1px solid var(--v-line);border-radius:12px;padding:14px 16px}
    .cod-k span{display:block;font-size:12px;color:var(--v-fg3);margin-bottom:6px}
    .cod-k b{font-size:22px;color:var(--v-fg);display:block;line-height:1.2}
    .cod-k small{font-size:12px;color:var(--v-fg2)}
    .cod-plans{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
    @media(max-width:767px){.cod-plans{grid-template-columns:1fr}}
    .cod-plan{border:1.5px solid var(--v-line2);border-radius:12px;padding:14px 16px;background:var(--v-bg);display:flex;flex-direction:column;gap:6px}
    .cod-plan.on{border-color:var(--v-ok);background:rgba(34,197,94,.08)}
    .cod-plan h4{margin:0;font-size:17px;font-weight:700;color:var(--v-fg)}
    .cod-plan .fee{font-size:13px;color:var(--v-fg2)}
    .cod-plan .ex{font-size:12px;color:var(--v-fg3)}
    .cod-tabs{display:flex;gap:6px;flex-wrap:wrap;margin:0 0 12px}
    .cod-tabs a{padding:7px 12px;border-radius:8px;font-size:13px;color:var(--v-fg2);text-decoration:none;border:1px solid var(--v-line)}
    .cod-tabs a.on{background:var(--v-blue-bg);color:var(--v-blue);border-color:transparent;font-weight:600}
    .cod-f{display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin-bottom:12px}
    .cod-f label{font-size:12px;color:var(--v-fg3);display:block;margin-bottom:3px}
    .cod-f input,.cod-f select{background:var(--v-bg);color:var(--v-fg);border:1px solid var(--v-line2);border-radius:8px;padding:6px 10px;font-size:13px;height:34px}
    .cod-tbl{width:100%;border-collapse:collapse;font-size:13px}
    .cod-tbl th{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--v-fg3);font-weight:600;padding:9px 10px;border-bottom:1px solid var(--v-line2);white-space:nowrap;text-align:left}
    .cod-tbl td{padding:9px 10px;border-bottom:1px solid var(--v-line);color:var(--v-fg);white-space:nowrap}
    .cod-tbl .r{text-align:right}
    .cod-tbl .neg{color:#f87171}
    .cod-mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px}
    .cod-scroll{overflow-x:auto}
</style>
<div class="pc-container vas">
<div class="pc-content sxd">
    <div class="vas-crumb"><a href="{{ url('/seller/dashboard') }}">Dashboard</a><i class="ti ti-chevron-right"></i><span style="color:var(--v-fg)">COD remittance</span></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if(isset($errors) && $errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="vas-main">
    @unless($ready)
        <div class="vas-banner warn"><i class="ti ti-alert-triangle"></i><div>COD remittance is being set up on your account. Please check back shortly.</div></div>
    @else
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h3 style="margin:0;color:var(--v-fg);font-weight:800">COD remittance</h3>
                <div class="vas-muted small">COD is generated on the day an order is marked delivered and paid to your bank on the payable date of your plan.</div>
            </div>
            <span class="pill {{ $plan === 'd7' ? 'blue' : 'ok' }}" style="font-size:13px">Your plan: {{ $cur['label'] }}{{ $cur['pct'] > 0 ? ' · ' . Cod::pct($cur['pct']) . ' deduction' : ' · no deduction' }}</span>
        </div>

        <div class="cod-grid">
            <div class="cod-k"><span>COD generated</span><b>{{ $money($sum['generated']) }}</b><small>{{ number_format($sum['orders']) }} delivered COD orders</small></div>
            <div class="cod-k"><span>Next payout</span><b>{{ $next ? $money($next->amount) : '—' }}</b><small>{{ $next ? 'on ' . \Carbon\Carbon::parse($next->due_on)->format('d M Y') . ' · ' . $next->orders . ' orders' : 'Nothing pending' }}</small></div>
            <div class="cod-k"><span>Total paid to bank</span><b>{{ $money($sum['paid']) }}</b><small>{{ $money($sum['upcoming'] + $sum['due']) }} still to be paid</small></div>
            <div class="cod-k"><span>Deductions</span><b>{{ $money($sum['fee']) }}</b><small>Early COD fee · {{ $money($sum['adj']) }} adjusted to wallet</small></div>
        </div>

        @if($balance < Cod::MIN_WALLET)
            <div class="vas-banner warn" style="margin-bottom:16px"><i class="ti ti-wallet"></i><div>
                Your wallet is at <b>{{ $money($balance) }}</b>. Due COD is first added to your wallet until it reaches <b>{{ $money(Cod::MIN_WALLET) }}</b>; only the rest is paid to your bank.
            </div></div>
        @endif

        <div class="vas-card">
            <h3 style="margin-top:0">Remittance plan</h3>
            <p class="vas-muted small" style="margin-top:-4px">Switch any time. The new plan applies to COD delivered from the day you switch; COD already generated keeps its date and deduction.</p>
            <form method="POST" action="{{ route('seller.cod.plan') }}">
                @csrf
                <div class="cod-plans">
                    @foreach($plans as $code => $p)
                        <div class="cod-plan {{ $plan === $code ? 'on' : '' }}">
                            <h4>{{ $p['label'] }}</h4>
                            <div class="fee">{{ $p['pct'] > 0 ? Cod::pct($p['pct']) . ' of COD amount deducted' : 'No deduction' }}</div>
                            <div class="ex">Delivered Monday → paid {{ \Carbon\Carbon::parse('next monday')->addDays($p['days'])->format('l') }} · on ₹10,000 you get {{ $money(10000 - 10000 * $p['pct'] / 100) }}</div>
                            <div style="margin-top:6px">
                                @if($plan === $code)
                                    <span class="pill ok"><i class="ti ti-circle-check"></i> Active</span>
                                @else
                                    <button name="plan" value="{{ $code }}" class="vas-btn {{ $code === 'd7' ? 'vas-btn-ghost' : 'vas-btn-go' }} vas-btn-sm"
                                        onclick="return confirm('Switch to {{ $p['label'] }}?{{ $p['pct'] > 0 ? ' ' . Cod::pct($p['pct']) . ' of the COD amount will be deducted on COD delivered from today.' : '' }}')">
                                        {{ $code === 'd7' ? 'Switch to standard' : 'Activate' }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </form>
        </div>

        @if($schedule->isNotEmpty())
        <div class="vas-card">
            <h3 style="margin-top:0">Upcoming payable dates</h3>
            <div class="cod-scroll">
            <table class="cod-tbl">
                <thead><tr><th>Payable date</th><th class="r">Orders</th><th class="r">COD amount</th><th class="r">Deduction</th><th class="r">Adjusted to wallet</th><th class="r">Net payable</th></tr></thead>
                <tbody>
                @foreach($schedule as $s)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($s->due_on)->format('d M Y, D') }} @if($s->due_on <= now()->toDateString())<span class="pill warn">Due</span>@endif</td>
                        <td class="r">{{ $s->orders }}</td>
                        <td class="r">{{ $money($s->cod) }}</td>
                        <td class="r {{ $s->fee > 0 ? 'neg' : '' }}">{{ $s->fee > 0 ? '−' . $money($s->fee) : '—' }}</td>
                        <td class="r {{ $s->adj > 0 ? 'neg' : '' }}">{{ $s->adj > 0 ? '−' . $money($s->adj) : '—' }}</td>
                        <td class="r"><b>{{ $money($s->net) }}</b></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>
        @endif

        <div class="vas-card">
            <div class="cod-tabs">
                @foreach($tabs as $k => $label)
                    <a href="{{ $q(['tab' => $k === 'all' ? null : $k, 'page' => null]) }}" class="{{ $f['tab'] === $k ? 'on' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
            <form class="cod-f" method="GET" action="{{ route('seller.cod') }}">
                @if($f['tab'] !== 'all')<input type="hidden" name="tab" value="{{ $f['tab'] }}">@endif
                @if($f['tab'] !== 'payouts')
                <div><label>Date type</label><select name="by"><option value="delivered" @selected($f['by'] === 'delivered_on')>Delivered date</option><option value="due" @selected($f['by'] === 'due_on')>Payable date</option></select></div>
                @endif
                <div><label>From</label><input type="date" name="from" value="{{ $f['from'] }}"></div>
                <div><label>To</label><input type="date" name="to" value="{{ $f['to'] }}"></div>
                @if($f['tab'] !== 'payouts')
                <div><label>AWB / order</label><input type="text" name="q" value="{{ $f['q'] }}" placeholder="Search"></div>
                @endif
                <button class="vas-btn vas-btn-go vas-btn-sm">Apply</button>
                <a class="vas-link" href="{{ route('seller.cod', $f['tab'] !== 'all' ? ['tab' => $f['tab']] : []) }}">Clear</a>
                <a class="vas-btn vas-btn-ghost vas-btn-sm ms-auto" href="{{ route('seller.cod.export', \App\Support\CleanQuery::get()) }}"><i class="ti ti-download"></i> Download</a>
            </form>

            @if($f['tab'] === 'payouts')
                <div class="cod-scroll">
                <table class="cod-tbl">
                    <thead><tr><th>Paid on</th><th class="r">Orders</th><th class="r">COD amount</th><th class="r">Deduction</th><th class="r">Adjusted to wallet</th><th class="r">Paid to bank</th><th>Bank account</th><th>Reference</th></tr></thead>
                    <tbody>
                    @forelse($payouts as $p)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($p->paid_on)->format('d M Y') }}</td>
                            <td class="r">{{ $p->orders }}</td>
                            <td class="r">{{ $money($p->cod_amount) }}</td>
                            <td class="r {{ $p->fee_amount > 0 ? 'neg' : '' }}">{{ $p->fee_amount > 0 ? '−' . $money($p->fee_amount) : '—' }}</td>
                            <td class="r {{ $p->wallet_adjusted > 0 ? 'neg' : '' }}">{{ $p->wallet_adjusted > 0 ? '−' . $money($p->wallet_adjusted) : '—' }}</td>
                            <td class="r"><b>{{ $money($p->amount) }}</b></td>
                            <td>{{ $p->bank_name ?: '—' }} {{ $p->account_number ? '· XX' . substr($p->account_number, -4) : '' }}</td>
                            <td class="cod-mono">{{ $p->reference ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center vas-muted py-4">No payouts yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                </div>
                <div class="mt-3">{{ $payouts->links() }}</div>
            @else
                <div class="cod-scroll">
                <table class="cod-tbl">
                    <thead><tr><th>AWB / Order</th><th>Courier</th><th>Delivered</th><th class="r">COD amount</th><th>Plan</th><th class="r">Deduction</th><th class="r">Adjusted to wallet</th><th class="r">Net payable</th><th>Payable date</th><th>Status</th></tr></thead>
                    <tbody>
                    @php $today = now()->toDateString(); @endphp
                    @forelse($rows as $r)
                        @php [$sl, $sc] = Cod::STATUS[Cod::state($r, $today)]; @endphp
                        <tr>
                            <td><div class="cod-mono">{{ $r->awb }}</div><div class="vas-muted small">{{ $r->order_number }}</div></td>
                            <td>{{ $r->courier }}</td>
                            <td>{{ \Carbon\Carbon::parse($r->delivered_on)->format('d M Y') }}</td>
                            <td class="r">{{ $money($r->cod_amount) }}</td>
                            <td>D+{{ $r->plan_days }}</td>
                            <td class="r {{ $r->fee_amount > 0 ? 'neg' : '' }}">{{ $r->fee_amount > 0 ? '−' . $money($r->fee_amount) . ' (' . Cod::pct($r->fee_pct) . ')' : '—' }}</td>
                            <td class="r {{ $r->wallet_adjusted > 0 ? 'neg' : '' }}">{{ $r->wallet_adjusted > 0 ? '−' . $money($r->wallet_adjusted) : '—' }}</td>
                            <td class="r"><b>{{ $money($r->net_payable) }}</b></td>
                            <td>{{ \Carbon\Carbon::parse($r->due_on)->format('d M Y') }}</td>
                            <td><span class="pill {{ $sc }}">{{ $sl }}</span>@if($r->paid_at)<div class="vas-muted small">{{ \Carbon\Carbon::parse($r->paid_at)->format('d M Y') }}</div>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center vas-muted py-4">No COD here yet. COD shows up the day a COD order is delivered.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                </div>
                <div class="mt-3">{{ $rows->links() }}</div>
            @endif
        </div>
    @endunless
    </div>
</div>
</div>
@endsection
