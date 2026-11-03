@extends('sellerdashboard.vas._layout')

@section('vas')
@php
    $cur = $plan ?? 'd7';
    $isEarly = $cur !== 'd7';
    $best = collect($plans)->sortBy('days')->first();
    $pct = fn ($p) => rtrim(rtrim(number_format($p, 2), '0'), '.') . '%';
@endphp
<div class="vas-hero">
    <div>
        <h1>Early <span>COD</span></h1>
        <p class="vas-lead">Get your cash-on-delivery money in as little as <b>{{ $best['days'] }} days</b> after delivery instead of waiting for the normal remittance cycle. Keep stock moving and pay suppliers on time.</p>
        <div class="vas-dash">Pick a plan below. D+7 is free. Early plans deduct a small % of the COD amount <b>from the payout</b>; nothing is charged on orders that are not delivered.</div>
        <p style="margin:0 0 14px"><span class="pill {{ $isEarly ? 'ok' : 'blue' }}" style="font-size:13px">Your plan: {{ $plans[$cur]['label'] }}</span></p>
        @if(!$action)
            <div class="vas-banner warn"><i class="ti ti-alert-triangle"></i><div>Early COD is being set up on your account. Please check back shortly.</div></div>
        @else
        <form method="POST" action="{{ $action }}">
            @csrf
            <div class="vas-plans" role="radiogroup" aria-label="COD remittance plan">
                @foreach($plans as $key => $p)
                    <label class="vas-plan">
                        <input type="radio" name="plan" value="{{ $key }}" @checked($cur === $key)>
                        <b>D+{{ $p['days'] }}</b>
                        <span>{{ $p['pct'] > 0 ? $pct($p['pct']) . ' deduction' : 'No deduction' }}</span>
                    </label>
                @endforeach
            </div>
            <div class="vas-btns">
                <button class="vas-btn vas-btn-go"><i class="ti ti-bolt"></i> {{ $isEarly ? 'Change plan' : 'Activate' }}</button>
                @if(Route::has('seller.cod'))<a class="vas-link" href="{{ route('seller.cod') }}">View COD remittance</a>@endif
            </div>
        </form>
        <p class="vas-note" style="margin-top:12px">Activates instantly. Applies to COD delivered from today; COD already generated keeps its date and deduction.</p>
        @endif
    </div>
    <div class="vas-art">
        <div class="vas-mock" aria-hidden="true">
            <div class="hd" style="background:#16a34a"><i class="ti ti-cash"></i> COD remittance</div>
            <div class="bd">
                <div class="row"><span>Delivered</span><b>Mon</b></div>
                <div class="row"><span>Normal cycle</span><b style="color:#9ca3af">Next week</b></div>
                <div class="row"><span>With Early COD (D+2)</span><b style="color:#16a34a">Wednesday</b></div>
                <div class="row"><span>COD amount</span><b>₹10,000</b></div>
                <div class="row"><span>Fee ({{ rtrim(rtrim(number_format($plans['d2']['pct'] ?? 0, 2), '0'), '.') }}%)</span><b>−₹{{ number_format(10000 * ($plans['d2']['pct'] ?? 0) / 100, 0) }}</b></div>
            </div>
        </div>
    </div>
</div>

<div class="vas-card">
    <div class="vas-stats">
        <div class="vas-stat"><b>₹{{ number_format($pendingCod, 0) }}</b><span>COD generated, not yet paid</span></div>
        <div class="vas-stat"><b>₹{{ number_format($dueAmount, 0) }}</b><span>Due now{{ $dueCount ? ' (' . $dueCount . ' orders)' : '' }}</span></div>
        <div class="vas-stat"><b>D+{{ $plans[$cur]['days'] }}</b><span>Current payout cycle</span></div>
    </div>
</div>

<div class="vas-card">
    <h3>How it works</h3>
    <div class="vas-feat">
        <div><i class="ti ti-truck-delivery"></i><b>1. Order delivered</b>Courier collects cash from your buyer.</div>
        <div><i class="ti ti-calendar"></i><b>2. D+7 / D+4 / D+2</b>That many days after delivery, the COD amount is payable to you.</div>
        <div><i class="ti ti-building-bank"></i><b>3. Paid to your bank</b>Minus the plan fee. See every payout under COD remittance.</div>
    </div>
</div>
@endsection
