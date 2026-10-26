@extends('layouts.app')

@section('content')
<style>
    .fs-pill{display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600}
    .fs-ok{background:var(--sx-success-soft,#e6f6ec);color:var(--sx-success,#137333)}
    .fs-bad{background:var(--sx-danger-soft,#fdecea);color:var(--sx-danger,#b3261e)}
    .fs-warn{background:var(--sx-warning-soft,#fff4e5);color:var(--sx-warning,#8a5300)}
    .fs-mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px}
    .fs-table td,.fs-table th{vertical-align:middle;font-size:13px}
</style>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Fship Integration</h5>
        <a href="{{ route('admin.fship.index', ['refresh' => 1]) }}" class="btn btn-sm btn-primary">Test connection</a>
    </div>
    <div class="card-body">
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="row g-3">
            <div class="col-md-3"><div class="text-muted small">Environment</div><strong>{{ ucfirst($env) }}</strong><div class="fs-mono text-muted">{{ $baseUrl }}</div></div>
            <div class="col-md-3"><div class="text-muted small">Client key</div>
                @if($configured)<span class="fs-pill fs-ok">Set ({{ $keySource === 'env' ? '.env' : 'saved here' }})</span>@else<span class="fs-pill fs-bad">Missing — paste it below</span>@endif
            </div>
            <div class="col-md-3"><div class="text-muted small">Connection</div>
                @if(!$configured)<span class="fs-pill fs-warn">Not tested</span>
                @elseif(count($couriers))<span class="fs-pill fs-ok">Connected · {{ count($couriers) }} couriers</span>
                @else<span class="fs-pill fs-bad">Failed — see API log below</span>@endif
            </div>
            <div class="col-md-3"><div class="text-muted small">Panel courier</div>
                @if($provider)
                    <span class="fs-pill {{ (string)$provider->status === '1' || $provider->status === 'active' ? 'fs-ok' : 'fs-warn' }}">logistic_providers #{{ $provider->id }} · status {{ $provider->status }}</span>
                @else<span class="fs-pill fs-bad">Run php artisan migrate</span>@endif
                @unless($migrated)<div><span class="fs-pill fs-bad mt-1">Migration pending</span></div>@endunless
            </div>
        </div>
        @if($provider && $env !== 'staging')
        <form method="POST" action="{{ route('admin.fship.settings') }}" class="row g-2 align-items-end mt-2">
            @csrf
            <div class="col-md-6"><label class="form-label small">Fship client key (Fship › Settings › API Details)</label>
                <input name="api_key" type="password" autocomplete="off" class="form-control form-control-sm" placeholder="{{ $configured ? 'Saved — leave blank to keep' : 'Paste client key' }}"></div>
            <div class="col-6 col-md-3"><label class="form-label small">Show Fship to sellers</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="1" @selected((string)$provider->status === '1')>On</option>
                    <option value="0" @selected((string)$provider->status !== '1')>Off</option>
                </select></div>
            <div class="col-6 col-md-3"><button class="btn btn-sm btn-primary w-100">Save</button></div>
        </form>
        @endif
        <p class="text-muted small mt-3 mb-0">
            Sellers see every active Fship courier by default. Seller price = zone rate card for key <span class="fs-mono">Fship_&lt;Courier_Name&gt;</span> (Zone Price Setting) if one exists,
            otherwise Fship cost + {{ $markup }}% markup + GST. To hide Fship for a seller, set courier <span class="fs-mono">Fship</span> (or a single <span class="fs-mono">Fship_…</span> key) inactive in Active Slabs.
        </p>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Couriers on the Fship account</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm fs-table mb-0">
                    <thead><tr><th>ID</th><th>Courier</th><th>Rate-card key</th><th>Active</th></tr></thead>
                    <tbody>
                    @forelse($couriers as $c)
                        <tr>
                            <td>{{ $c['courierId'] ?? '' }}</td>
                            <td>{{ $c['courierName'] ?? '' }}</td>
                            <td class="fs-mono">{{ \App\Services\FshipService::rateKey($c['courierName'] ?? '') }}</td>
                            <td>{!! ($c['isActive'] ?? true) ? '<span class="fs-pill fs-ok">Yes</span>' : '<span class="fs-pill fs-warn">No</span>' !!}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted p-3">No couriers loaded.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Rate &amp; serviceability test</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.fship.rate-test') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-6 col-md-2"><label class="form-label small">From pincode</label><input name="from" class="form-control form-control-sm" value="{{ old('from', $rateResult['input']['from'] ?? '110053') }}" required></div>
                    <div class="col-6 col-md-2"><label class="form-label small">To pincode</label><input name="to" class="form-control form-control-sm" value="{{ old('to', $rateResult['input']['to'] ?? '') }}" required></div>
                    <div class="col-4 col-md-2"><label class="form-label small">Weight (kg)</label><input name="weight" class="form-control form-control-sm" value="{{ old('weight', $rateResult['input']['weight'] ?? '0.5') }}" required></div>
                    <div class="col-4 col-md-2"><label class="form-label small">Payment</label>
                        <select name="payment" class="form-select form-select-sm">
                            <option value="P" @selected(($rateResult['input']['payment'] ?? '') === 'P')>Prepaid</option>
                            <option value="COD" @selected(($rateResult['input']['payment'] ?? '') === 'COD')>COD</option>
                        </select></div>
                    <div class="col-4 col-md-2"><label class="form-label small">Value (₹)</label><input name="amount" class="form-control form-control-sm" value="{{ old('amount', $rateResult['input']['amount'] ?? '1000') }}" required></div>
                    <div class="col-12 col-md-2"><button class="btn btn-sm btn-primary w-100">Check</button></div>
                </form>
                @if($errors->any())<div class="alert alert-danger mt-2 small">{{ $errors->first() }}</div>@endif

                @if($rateResult)
                    <hr>
                    @php($sv = $rateResult['serviceable'])
                    <div class="small mb-2">
                        Serviceability:
                        @if($sv['ok']) <span class="fs-pill fs-ok">{{ $sv['data']['source'] ?? '' }} → {{ $sv['data']['destination'] ?? '' }} · zone {{ $sv['data']['zone'] ?? '-' }} · COD {{ $sv['data']['cod'] ?? '-' }}</span>
                        @else <span class="fs-pill fs-bad">{{ $sv['error'] }}</span> @endif
                    </div>
                    @php($rt = $rateResult['rates'])
                    @if($rt['ok'] && !empty($rt['data']['shipment_rates']))
                        <table class="table table-sm fs-table">
                            <thead><tr><th>Courier</th><th>Mode</th><th>Freight</th><th>COD</th><th>RTO</th><th>EDD</th></tr></thead>
                            <tbody>
                            @foreach($rt['data']['shipment_rates'] as $r)
                                <tr>
                                    <td>{{ $r['courier_name'] ?? '' }} <span class="text-muted">#{{ $r['courier_id'] ?? '' }}</span></td>
                                    <td>{{ $r['service_mode'] ?? '' }}</td>
                                    <td>₹{{ $r['shipping_charge'] ?? 0 }}</td>
                                    <td>₹{{ $r['cod_charge'] ?? 0 }}</td>
                                    <td>₹{{ $r['rto_charge'] ?? 0 }}</td>
                                    <td>{{ !empty($r['expectedDeliveryDate']) ? \Carbon\Carbon::parse($r['expectedDeliveryDate'])->format('d M') : '-' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <div class="text-muted small">Fship cost, excluding GST and additional charges.</div>
                    @else
                        <div class="small"><span class="fs-pill fs-bad">{{ $rt['error'] ?? 'No rates' }}</span></div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">API log (last 50 Fship calls)</h6></div>
    <div class="card-body p-0" style="overflow-x:auto">
        <table class="table table-sm fs-table mb-0">
            <thead><tr><th>Time</th><th>Endpoint</th><th>HTTP</th><th>Result</th><th>ms</th><th>Order</th><th>AWB</th><th>Response</th><th></th></tr></thead>
            <tbody>
            @forelse($logs as $l)
                <tr>
                    <td class="text-nowrap">{{ \Carbon\Carbon::parse($l->created_at)->format('d M H:i:s') }}</td>
                    <td class="fs-mono">{{ $l->endpoint }}</td>
                    <td>{{ $l->http_status }}</td>
                    <td>{!! $l->success ? '<span class="fs-pill fs-ok">OK</span>' : '<span class="fs-pill fs-bad">Fail</span>' !!}</td>
                    <td>{{ $l->duration_ms }}</td>
                    <td>{{ $l->order_id }}</td>
                    <td class="fs-mono">{{ $l->awb }}</td>
                    <td class="fs-mono text-muted" style="max-width:360px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ \Illuminate\Support\Str::limit((string) $l->response, 140) }}</td>
                    <td><a href="{{ route('admin.fship.log', $l->id) }}" target="_blank" class="small">View</a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-muted p-3">{{ $migrated ? 'No calls logged yet.' : 'Run php artisan migrate to enable API logging.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(count($warehouses))
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Pickup addresses registered on Fship</h6></div>
    <div class="card-body p-0">
        <table class="table table-sm fs-table mb-0">
            <thead><tr><th>Seller</th><th>Name on Fship</th><th>Pincode</th><th>Fship ID</th><th>RTO ID</th><th>Env</th><th>Created</th></tr></thead>
            <tbody>
            @foreach($warehouses as $w)
                <tr><td>{{ $w->seller_id }}</td><td class="fs-mono">{{ $w->warehouse_name }}</td><td>{{ $w->pincode }}</td><td>{{ $w->fship_warehouse_id }}</td><td>{{ $w->fship_rto_warehouse_id }}</td><td>{{ $w->environment }}</td><td>{{ \Carbon\Carbon::parse($w->created_at)->format('d M Y') }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
