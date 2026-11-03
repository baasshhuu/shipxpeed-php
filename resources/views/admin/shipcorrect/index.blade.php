@extends('layouts.app')

@section('content')
<style>
    .fs-pill{display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600}
    .fs-ok{background:var(--sx-success-soft,#e6f6ec);color:var(--sx-success,#137333)}
    .fs-bad{background:var(--sx-danger-soft,#fdecea);color:var(--sx-danger,#b3261e)}
    .fs-warn{background:var(--sx-warning-soft,#fff4e5);color:var(--sx-warning,#8a5300)}
    .fs-mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px}
    .fs-table td,.fs-table th{vertical-align:middle;font-size:13px}
    .fs-logo{height:24px;width:56px;object-fit:contain;margin-right:8px;background:#fff;border-radius:4px;padding:2px;vertical-align:middle}
</style>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">ShipCorrect Integration</h5>
        <a href="{{ route('admin.shipcorrect.index', ['refresh' => 1]) }}" class="btn btn-sm btn-primary">Test connection</a>
    </div>
    <div class="card-body">
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($lowBalance)
            <div class="alert alert-danger d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><strong>ShipCorrect account balance is empty</strong> (since {{ $lowBalance }}). Bookings fail, so ShipCorrect couriers are hidden from sellers for 30 minutes. Recharge the wallet on shipcorrect.com, then click the button.</span>
                <a href="{{ route('admin.shipcorrect.index', ['balance_ok' => 1]) }}" class="btn btn-sm btn-light">I recharged - show couriers again</a>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-md-3"><div class="text-muted small">API</div><strong>Production</strong><div class="fs-mono text-muted">{{ $baseUrl }}</div></div>
            <div class="col-md-3"><div class="text-muted small">API key</div>
                @if($configured)<span class="fs-pill fs-ok">Set ({{ $keySource === 'env' ? '.env' : 'saved here' }})</span><div class="small text-muted mt-1">{{ $keyHint }}</div>@else<span class="fs-pill fs-bad">Missing — paste it below</span>@endif
            </div>
            <div class="col-md-3"><div class="text-muted small">Connection</div>
                @if(!$configured)<span class="fs-pill fs-warn">Not tested</span>
                @elseif($conn && $conn['ok'])<span class="fs-pill fs-ok">Connected · {{ $conn['count'] }} courier rates</span>
                @else<span class="fs-pill fs-bad">Failed</span><div class="small text-muted mt-1">{{ $conn['error'] ?? '' }}</div>@endif
            </div>
            <div class="col-md-3"><div class="text-muted small">Panel courier</div>
                @if($provider)
                    <span class="fs-pill {{ in_array((string) $provider->status, ['1', 'active'], true) ? 'fs-ok' : 'fs-warn' }}">logistic_providers #{{ $provider->id }} · {{ in_array((string) $provider->status, ['1', 'active'], true) ? 'on' : 'off' }}</span>
                @else<span class="fs-pill fs-bad">Run php artisan migrate</span>@endif
            </div>
        </div>

        @if($provider)
        <form method="POST" action="{{ route('admin.shipcorrect.settings') }}" class="row g-2 align-items-end mt-2">
            @csrf
            <div class="col-md-6"><label class="form-label small">ShipCorrect secret API key (ShipCorrect › API Documentation)</label>
                <input name="api_key" type="password" autocomplete="off" class="form-control form-control-sm" placeholder="{{ $configured ? 'Saved — leave blank to keep' : 'Paste API key' }}"></div>
            <div class="col-6 col-md-3"><label class="form-label small">ShipCorrect in panel</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="1" @selected(in_array((string) $provider->status, ['1', 'active'], true))>On</option>
                    <option value="0" @selected(!in_array((string) $provider->status, ['1', 'active'], true))>Off</option>
                </select></div>
            <div class="col-6 col-md-3"><button class="btn btn-sm btn-primary w-100">Save</button></div>
        </form>
        @endif

        <p class="text-muted small mt-3 mb-0">
            Each ShipCorrect courier starts <strong>OFF</strong> and is marked NEW on Courier Status until you approve it.
            Seller price = zone rate card for key <span class="fs-mono">ShipCorrect_&lt;Courier&gt;</span> if one exists, otherwise ShipCorrect cost + {{ $markup }}% markup + 18% GST.
            Sellers only ever see the real courier name (e.g. "Ekart Surface").
        </p>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Couriers ShipCorrect can book</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm fs-table mb-0">
                    <thead><tr><th>Courier</th><th>Rate-card key</th><th>Default</th></tr></thead>
                    <tbody>
                    @foreach($couriers as $key => $label)
                        @php($def = \App\Support\CourierAccess::defaults()[$key] ?? null)
                        <tr>
                            <td class="text-nowrap"><img class="fs-logo" src="{{ asset(\App\Support\CourierBrand::sellerLogo($label)) }}" alt="">{{ $label }}</td>
                            <td class="fs-mono">{{ $key }}</td>
                            <td>{!! $def === null ? '<span class="fs-pill fs-warn">New · off</span>' : ($def ? '<span class="fs-pill fs-ok">On</span>' : '<span class="fs-pill fs-bad">Off</span>') !!}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Tracking test</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.shipcorrect.track-test') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-8"><label class="form-label small">AWB</label><input name="awb" class="form-control form-control-sm" value="{{ $trackResult['awb'] ?? '' }}" required></div>
                    <div class="col-4"><button class="btn btn-sm btn-primary w-100">Track</button></div>
                </form>
                @if($trackResult)
                    @php($t = $trackResult['track'])
                    <hr>
                    @if(!empty($t['status']))
                        <div class="small mb-2"><strong>{{ $t['raw_status'] ?? '-' }}</strong> → panel status <span class="fs-pill fs-ok">{{ $t['mapped_status'] }}</span> · <img class="fs-logo" src="{{ asset(\App\Support\CourierBrand::sellerLogo($t['courier'] ?? '')) }}" alt="">{{ $t['courier'] ?? '' }}</div>
                        <table class="table table-sm fs-table mb-0">
                            @foreach($t['history'] as $h)<tr><td class="text-nowrap">{{ $h['event_time'] }}</td><td>{{ $h['message'] }}</td><td>{{ $h['location'] }}</td></tr>@endforeach
                        </table>
                    @else
                        <span class="fs-pill fs-bad">{{ $t['message'] ?? 'No data' }}</span>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Live rate test</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.shipcorrect.rate-test') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-6 col-md-2"><label class="form-label small">From pincode</label><input name="from" class="form-control form-control-sm" value="{{ $rateResult['input']['from'] ?? '110053' }}" required></div>
                    <div class="col-6 col-md-2"><label class="form-label small">To pincode</label><input name="to" class="form-control form-control-sm" value="{{ $rateResult['input']['to'] ?? '' }}" required></div>
                    <div class="col-4 col-md-2"><label class="form-label small">Weight (kg)</label><input name="weight" class="form-control form-control-sm" value="{{ $rateResult['input']['weight'] ?? '0.5' }}" required></div>
                    <div class="col-4 col-md-2"><label class="form-label small">Payment</label>
                        <select name="payment" class="form-select form-select-sm">
                            <option value="prepaid" @selected(($rateResult['input']['payment'] ?? '') === 'prepaid')>Prepaid</option>
                            <option value="cod" @selected(($rateResult['input']['payment'] ?? '') === 'cod')>COD</option>
                        </select></div>
                    <div class="col-4 col-md-2"><label class="form-label small">Value (₹)</label><input name="amount" class="form-control form-control-sm" value="{{ $rateResult['input']['amount'] ?? '1000' }}" required></div>
                    <div class="col-12 col-md-2"><button class="btn btn-sm btn-primary w-100">Check</button></div>
                </form>
                @if($errors->any())<div class="alert alert-danger mt-2 small">{{ $errors->first() }}</div>@endif

                @if($rateResult)
                    <hr>
                    @php($rt = $rateResult['rates'])
                    @if($rt['ok'] && !empty($rt['data']['rates']))
                        <div class="small text-muted mb-2">Applied weight {{ $rt['data']['applied_weight'] ?? '-' }} kg (volumetric {{ $rt['data']['volumetric_weight'] ?? '-' }} kg)</div>
                        <table class="table table-sm fs-table">
                            <thead><tr><th>Courier</th><th>Mode</th><th>Freight</th><th>COD</th><th>GST</th><th>Total (cost)</th></tr></thead>
                            <tbody>
                            @foreach($rt['data']['rates'] as $r)
                                <tr>
                                    <td class="text-nowrap"><img class="fs-logo" src="{{ asset(\App\Support\CourierBrand::sellerLogo($r['courier'] ?? '')) }}" alt="">{{ $r['courier'] ?? $r['courier_name'] ?? '' }}</td>
                                    <td>{{ $r['mode'] ?? '' }}</td>
                                    <td>₹{{ $r['Freight_charge'] ?? '-' }}</td>
                                    <td>₹{{ $r['COD_CHARGE'] ?? 0 }}</td>
                                    <td>₹{{ $r['Gst'] ?? '-' }}</td>
                                    <td><strong>₹{{ $r['rate'] ?? '-' }}</strong></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <div class="text-muted small">ShipCorrect cost to Shipxpeed (not the seller price). ShipCorrect's rate API does not check pincode serviceability — an unserviceable pincode is rejected at booking.</div>
                    @else
                        <div class="small"><span class="fs-pill fs-bad">{{ $rt['error'] ?? 'No rates' }}</span></div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">API log (last 50 ShipCorrect calls)</h6></div>
    <div class="card-body p-0" style="overflow-x:auto">
        <table class="table table-sm fs-table mb-0">
            <thead><tr><th>Time</th><th>Endpoint</th><th>HTTP</th><th>Result</th><th>ms</th><th>Order</th><th>AWB</th><th>Response</th><th></th></tr></thead>
            <tbody>
            @forelse($logs as $l)
                <tr>
                    <td class="text-nowrap">{{ \Carbon\Carbon::parse($l->created_at)->format('d M H:i:s') }}</td>
                    <td class="fs-mono">{{ $l->endpoint }}</td>
                    <td>{{ $l->http_status ?? $l->status_code ?? '' }}</td>
                    <td>{!! $l->success ? '<span class="fs-pill fs-ok">OK</span>' : '<span class="fs-pill fs-bad">Fail</span>' !!}</td>
                    <td>{{ $l->duration_ms ?? '' }}</td>
                    <td>{{ $l->order_id ?? '' }}</td>
                    <td class="fs-mono">{{ $l->awb ?? '' }}</td>
                    <td class="fs-mono text-muted" style="max-width:360px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ \Illuminate\Support\Str::limit((string) ($l->response ?? $l->response_body ?? ''), 140) }}</td>
                    <td><a href="{{ route('admin.shipcorrect.log', $l->id) }}" target="_blank" class="small">View</a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-muted p-3">No calls logged yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
