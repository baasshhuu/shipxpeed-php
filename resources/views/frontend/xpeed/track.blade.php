@php
    $color = $brand->color ?? '#2f6fed';
    $name = $brand->brand_name ?? null;
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>{{ $notFound ? 'Shipment not found' : 'Track ' . $awb }}{{ $name ? ' · ' . $name : '' }}</title>
<style>
    :root { --c: {{ $color }}; }
    * { box-sizing:border-box; }
    body { margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; background:#f3f5f9; color:#1b2230; }
    .top { background:var(--c); color:#fff; }
    .wrap { max-width:680px; margin:0 auto; padding:0 16px; }
    .top .wrap { display:flex; align-items:center; gap:12px; min-height:64px; }
    .top img { height:36px; max-width:140px; object-fit:contain; background:#fff; border-radius:8px; padding:3px; }
    .top b { font-size:18px; }
    .card { background:#fff; border-radius:14px; padding:20px; margin:16px 0; box-shadow:0 1px 3px rgba(20,30,50,.08); }
    .muted { color:#6b7487; font-size:13px; }
    .status { font-size:24px; font-weight:800; margin:6px 0 2px; }
    .edd { font-size:14px; color:#38404f; }
    .steps { display:flex; align-items:center; margin:22px 0 6px; }
    .dot { width:16px; height:16px; border-radius:50%; background:#d9dee8; flex:none; }
    .dot.on { background:var(--c); box-shadow:0 0 0 4px color-mix(in srgb, var(--c) 20%, transparent); }
    .line { flex:1; height:3px; background:#d9dee8; } .line.on { background:var(--c); }
    .labels { display:flex; justify-content:space-between; font-size:11px; color:#6b7487; }
    .labels span { width:25%; text-align:center; } .labels span:first-child { text-align:left; } .labels span:last-child { text-align:right; }
    .alert { background:#fff4e5; color:#7a4a00; padding:10px 12px; border-radius:10px; font-size:14px; margin-top:12px; }
    .tl { list-style:none; margin:0; padding:0; }
    .tl li { position:relative; padding:0 0 16px 22px; border-left:2px solid #e3e7ef; margin-left:6px; }
    .tl li:last-child { border-left-color:transparent; padding-bottom:0; }
    .tl li::before { content:''; position:absolute; left:-7px; top:2px; width:12px; height:12px; border-radius:50%; background:#cfd5e1; }
    .tl li:first-child::before { background:var(--c); }
    .tl .m { font-weight:600; font-size:14px; } .tl .w { font-size:12px; color:#6b7487; }
    .row { display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:14px; }
    .row b { display:block; font-size:11px; color:#6b7487; font-weight:600; text-transform:uppercase; letter-spacing:.04em; }
    .banner { display:block; background:color-mix(in srgb, var(--c) 12%, #fff); color:#1b2230; border:1px dashed var(--c); border-radius:12px; padding:14px; text-decoration:none; font-weight:600; text-align:center; }
    .help a { color:var(--c); font-weight:600; text-decoration:none; margin-right:14px; }
    form.s { display:flex; gap:8px; } form.s input { flex:1; padding:11px 12px; border:1px solid #cfd5e1; border-radius:10px; font-size:15px; }
    form.s button, .btn { background:var(--c); color:#fff; border:0; border-radius:10px; padding:11px 16px; font-weight:700; cursor:pointer; text-decoration:none; }
    .foot { text-align:center; font-size:11px; color:#98a0b0; padding:18px 0 30px; }
</style>
</head>
<body>
<div class="top"><div class="wrap">
    @if($brand && $brand->logo_path)<img src="{{ asset($brand->logo_path) }}" alt="{{ $name }}">@endif
    <b>{{ $name ?: 'Shipment tracking' }}</b>
</div></div>

<div class="wrap">
@if($notFound)
    <div class="card">
        <div class="status">We couldn't find this shipment</div>
        <p class="muted">Check the tracking number <b>{{ $awb }}</b> and try again. New shipments can take a few hours to appear.</p>
        <form class="s" onsubmit="location.href='{{ url('/t') }}/' + encodeURIComponent(this.a.value.trim()); return false;">
            <input name="a" placeholder="Tracking number" value="{{ $awb }}"><button>Track</button>
        </form>
    </div>
@else
    <div class="card">
        <div class="muted">Order #{{ $ref }} · {{ $track['courier'] }} · AWB {{ $awb }}</div>
        <div class="status">{{ $track['status'] }}</div>
        @if($track['edd'] && !in_array($track['bucket'], ['delivered', 'rto', 'cancelled']))<div class="edd">Expected delivery: <b>{{ $track['edd'] }}</b></div>@endif
        @unless(in_array($track['bucket'], ['rto', 'cancelled']))
            @php $steps = \App\Support\Xpeed\Tracking::steps($track['bucket']); @endphp
            <div class="steps">
                @foreach($steps as $i => $s)
                    <span class="dot {{ $s['done'] ? 'on' : '' }}"></span>
                    @if($i < count($steps) - 1)<span class="line {{ ($steps[$i + 1]['done'] ?? false) ? 'on' : '' }}"></span>@endif
                @endforeach
            </div>
            <div class="labels">@foreach($steps as $s)<span>{{ $s['label'] }}</span>@endforeach</div>
        @endunless
        @if($track['bucket'] === 'ndr')
            <div class="alert">The courier could not deliver your parcel on the last attempt. They will try again — keep your phone reachable{{ $brand && $brand->support_phone ? ', or call us on ' . $brand->support_phone : '' }}.</div>
        @endif
        @if($cod)<div class="alert" style="background:#eef4ff;color:#1a3c7a">Cash on delivery: please keep <b>₹{{ number_format($cod, 2) }}</b> ready.</div>@endif
    </div>

    @if($brand && $brand->banner_text)
        @if($brand->banner_link)<a class="banner" href="{{ $brand->banner_link }}" target="_blank" rel="noopener">{{ $brand->banner_text }}</a>
        @else<div class="banner">{{ $brand->banner_text }}</div>@endif
    @endif

    <div class="card">
        <div class="row">
            <div><b>Delivering to</b>{{ $city ?: '—' }}</div>
            <div><b>Items</b>{{ $items->implode(', ') ?: '—' }}</div>
        </div>
    </div>

    <div class="card">
        <div style="font-weight:700;margin-bottom:12px">Shipment updates</div>
        @if(count($track['history']))
            <ul class="tl">
                @foreach($track['history'] as $h)
                    @php try { $when = $h['event_time'] ? \Carbon\Carbon::parse($h['event_time'])->format('d M Y, h:i A') : ''; } catch (\Throwable $e) { $when = (string) $h['event_time']; } @endphp
                    <li><div class="m">{{ $h['message'] }}</div><div class="w">{{ $h['location'] }}{{ $h['location'] && $when ? ' · ' : '' }}{{ $when }}</div></li>
                @endforeach
            </ul>
        @else
            <p class="muted" style="margin:0">Detailed scans will appear here as the courier updates them. Current status: {{ $track['status'] }}.</p>
        @endif
    </div>

    @if($brand && ($brand->support_phone || $brand->support_email || $brand->website))
        <div class="card help">
            <div style="font-weight:700;margin-bottom:8px">Need help with your order?</div>
            @if($brand->support_phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $brand->support_phone) }}">Call {{ $brand->support_phone }}</a>@endif
            @if($brand->support_email)<a href="mailto:{{ $brand->support_email }}">E-mail us</a>@endif
            @if($brand->website)<a href="{{ $brand->website }}" target="_blank" rel="noopener">Visit store</a>@endif
        </div>
    @endif
@endif
    <div class="foot">Tracking powered by Shipxpeed</div>
</div>
</body>
</html>
