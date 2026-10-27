@extends('layouts.app')

@section('content')
@php
    $pill = fn ($s) => match (true) {
        $s === 'sent', $s === 'sent_to_courier', $s === 'actioned_by_ops' => 'wa-ok',
        $s === 'failed' => 'wa-bad',
        str_starts_with($s, 'skipped_low'), $s === 'needs_manual', $s === 'processing' => 'wa-warn',
        default => 'wa-muted',
    };
    $ndrLabels = ['re-attempt' => 'Re-attempt', 'change-address' => 'New address', 'change-phone' => 'New phone', 'rto' => 'Return to seller'];
@endphp
<style>
    .wa-pill{display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;white-space:nowrap}
    .wa-ok{background:#e6f6ec;color:#137333}.wa-bad{background:#fdecea;color:#b3261e}.wa-warn{background:#fff4e5;color:#8a5300}.wa-muted{background:#eef0f4;color:#5f6878}
    .wa-mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px}
    .wa-table td,.wa-table th{vertical-align:middle;font-size:13px}
    .wa-stat{font-size:1.4rem;font-weight:700;line-height:1.2}.wa-stat-l{font-size:12px;color:#7a8496}
    .wa-rate{max-width:90px}
</style>

@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@unless($migrated)<div class="alert alert-warning">Database not ready: run <span class="wa-mono">php artisan migrate --path=database/migrations/2026_10_27_000005_whatsapp_aisensy.php --force</span></div>@endunless

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-3"><div class="wa-stat-l">AiSensy API key</div>
                @if($keySource)<span class="wa-pill wa-ok">Set ({{ $keySource === 'env' ? '.env' : 'saved here' }})</span>@else<span class="wa-pill wa-bad">Missing</span>@endif</div>
            <div class="col-6 col-md-3"><div class="wa-stat">{{ $stats['sent_today'] }}</div><div class="wa-stat-l">Alerts sent today ({{ $stats['failed_today'] }} failed)</div></div>
            <div class="col-6 col-md-3"><div class="wa-stat">₹{{ number_format($stats['billed_month'], 2) }}</div><div class="wa-stat-l">Billed to sellers this month</div></div>
            <div class="col-6 col-md-3"><div class="wa-stat">{{ $stats['active_sellers'] }}</div><div class="wa-stat-l">Sellers with service active</div></div>
        </div>
    </div>
</div>

<div class="row">
<div class="col-xl-7">
<form method="POST" action="{{ route('admin.whatsapp.settings') }}" class="card mb-3">
    @csrf
    <div class="card-header"><h5 class="mb-0">Settings and default rates</h5></div>
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-md-12"><label class="form-label small">AiSensy API key (AiSensy › Manage › API Key)</label>
                <input name="api_key" type="password" autocomplete="off" class="form-control form-control-sm" placeholder="{{ $keySource === 'env' ? 'Using AISENSY_API_KEY from .env' : ($keySource ? 'Saved, leave blank to keep' : 'Paste API key') }}"></div>
            <div class="col-6 col-md-3"><label class="form-label small">WhatsApp OTP</label>
                <select name="otp_enabled" class="form-select form-select-sm"><option value="1" @selected($saved['otp_enabled'] ?? true)>On</option><option value="0" @selected(!($saved['otp_enabled'] ?? true))>Off</option></select></div>
            <div class="col-6 col-md-3"><label class="form-label small">OTP copy-code button</label>
                <select name="otp_copy_button" class="form-select form-select-sm"><option value="1" @selected($saved['otp_copy_button'] ?? true)>Yes</option><option value="0" @selected(!($saved['otp_copy_button'] ?? true))>No</option></select></div>
            <div class="col-12 col-md-6"><label class="form-label small">OTP campaign name</label>
                <input name="otp_campaign" class="form-control form-control-sm wa-mono" value="{{ $otpCampaign }}"></div>
            <div class="col-6 col-md-3"><label class="form-label small">Buyer alerts (all sellers)</label>
                <select name="alerts_enabled" class="form-select form-select-sm"><option value="1" @selected($saved['alerts_enabled'] ?? true)>On</option><option value="0" @selected(!($saved['alerts_enabled'] ?? true))>Off</option></select></div>
        </div>

        <div class="table-responsive">
        <table class="table table-sm wa-table mb-2">
            <thead><tr><th>Status alert</th><th>AiSensy API campaign name</th><th>Template fields</th><th>Default ₹ / msg</th><th class="text-center">On</th></tr></thead>
            <tbody>
            @foreach($events as $key => $e)
                <tr>
                    <td>{{ $e['label'] }}</td>
                    <td><input name="events[{{ $key }}][campaign]" class="form-control form-control-sm wa-mono" value="{{ $e['campaign'] }}" placeholder="not set up yet"></td>
                    <td><select name="events[{{ $key }}][format]" class="form-select form-select-sm">
                        <option value="basic" @selected($e['format'] === 'basic')>3: name, order, AWB</option>
                        <option value="full" @selected($e['format'] === 'full')>6: name, brand, order, courier, AWB, link</option>
                    </select></td>
                    <td><input name="events[{{ $key }}][rate]" type="number" step="0.01" min="0" class="form-control form-control-sm wa-rate" value="{{ $e['rate'] }}"></td>
                    <td class="text-center"><input type="checkbox" name="events[{{ $key }}][enabled]" value="1" class="form-check-input" @checked($e['enabled'])></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        <p class="text-muted small mb-3">Statuses with no campaign name send nothing and charge nothing. Default rate applies to every seller unless you set a custom rate for them on the right. 0 = free. For "6" templates the variables are: @{{1}} buyer name, @{{2}} seller brand, @{{3}} order no., @{{4}} courier, @{{5}} AWB, @{{6}} link (tracking link; for delivery-failed it's the buyer's response link).</p>
        <button class="btn btn-primary btn-sm">Save settings</button>
    </div>
</form>

<form method="POST" action="{{ route('admin.whatsapp.test') }}" class="card mb-3">
    @csrf
    <div class="card-header"><h5 class="mb-0">Send a test</h5></div>
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label small">Mobile</label><input name="phone" class="form-control form-control-sm" placeholder="10-digit" inputmode="numeric"></div>
        <div class="col-md-5"><label class="form-label small">Template</label>
            <select name="event" class="form-select form-select-sm"><option value="otp">OTP code</option>@foreach($events as $key => $e)<option value="{{ $key }}">{{ $e['label'] }}</option>@endforeach</select></div>
        <div class="col-md-3"><button class="btn btn-outline-primary btn-sm w-100">Send test</button></div>
    </div>
</form>
</div>

<div class="col-xl-5">
<div class="card mb-3">
    <div class="card-header"><h5 class="mb-0">Seller rates and service</h5></div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.whatsapp.index') }}" class="d-flex gap-2 mb-3">
            <select name="seller" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Choose a seller…</option>
                @foreach($sellers as $s)<option value="{{ $s->id }}" @selected($editSeller && $editSeller->id === $s->id)>{{ $s->name }} · #{{ $s->id }}{{ $s->whatsapp_alerts_active ? ' · active' : '' }}</option>@endforeach
            </select>
        </form>
        @if($editSeller)
            @php $custom = \App\Services\WhatsApp\WhatsAppSettings::sellerRates($editSeller); @endphp
            <form method="POST" action="{{ route('admin.whatsapp.seller', $editSeller->id) }}">
                @csrf
                <div class="mb-2"><label class="form-label small">WhatsApp alert service</label>
                    <select name="active" class="form-select form-select-sm"><option value="1" @selected($editSeller->whatsapp_alerts_active)>Active</option><option value="0" @selected(!$editSeller->whatsapp_alerts_active)>Off</option></select></div>
                <table class="table table-sm wa-table mb-2">
                    <thead><tr><th>Status alert</th><th>Default</th><th>Custom ₹</th></tr></thead>
                    <tbody>
                    @foreach($events as $key => $e)
                        <tr><td>{{ $e['label'] }}</td><td>₹{{ $e['rate'] }}</td>
                            <td><input name="rates[{{ $key }}]" type="number" step="0.01" min="0" class="form-control form-control-sm wa-rate" value="{{ $custom[$key] ?? '' }}" placeholder="default"></td></tr>
                    @endforeach
                    </tbody>
                </table>
                <p class="text-muted small">Leave blank to use the default rate. Brand shown to buyers: <strong>{{ $editSeller->whatsapp_brand_name ?: 'pickup location name' }}</strong>.</p>
                <button class="btn btn-primary btn-sm">Save for {{ $editSeller->name }}</button>
            </form>
        @else
            <p class="text-muted small mb-0">Pick a seller to switch their service on or off and set custom per-message rates.</p>
        @endif
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h5 class="mb-0">NDR replies needing ops action ({{ $ndrQueue->count() }})</h5></div>
    <div class="card-body p-0">
        @if($ndrQueue->isEmpty())
            <p class="text-muted small p-3 mb-0">Nothing pending. Requests the courier API accepted go through automatically.</p>
        @else
        <div class="table-responsive"><table class="table table-sm wa-table mb-0">
            <thead><tr><th>AWB / seller</th><th>Buyer wants</th><th></th></tr></thead>
            <tbody>
            @foreach($ndrQueue as $r)
                @php $cr = json_decode((string) $r->courier_response, true); @endphp
                <tr>
                    <td><span class="wa-mono">{{ $r->awb_number }}</span><div class="small text-muted">{{ $sellerNames[$r->seller_id] ?? '#' . $r->seller_id }}</div></td>
                    <td><strong>{{ $ndrLabels[$r->action] ?? $r->action }}</strong>
                        @if($r->reattempt_date)<div class="small">Date: {{ \Illuminate\Support\Carbon::parse($r->reattempt_date)->format('d M') }}</div>@endif
                        @if($r->address)<div class="small">{{ $r->address }}{{ $r->landmark ? ' (' . $r->landmark . ')' : '' }}</div>@endif
                        @if($r->phone)<div class="small">Phone: {{ $r->phone }}</div>@endif
                        @if($r->remark)<div class="small text-muted">{{ $r->remark }}</div>@endif
                        @if(!empty($cr['message']))<div class="small text-muted">Why manual: {{ $cr['message'] }}</div>@endif
                    </td>
                    <td><form method="POST" action="{{ route('admin.whatsapp.ndr.done', $r->id) }}">@csrf<button class="btn btn-sm btn-outline-success">Done</button></form></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        @endif
    </div>
</div>
</div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Message log</h5>
        <form method="GET" class="d-flex gap-2">
            <input name="awb" class="form-control form-control-sm" placeholder="AWB" value="{{ request('awb') }}">
            <select name="status" class="form-select form-select-sm"><option value="">All</option>
                @foreach(['sent','failed','skipped_low_balance','skipped_no_phone','skipped_event_off'] as $st)<option @selected(request('status') === $st)>{{ $st }}</option>@endforeach</select>
            <button class="btn btn-sm btn-outline-secondary">Filter</button>
        </form>
    </div>
    <div class="card-body p-0"><div class="table-responsive">
        <table class="table table-sm wa-table mb-0">
            <thead><tr><th>Time</th><th>Seller</th><th>AWB</th><th>Alert</th><th>To</th><th>Result</th><th>₹</th><th></th></tr></thead>
            <tbody>
            @forelse($messages as $m)
                <tr>
                    <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($m->created_at)->format('d M H:i') }}</td>
                    <td>{{ $sellerNames[$m->seller_id] ?? '#' . $m->seller_id }}</td>
                    <td class="wa-mono">{{ $m->awb_number }}</td>
                    <td>{{ $events[$m->event]['label'] ?? $m->event }}<div class="wa-mono text-muted">{{ $m->campaign }}</div></td>
                    <td class="wa-mono">{{ \App\Services\WhatsApp\AiSensyClient::mask($m->phone) }}</td>
                    <td><span class="wa-pill {{ $pill($m->status) }}">{{ $m->status }}</span>@if($m->reason)<div class="small text-muted">{{ \Illuminate\Support\Str::limit($m->reason, 90) }}</div>@endif</td>
                    <td>{{ $m->charge > 0 ? number_format($m->charge, 2) : '—' }}</td>
                    <td>@if($m->status !== 'sent' && $m->order_id)<form method="POST" action="{{ route('admin.whatsapp.resend', $m->id) }}">@csrf<button class="btn btn-sm btn-link p-0">Retry</button></form>@endif</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-muted p-3">No messages yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@endsection
