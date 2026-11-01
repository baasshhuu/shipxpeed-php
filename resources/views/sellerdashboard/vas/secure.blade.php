@extends('sellerdashboard.vas._layout')

@section('vas')
@php
    $on = (bool) $svc->protection_on;
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
@endphp
<div class="vas-hero">
    <div>
        <h1>Secure <span>Shipments</span></h1>
        <p class="vas-lead">Protect high-value parcels against loss and damage in transit. If something goes wrong, file a claim with proof and get paid to your wallet.</p>
        <div class="vas-dash">Covers every shipment worth <b>₹{{ number_format($cfg['min_value'], 0) }} or more</b>, up to ₹{{ number_format($cfg['max_cover'], 0) }} per shipment. Claims within {{ $cfg['window'] }} days of shipping.</div>
        <div class="vas-price">
            <div class="ic"><i class="ti ti-shield-check"></i></div>
            <div><span class="amt">{{ $fmt($cfg['pct']) }}%*</span> <span class="per">of order value</span></div>
        </div>
        <p class="vas-note">*Minimum ₹{{ $fmt($cfg['min']) }} per protected shipment, debited from your wallet when booked. Refunded if you cancel the shipment.</p>
        @if(!$action)
            <div class="vas-banner warn"><i class="ti ti-alert-triangle"></i><div>Secure Shipments is being set up on your account. Please check back shortly.</div></div>
        @else
        <form method="POST" action="{{ $action }}" class="vas-btns">
            @csrf
            @if(Route::has('xp.protection'))<a href="{{ route('xp.protection') }}#claim" class="vas-btn vas-btn-ghost">File a claim</a>@endif
            @if($on)
                <button type="button" class="vas-btn vas-btn-go" style="cursor:default"><i class="ti ti-circle-check"></i> Activated</button>
                <button name="on" value="0" class="vas-link" onclick="return confirm('Turn off protection? Shipments already protected stay covered.')">Turn off</button>
            @else
                <button name="on" value="1" class="vas-btn vas-btn-go" @disabled(!$cfg['open'])><i class="ti ti-bolt"></i> Activate</button>
            @endif
        </form>
        @if(!$on && !$cfg['open'])<p class="vas-note" style="margin-top:12px">Secure Shipments is opening soon for new sellers.</p>@endif
        @endif
    </div>
    <div class="vas-art">
        <div class="vas-mock" aria-hidden="true">
            <div class="hd" style="background:#4f46e5"><i class="ti ti-shield-check"></i> Protected shipment</div>
            <div class="bd">
                <div class="row"><span>Order value</span><b>₹8,999</b></div>
                <div class="row"><span>Fee ({{ $fmt($cfg['pct']) }}%)</span><b>₹{{ number_format(max($cfg['min'], 8999 * $cfg['pct'] / 100), 2) }}</b></div>
                <div class="row"><span>Status</span><b style="color:#dc2626">Lost in transit</b></div>
                <div class="row"><span>Claim</span><b style="color:#16a34a">₹8,999 to wallet</b></div>
            </div>
        </div>
    </div>
</div>

<div class="vas-card">
    <div class="vas-stats">
        <div class="vas-stat"><b>{{ $on ? 'On' : 'Off' }}</b><span>Protection status</span></div>
        <div class="vas-stat"><b>{{ (int) ($month->c ?? 0) }}</b><span>Protected this month</span></div>
        <div class="vas-stat"><b>₹{{ number_format((float) ($month->f ?? 0), 2) }}</b><span>Fees this month</span></div>
        <div class="vas-stat"><b>{{ (int) ($claims['pending'] ?? 0) }} / {{ (int) ($claims['approved'] ?? 0) }}</b><span>Claims open / paid</span></div>
    </div>
</div>

<div class="vas-card">
    <h3>What's covered</h3>
    <div class="vas-feat">
        <div><i class="ti ti-package-off"></i><b>Lost in transit</b>Courier loses the parcel or it never reaches the buyer.</div>
        <div><i class="ti ti-box-off"></i><b>Damaged</b>Parcel arrives broken or crushed — add unboxing photos / video.</div>
        <div><i class="ti ti-list-check"></i><b>Missing items</b>Box delivered with items missing — weight proof helps.</div>
    </div>
</div>
@endsection
