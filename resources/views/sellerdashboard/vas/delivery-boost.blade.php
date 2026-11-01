@extends('sellerdashboard.vas._layout')

@section('vas')
<div class="vas-hero">
    <div>
        <h1>Delivery <span>Boost</span></h1>
        <p class="vas-lead">Save failed deliveries before they turn into returns. When a delivery fails, your buyer gets a WhatsApp message with a link to pick a new date, fix the address or phone — and we pass it to the courier.</p>
        <div class="vas-dash">Buyers answer on WhatsApp in minutes, so the courier <b>re-attempts with the right details</b> instead of sending the parcel back.</div>
        <div class="vas-price">
            <div class="ic"><i class="ti ti-currency-rupee"></i></div>
            <div><span class="amt">{{ $ndrRate > 0 ? '₹' . number_format($ndrRate, 2) : 'Free' }}*</span> <span class="per">per failed delivery</span></div>
        </div>
        <p class="vas-note">*Uses your WhatsApp alerts. Charged only when the message is sent.</p>
        @if(!$waReady || !Route::has('seller.whatsapp.alerts.update'))
            <div class="vas-banner warn"><i class="ti ti-alert-triangle"></i><div>WhatsApp alerts are being set up on your account. Please check back shortly.</div></div>
        @else
        <form method="POST" action="{{ route('seller.whatsapp.alerts.update') }}" class="vas-btns">
            @csrf
            <input type="hidden" name="brand_name" value="{{ $brandName }}">
            @if(Route::has('xp.ndr'))<a href="{{ route('xp.ndr') }}" class="vas-btn vas-btn-ghost">Open NDR</a>@endif
            @if($waActive)
                <button type="button" class="vas-btn vas-btn-go" style="cursor:default"><i class="ti ti-circle-check"></i> Activated</button>
                <button name="active" value="0" class="vas-link" onclick="return confirm('Turn off WhatsApp alerts? Buyers will stop getting delivery updates and NDR links.')">Turn off</button>
            @else
                <button name="active" value="1" class="vas-btn vas-btn-go"><i class="ti ti-bolt"></i> Activate</button>
            @endif
        </form>
        @endif
    </div>
    <div class="vas-art">
        <div class="vas-mock" aria-hidden="true">
            <div class="hd" style="background:#075e54"><i class="ti ti-brand-whatsapp"></i> {{ \Illuminate\Support\Str::limit($brandName ?: $seller->name, 22) }}</div>
            <div class="bd" style="background:#e9e3da">
                <div style="background:#fff;border-radius:0 10px 10px 10px;padding:9px 10px">⚠️ We couldn't deliver order <b>#1207</b> today.<br>Choose what to do:
                    <div style="margin-top:8px;display:grid;gap:5px;text-align:center;color:#0a7cff;font-weight:600">
                        <span style="border-top:1px solid #eee;padding-top:5px">Re-attempt tomorrow</span><span>Change address</span><span>Change phone</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="vas-card">
    <div class="vas-stats">
        <div class="vas-stat"><b>{{ $waActive ? 'On' : 'Off' }}</b><span>WhatsApp alerts</span></div>
        <div class="vas-stat"><b>{{ $ndrOpen }}</b><span>Failed deliveries, 30 days</span></div>
        <div class="vas-stat"><b>{{ $sentLinks }}</b><span>NDR links sent to buyers</span></div>
        <div class="vas-stat"><b>{{ $answered }}</b><span>Buyers who answered</span></div>
    </div>
</div>

<div class="vas-card">
    <h3>What happens on a failed delivery</h3>
    <div class="vas-feat">
        <div><i class="ti ti-alert-triangle"></i><b>Courier marks NDR</b>Buyer not reachable, address issue, refused…</div>
        <div><i class="ti ti-brand-whatsapp"></i><b>Buyer gets WhatsApp</b>With a secure link: re-attempt date, new address or phone, or return.</div>
        <div><i class="ti ti-truck-return"></i><b>Sent to the courier</b>Automatically where the courier's NDR API allows; otherwise our ops team actions it.</div>
    </div>
</div>
@endsection
