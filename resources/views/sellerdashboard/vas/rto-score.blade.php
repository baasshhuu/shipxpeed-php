@extends('sellerdashboard.vas._layout')

@section('vas')
@php
    $lvl = ['high' => ['High risk', 'bad', '#ef4444'], 'medium' => ['Medium', 'warn', '#f59e0b'], 'low' => ['Low', 'ok', '#22c55e']];
@endphp
<div class="vas-hero">
    <div>
        <h1>RTO <span>Score</span></h1>
        <p class="vas-lead">Know which orders are likely to come back <b>before</b> you ship them. Every unshipped order gets a risk score, so you can call the buyer, ask for prepaid, or cancel.</p>
        <div class="vas-dash">Scored from your buyer's <b>past deliveries and returns</b>, the delivery pincode's return rate, address quality and order value.</div>
        <div class="vas-price">
            <div class="ic"><i class="ti ti-gift"></i></div>
            <div><span class="amt">Free</span> <span class="per">for every order</span></div>
        </div>
        <p class="vas-note">Always on for your account. Scores refresh every few hours.</p>
        <div class="vas-btns">
            <a href="#rtoList" class="vas-btn vas-btn-go"><i class="ti ti-circle-check"></i> Activated</a>
            <a href="{{ route('vas.show', ['rto-score', 'cod' => $codOnly ? '0' : '1']) }}#rtoList" class="vas-btn vas-btn-ghost">{{ $codOnly ? 'Show all orders' : 'COD orders only' }}</a>
        </div>
    </div>
    <div class="vas-art">
        <div class="vas-mock" aria-hidden="true">
            <div class="hd" style="background:#ef4444"><i class="ti ti-shield-x"></i> RTO Score · 78 / 100</div>
            <div class="bd">
                <div class="row"><span>Buyer returned before</span><b>2 of 3</b></div>
                <div class="row"><span>Pincode return rate</span><b>31%</b></div>
                <div class="row"><span>Address</span><b>Too short</b></div>
                <div class="row"><span>Payment</span><b>COD ₹2,499</b></div>
                <div style="margin-top:10px;padding:8px 10px;border-radius:8px;background:#fef2f2;color:#b91c1c;font-weight:600">Call buyer or ask for prepaid</div>
            </div>
        </div>
    </div>
</div>

<div class="vas-card">
    <div class="vas-stats">
        <div class="vas-stat"><b style="color:#f87171">{{ $counts['high'] }}</b><span>High risk to check</span></div>
        <div class="vas-stat"><b style="color:#fbbf24">{{ $counts['medium'] }}</b><span>Medium risk</span></div>
        <div class="vas-stat"><b style="color:#4ade80">{{ $counts['low'] }}</b><span>Low risk</span></div>
        <div class="vas-stat"><b>{{ $rtoRate !== null ? $rtoRate . '%' : '—' }}</b><span>Your RTO rate, 90 days{{ $closed90 ? ' (' . $closed90 . ' closed)' : '' }}</span></div>
    </div>
</div>

<div class="vas-card" id="rtoList">
    <h3>Unshipped {{ $codOnly ? 'COD ' : '' }}orders · last 30 days</h3>
    <p class="sub">Highest risk first. Hover a reason to read it. Ship low-risk orders as usual; confirm high-risk COD orders with the buyer first.</p>
    @if($rows->isEmpty())
        <p class="vas-muted mb-0"><i class="ti ti-shield-check"></i> No unshipped {{ $codOnly ? 'COD ' : '' }}orders to check right now.</p>
    @else
    <div class="table-responsive">
    <table class="vas-tbl">
        <thead><tr><th>Order</th><th>Buyer</th><th>Payment</th><th>Risk</th><th>Why</th><th></th></tr></thead>
        <tbody>
        @foreach($rows as $r)
            @php $o = $r['order']; $k = \App\Support\Xpeed\Kit::consignee($o); [$ll, $lc, $col] = $lvl[$r['risk']['level']] ?? $lvl['low']; @endphp
            <tr>
                <td><b>{{ \App\Support\Xpeed\Kit::ref($o) }}</b><div class="vas-muted small">{{ optional($o->created_at)->format('d M, h:i A') }}</div></td>
                <td>{{ $k['name'] ?? '—' }}<div class="vas-muted small">{{ $k['city'] ?? '' }} {{ $k['pincode'] ?? '' }}</div></td>
                <td>{{ strtoupper((string) $o->payment_type) }}<div class="vas-muted small">₹{{ number_format((float) $o->order_amount, 0) }}</div></td>
                <td style="white-space:nowrap"><span class="vas-meter"><i style="width:{{ min(100, $r['risk']['score']) }}%;background:{{ $col }}"></i></span><span class="pill {{ $lc }}">{{ $ll }} · {{ $r['risk']['score'] }}</span></td>
                <td class="small">{{ implode(' · ', array_slice($r['risk']['reasons'], 0, 3)) ?: '—' }}</td>
                <td><a class="vas-btn vas-btn-ghost vas-btn-sm" href="{{ url('/order/edit/' . $o->id) }}">Open</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    @endif
</div>
@endsection
