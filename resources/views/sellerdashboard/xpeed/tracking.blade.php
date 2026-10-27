@extends('layouts.sellerdash')

@section('content')
@php
    $color = old('color', $brand->color ?? '#4f8cff');
    $name = old('brand_name', $brand->brand_name ?? ($seller->name ?? ''));
@endphp
@include('sellerdashboard.xpeed._style')
<div class="pc-container xp">
<div class="pc-content">
    <div class="xp-head">
        <div>
            <h1><i class="ti ti-map-pin"></i> Branded tracking page</h1>
            <p>Your buyers track their parcel on a page with your logo, colours and support contacts — not ours. Share the link in your order confirmation, Shopify thank-you page or WhatsApp.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="xp-card">
                <form method="POST" action="{{ route('xp.tracking.save') }}" enctype="multipart/form-data" class="row g-2">
                    @csrf
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <h2 class="mb-0">Page settings</h2>
                        <label class="small"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked(!$brand || $brand->active)> Show my branding</label>
                    </div>
                    <div class="col-md-8"><label class="form-label">Brand name</label><input name="brand_name" maxlength="80" class="form-control form-control-sm" value="{{ $name }}" oninput="document.getElementById('pvName').textContent = this.value || 'Your brand'"></div>
                    <div class="col-md-4"><label class="form-label">Brand colour</label><input type="color" name="color" class="form-control form-control-sm form-control-color w-100" value="{{ $color }}" oninput="document.getElementById('pvBar').style.background = this.value; document.querySelectorAll('.pv-dot.on').forEach(d => d.style.background = this.value)"></div>
                    <div class="col-md-8"><label class="form-label">Logo (PNG/JPG/WebP, max 1 MB)</label><input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control form-control-sm"></div>
                    <div class="col-md-4 d-flex align-items-end">
                        @if($brand && $brand->logo_path)<label class="small"><input type="checkbox" name="remove_logo" value="1"> Remove current logo</label>@endif
                    </div>
                    <div class="col-md-6"><label class="form-label">Support phone</label><input name="support_phone" maxlength="20" class="form-control form-control-sm" value="{{ old('support_phone', $brand->support_phone ?? '') }}"></div>
                    <div class="col-md-6"><label class="form-label">Support e-mail</label><input type="email" name="support_email" maxlength="120" class="form-control form-control-sm" value="{{ old('support_email', $brand->support_email ?? '') }}"></div>
                    <div class="col-12"><label class="form-label">Website / store link</label><input type="url" name="website" maxlength="190" class="form-control form-control-sm" placeholder="https://yourstore.com" value="{{ old('website', $brand->website ?? '') }}"></div>
                    <div class="col-md-7"><label class="form-label">Promo banner text (optional)</label><input name="banner_text" maxlength="160" class="form-control form-control-sm" placeholder="Use code WELCOME10 for 10% off your next order" value="{{ old('banner_text', $brand->banner_text ?? '') }}"></div>
                    <div class="col-md-5"><label class="form-label">Banner link</label><input type="url" name="banner_link" maxlength="255" class="form-control form-control-sm" placeholder="https://" value="{{ old('banner_link', $brand->banner_link ?? '') }}"></div>
                    <div class="col-12 mt-2"><button class="btn btn-xp btn-sm" @disabled(!$xpReady)>Save</button></div>
                </form>
            </div>

            <div class="xp-card">
                <h2>Your tracking link</h2>
                <p class="xp-sub">Replace the AWB at the end for each order.</p>
                @php $link = route('xp.track.public', ['awb' => $sample->awb_number ?? 'AWBNUMBER']); @endphp
                <div class="input-group input-group-sm">
                    <input id="xpLink" class="form-control mono" readonly value="{{ $link }}">
                    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('xpLink').value); this.textContent='Copied'">Copy</button>
                    @if($sample)<a class="btn btn-outline-primary" target="_blank" href="{{ $link }}">Open</a>@endif
                </div>
                <div class="small text-muted mt-2">In Shopify, add it to the Shipping confirmation notification (Settings › Notifications) as <span class="mono">{{ url('/t') }}/@{{ fulfillment.tracking_number }}</span>.</div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="xp-card">
                <h2>Preview</h2>
                <div style="border:1px solid rgba(120,130,150,.25);border-radius:12px;overflow:hidden;background:#fff;color:#111">
                    <div id="pvBar" style="background:{{ $color }};padding:14px 16px;color:#fff;display:flex;align-items:center;gap:10px">
                        @if($brand && $brand->logo_path)<img src="{{ asset($brand->logo_path) }}" alt="" style="height:28px;background:#fff;border-radius:6px;padding:2px">@endif
                        <b id="pvName">{{ $name ?: 'Your brand' }}</b>
                    </div>
                    <div style="padding:16px">
                        <div style="font-size:12px;color:#666">Order #12345 · AWB 1234567890</div>
                        <div style="font-size:18px;font-weight:700;margin:4px 0 12px">In transit</div>
                        <div style="display:flex;gap:6px;align-items:center">
                            @foreach(['Placed', 'Shipped', 'Out for delivery', 'Delivered'] as $i => $s)
                                <span class="pv-dot {{ $i < 2 ? 'on' : '' }}" style="width:12px;height:12px;border-radius:50%;background:{{ $i < 2 ? $color : '#ddd' }}"></span>
                                @if($i < 3)<span style="flex:1;height:2px;background:#ddd"></span>@endif
                            @endforeach
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:10px;color:#666;margin-top:4px"><span>Placed</span><span>Shipped</span><span>Out for delivery</span><span>Delivered</span></div>
                        <div style="margin-top:14px;padding:10px;border-radius:8px;background:#f5f7fb;font-size:12px">Latest: Arrived at hub, Delhi</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
