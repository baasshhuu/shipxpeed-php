@extends('sellerdashboard.vas._layout')

@section('vas')
@php
    $on = $brand && (int) $brand->active === 1;
    $color = $brand->color ?? '#4f8cff';
@endphp
<div class="vas-hero">
    <div>
        <h1>Brand <span>Boost</span></h1>
        <p class="vas-lead">Turn every tracking check into a brand moment. Your buyers see your logo, colours, support contacts and an offer banner on the tracking page — not the courier's.</p>
        <div class="vas-dash">Buyers open their tracking page several times per order — <b>put your next sale in front of them</b> with a banner link to your store.</div>
        <div class="vas-price">
            <div class="ic"><i class="ti ti-gift"></i></div>
            <div><span class="amt">Free</span> <span class="per">branded tracking page</span></div>
        </div>
        <p class="vas-note">The same page is linked from your WhatsApp and email alerts.</p>
        <div class="vas-btns">
            @if($previewUrl)<a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="vas-btn vas-btn-ghost"><i class="ti ti-external-link"></i> Preview</a>@endif
            @if($settingsUrl)
                @if($on)
                    <a href="{{ $settingsUrl }}" class="vas-btn vas-btn-go"><i class="ti ti-circle-check"></i> Activated · Edit</a>
                @else
                    <a href="{{ $settingsUrl }}" class="vas-btn vas-btn-go"><i class="ti ti-bolt"></i> Set up</a>
                @endif
            @else
                <span class="vas-banner warn"><i class="ti ti-alert-triangle"></i>Brand Boost is being set up on your account.</span>
            @endif
        </div>
    </div>
    <div class="vas-art">
        <div class="vas-mock" aria-hidden="true">
            <div class="hd" style="background:{{ $color }}"><i class="ti ti-building-store"></i> {{ \Illuminate\Support\Str::limit(($brand->brand_name ?? null) ?: $seller->name, 22) }}</div>
            <div class="bd">
                <div style="font-weight:700;font-size:14px;margin-bottom:6px">Out for delivery</div>
                <div class="row"><span>Order</span><b>#1207</b></div>
                <div class="row"><span>Courier</span><b>Delhivery</b></div>
                <div style="margin-top:10px;padding:9px;border-radius:8px;background:{{ $color }}1f;color:{{ $color }};font-weight:700;text-align:center">{{ \Illuminate\Support\Str::limit(($brand->banner_text ?? null) ?: '10% off your next order →', 40) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="vas-card">
    <h3>What you can brand</h3>
    <div class="vas-feat">
        <div><i class="ti ti-photo"></i><b>Logo &amp; colour</b>Your logo on top and your brand colour throughout.</div>
        <div><i class="ti ti-headset"></i><b>Support contacts</b>Your phone, email and website, so buyers reach you, not the courier.</div>
        <div><i class="ti ti-speakerphone"></i><b>Offer banner</b>A line of text and a link to your store — repeat sales from tracking.</div>
    </div>
</div>
@endsection
