@extends('layouts.sellerdash')

@section('content')
@php
    $statusLabels = [
        'sent' => ['Sent', 'wa-ok'], 'failed' => ['Failed', 'wa-bad'],
        'skipped_low_balance' => ['Low wallet', 'wa-warn'], 'skipped_no_phone' => ['No buyer mobile', 'wa-warn'],
        'skipped_event_off' => ['Paused', 'wa-muted'], 'duplicate_race' => ['Duplicate', 'wa-muted'],
    ];
    $ndrLabels = ['re-attempt' => 'Re-attempt delivery', 'change-address' => 'New address', 'change-phone' => 'New phone', 'rto' => 'Return to seller'];
    $ndrStatus = ['sent_to_courier' => ['Sent to courier', 'wa-ok'], 'needs_manual' => ['Ops team will action', 'wa-warn'], 'failed' => ['Courier rejected', 'wa-bad']];
    $mask = fn ($p) => $p ? substr($p, 0, 3) . ' ' . substr($p, 3, 2) . '•••••' . substr($p, -3) : '—';
@endphp
<style>
    .wa-page { --wa:#25d366; --wa-dark:#128c4b; }
    .wa-page .wa-head { background: linear-gradient(93deg,#1f2a44 0%,#1d3b4f 60%,#145a45 120%); color:#fff; border-radius:10px; padding:18px 20px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }
    .wa-page .wa-head h1 { font-size:1.25rem; margin:0; color:#fff; display:flex; align-items:center; gap:10px; }
    .wa-page .wa-head p { margin:4px 0 0; opacity:.85; font-size:.85rem; }
    .wa-page .wa-card { background:var(--bs-body-bg,#fff); border:1px solid rgba(120,130,150,.18); border-radius:10px; padding:18px; margin-bottom:16px; }
    .wa-page .wa-card h2 { font-size:1rem; margin:0 0 4px; }
    .wa-page .wa-sub { color:#7a8496; font-size:.82rem; margin:0 0 14px; }
    .wa-page .pill { display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:600; white-space:nowrap; }
    .wa-page .wa-ok { background:#e3f7ea; color:#137333; } .wa-page .wa-bad { background:#fdecea; color:#b3261e; }
    .wa-page .wa-warn { background:#fff4e5; color:#8a5300; } .wa-page .wa-muted { background:#eef0f4; color:#5f6878; }
    .wa-page .wa-free { color:#137333; font-weight:600; }
    .wa-page .stat { font-size:1.35rem; font-weight:700; line-height:1.2; }
    .wa-page .stat-l { color:#7a8496; font-size:.78rem; }
    .wa-page .switch-row { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; padding:14px; border-radius:10px; background:rgba(37,211,102,.07); border:1px solid rgba(37,211,102,.25); }
    .wa-page .switch-row.off { background:rgba(120,130,150,.07); border-color:rgba(120,130,150,.2); }
    .wa-page .btn-wa { background:var(--wa-dark); border-color:var(--wa-dark); color:#fff; }
    .wa-page .btn-wa:hover { background:#0f7a40; color:#fff; }
    .wa-page table { font-size:13px; }
    .wa-page td, .wa-page th { vertical-align:middle; }
    .wa-page .mono { font-family:ui-monospace,Menlo,Consolas,monospace; font-size:12px; }
    @media (prefers-color-scheme: dark) { .wa-page .wa-card { border-color:rgba(255,255,255,.1); } }
</style>

<div class="pc-container wa-page">
<div class="pc-content">

    <div class="wa-head">
        <div>
            <h1><i class="ti ti-brand-whatsapp"></i> WhatsApp order alerts</h1>
            <p>Your buyers get a WhatsApp message each time their shipment changes status. Paid service, charged per message from your wallet.</p>
        </div>
        <span class="pill {{ $active ? 'wa-ok' : 'wa-muted' }}" style="font-size:13px">{{ $active ? 'Service active' : 'Service off' }}</span>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if(!$ready)
        <div class="alert alert-warning">WhatsApp alerts are being set up on your account. Please check back shortly.</div>
    @elseif(!$platformOn)
        <div class="alert alert-info mb-3">Shipxpeed is finishing the WhatsApp setup. You can turn the service on now; messages start automatically once it's live, and you're only charged for messages actually sent.</div>
    @endif

    <div class="row">
        <div class="col-lg-7">
            <div class="wa-card">
                <h2>Service</h2>
                <p class="wa-sub">Only works while the service is active. Turn it off any time; nothing is sent or charged while it's off.</p>
                <form method="POST" action="{{ route('seller.whatsapp.alerts.update') }}">
                    @csrf
                    <div class="switch-row {{ $active ? '' : 'off' }}">
                        <div>
                            <strong>{{ $active ? 'WhatsApp alerts are ON' : 'WhatsApp alerts are OFF' }}</strong>
                            <div class="wa-sub mb-0">{{ $active ? 'Buyers are getting status updates on WhatsApp.' : 'Turn on to send buyers status updates on WhatsApp.' }}</div>
                        </div>
                        @if($active)
                            <button name="active" value="0" class="btn btn-outline-secondary btn-sm" @disabled(!$ready)>Turn off</button>
                        @else
                            <button name="active" value="1" class="btn btn-wa btn-sm" @disabled(!$ready)>Activate service</button>
                        @endif
                    </div>
                    <div class="mt-3">
                        <label class="form-label small mb-1" for="brand_name">Brand name shown to buyers</label>
                        <div class="d-flex gap-2">
                            <input type="text" id="brand_name" name="brand_name" maxlength="40" class="form-control form-control-sm" value="{{ old('brand_name', $brandName) }}" placeholder="e.g. {{ $seller->name }}">
                            <button name="active" value="{{ $active ? 1 : 0 }}" class="btn btn-sm btn-outline-primary" @disabled(!$ready)>Save</button>
                        </div>
                        <div class="form-text">Used in messages like "Your order from <em>{{ $brandName ?: 'your brand' }}</em> is out for delivery". Leave blank to use your account name.</div>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="wa-card">
                <div class="row g-3">
                    <div class="col-6"><div class="stat">{{ number_format($month['sent']) }}</div><div class="stat-l">Messages this month</div></div>
                    <div class="col-6"><div class="stat">₹{{ number_format($month['charged'], 2) }}</div><div class="stat-l">Charged this month</div></div>
                    <div class="col-12"><div class="stat" style="font-size:1.05rem">₹{{ number_format($balance, 2) }}</div><div class="stat-l">Wallet balance. Paid alerts pause if the wallet can't cover a message; free alerts keep going.</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="wa-card">
        <h2>What buyers receive and what it costs</h2>
        <p class="wa-sub">Each status is sent once per shipment (delivery-failed alerts once per day). Rates are per message and set by Shipxpeed for your account.</p>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Shipment status</th><th>Buyer gets</th><th class="text-end">Your rate</th></tr></thead>
                <tbody>
                @foreach($events as $key => $e)
                    <tr>
                        <td><strong>{{ $e['label'] }}</strong></td>
                        <td class="text-muted">
                            @switch($key)
                                @case('ndr') Delivery failed notice{{ $e['format'] === 'full' ? ' with a link to choose: re-attempt (with date), new address, new phone, or return' : ' with Re-attempt / Return buttons' }}. @break
                                @case('booked') Order shipped, courier, AWB and tracking link. @break
                                @case('delivered') Delivery confirmation. @break
                                @default Status update{{ $e['format'] === 'full' ? ' with tracking link' : ' with order number and AWB' }}.
                            @endswitch
                        </td>
                        <td class="text-end">@if($e['seller_rate'] > 0) ₹{{ rtrim(rtrim(number_format($e['seller_rate'], 2), '0'), '.') }} @else <span class="wa-free">Free</span> @endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($ndr->isNotEmpty())
    <div class="wa-card">
        <h2>Buyer replies to failed deliveries</h2>
        <p class="wa-sub">What your buyers chose from the WhatsApp delivery-failed link.</p>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>When</th><th>AWB</th><th>Buyer chose</th><th>Details</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($ndr as $r)
                    @php [$sl, $sc] = $ndrStatus[$r->status] ?? [ucfirst(str_replace('_', ' ', $r->status)), 'wa-muted']; @endphp
                    <tr>
                        <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($r->responded_at)->format('d M, h:i A') }}</td>
                        <td class="mono">{{ $r->awb_number }}</td>
                        <td>{{ $ndrLabels[$r->action] ?? $r->action }}</td>
                        <td class="small text-muted">
                            @if($r->reattempt_date) Date: {{ \Illuminate\Support\Carbon::parse($r->reattempt_date)->format('d M') }} @endif
                            @if($r->address) <div>{{ \Illuminate\Support\Str::limit($r->address, 80) }}</div> @endif
                            @if($r->phone) <div>Phone: {{ $r->phone }}</div> @endif
                        </td>
                        <td><span class="pill {{ $sc }}">{{ $sl }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="wa-card">
        <h2>Message history</h2>
        <p class="wa-sub">Last 100 alerts for your shipments.</p>
        @if($messages->isEmpty())
            <p class="text-muted small mb-0">No messages yet. {{ $active ? 'They will appear here as your shipments move.' : 'Activate the service to start.' }}</p>
        @else
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>When</th><th>AWB</th><th>Status alert</th><th>Buyer</th><th>Result</th><th class="text-end">Charge</th></tr></thead>
                <tbody>
                @foreach($messages as $m)
                    @php [$ml, $mc] = $statusLabels[$m->status] ?? [ucfirst(str_replace(['skipped_', '_'], ['', ' '], $m->status)), 'wa-muted']; @endphp
                    <tr>
                        <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($m->created_at)->format('d M, h:i A') }}</td>
                        <td class="mono">{{ $m->awb_number }}</td>
                        <td>{{ config('whatsapp.events.' . $m->event . '.label', $m->event) }}</td>
                        <td class="mono">{{ $mask($m->phone) }}</td>
                        <td><span class="pill {{ $mc }}" @if($m->status !== 'sent' && $m->reason) title="{{ $m->reason }}" @endif>{{ $ml }}</span></td>
                        <td class="text-end">{{ $m->charge > 0 ? '₹' . number_format($m->charge, 2) : '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
</div>
@endsection
