@extends('sellerdashboard.vas._layout')

@section('vas')
@php
    $status = $svc->early_cod_status;
    $cur = $svc->early_cod_plan;
    $label = ['requested' => ['Request under review', 'warn'], 'active' => ['Active', 'ok'], 'stopped' => ['Stopped', 'muted'], 'rejected' => ['Not approved', 'bad']][$status] ?? null;
    $best = collect($plans)->sortBy('days')->first();
@endphp
<div class="vas-hero">
    <div>
        <h1>Early <span>COD</span></h1>
        <p class="vas-lead">Get your cash-on-delivery money in as little as <b>{{ $best['days'] }} days</b> after delivery instead of waiting for the normal remittance cycle. Keep stock moving and pay suppliers on time.</p>
        <div class="vas-dash">Pick a plan below. A small fee on the COD amount remitted early is <b>deducted from the payout</b>; there is no charge on orders that are not delivered.</div>
        @if($label)
            <p style="margin:0 0 14px"><span class="pill {{ $label[1] }}" style="font-size:13px">{{ $label[0] }}{{ $cur ? ' · D+' . ($plans[$cur]['days'] ?? '?') : '' }}</span>
            @if($svc->early_cod_note)<span class="vas-muted small" style="margin-left:8px">{{ $svc->early_cod_note }}</span>@endif</p>
        @endif

        @if(!$action)
            <div class="vas-banner warn"><i class="ti ti-alert-triangle"></i><div>Early COD is being set up on your account. Please check back shortly.</div></div>
        @else
        <form method="POST" action="{{ $action }}">
            @csrf
            <div class="vas-plans" role="radiogroup" aria-label="Early COD plan">
                @foreach($plans as $key => $p)
                    <label class="vas-plan">
                        <input type="radio" name="plan" value="{{ $key }}" @checked(($cur ?: 'd2') === $key) @disabled(!$open && $status !== 'active')>
                        <b>D+{{ $p['days'] }}</b>
                        <span>{{ rtrim(rtrim(number_format($p['pct'], 2), '0'), '.') }}% fee</span>
                    </label>
                @endforeach
            </div>
            <div class="vas-btns">
                @if($status === 'active')
                    <button type="button" class="vas-btn vas-btn-go" style="cursor:default"><i class="ti ti-circle-check"></i> Activated</button>
                    @if($open)<button class="vas-btn vas-btn-ghost">Change plan</button>@endif
                    <button name="plan" value="stop" class="vas-link" onclick="return confirm('Stop Early COD? Your COD goes back to the normal remittance cycle.')">Stop</button>
                @elseif($status === 'requested')
                    <button type="button" class="vas-btn vas-btn-go off" disabled><i class="ti ti-clock"></i> Request sent</button>
                    <button name="plan" value="stop" class="vas-link">Cancel request</button>
                @else
                    <button class="vas-btn vas-btn-go" @disabled(!$open)><i class="ti ti-bolt"></i> Activate</button>
                @endif
            </div>
        </form>
        @if(!$open && $status !== 'active')
            <p class="vas-note" style="margin-top:12px">Early COD is opening soon for new sellers. You'll be able to request it here.</p>
        @endif
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
        <div class="vas-stat"><b>₹{{ number_format($pendingCod, 0) }}</b><span>COD delivered, not yet remitted</span></div>
        <div class="vas-stat"><b>₹{{ number_format($dueAmount, 0) }}</b><span>Due early under your plan{{ $dueCount ? ' (' . $dueCount . ' orders)' : '' }}</span></div>
        <div class="vas-stat"><b>{{ $label[0] ?? 'Off' }}</b><span>Early COD status</span></div>
    </div>
</div>

<div class="vas-card">
    <h3>How it works</h3>
    <div class="vas-feat">
        <div><i class="ti ti-truck-delivery"></i><b>1. Order delivered</b>Courier collects cash from your buyer.</div>
        <div><i class="ti ti-calendar"></i><b>2. D+2 / D+3 / D+4</b>That many days after delivery, the COD amount is due to you.</div>
        <div><i class="ti ti-building-bank"></i><b>3. Paid to your bank</b>Minus the plan fee. See every payout under COD remittance.</div>
    </div>
</div>
@endsection
