@extends('layouts.app')

@section('content')
<style>
    .bs-pill{display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600}
    .bs-ok{background:var(--sx-success-soft,#e6f6ec);color:var(--sx-success,#137333)}
    .bs-bad{background:var(--sx-danger-soft,#fdecea);color:var(--sx-danger,#b3261e)}
    .bs-warn{background:var(--sx-warning-soft,#fff4e5);color:var(--sx-warning,#8a5300)}
    .bs-mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px}
    .bs-table td,.bs-table th{vertical-align:middle;font-size:13px}
</style>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Bigship B2B (domestic heavy / multi-box)</h5>
        <a href="{{ route('admin.bigship.index', ['test' => 1]) }}" class="btn btn-sm btn-primary">Test login</a>
    </div>
    <div class="card-body">
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        @unless($migrated)<div class="alert alert-warning">Database tables missing - run the B2B installer (php artisan migrate).</div>@endunless

        <div class="row g-3">
            <div class="col-md-3"><div class="text-muted small">Login details</div>
                @if($configured)<span class="bs-pill bs-ok">Saved ({{ $source === 'env' ? '.env' : 'this page' }})</span><div class="small text-muted mt-1">{{ $username }} · key {{ $keyHint }}</div>
                @else<span class="bs-pill bs-bad">Missing - fill the form below</span>@endif
            </div>
            <div class="col-md-3"><div class="text-muted small">Connection</div>
                @if($login === null)<span class="bs-pill bs-warn">Not tested</span>
                @elseif($login['ok'])<span class="bs-pill bs-ok">Logged in{{ !empty($login['data']['userWallet']['Balance']) || isset($login['data']['userWallet']['Balance']) ? ' · wallet ₹' . ($login['data']['userWallet']['Balance'] ?? 0) : '' }}</span>
                @else<span class="bs-pill bs-bad">{{ $login['error'] }}</span>@endif
            </div>
            <div class="col-md-3"><div class="text-muted small">Sellers</div>
                @if($enabled && $configured)<span class="bs-pill bs-ok">B2B orders ON</span>@else<span class="bs-pill bs-warn">B2B orders OFF</span>@endif
                <div class="small text-muted mt-1">{{ $couriers->where('approved', true)->count() }} of {{ $couriers->count() }} couriers approved</div>
            </div>
            <div class="col-md-3"><div class="text-muted small">Seller price</div><strong>Bigship cost + {{ rtrim(rtrim(number_format($markup, 2), '0'), '.') }}% + {{ (int) $gst }}% GST</strong></div>
        </div>

        <form method="POST" action="{{ route('admin.bigship.settings') }}" class="row g-2 align-items-end mt-3">
            @csrf
            <div class="col-md-3"><label class="form-label small">Bigship login email</label>
                <input name="username" type="email" class="form-control form-control-sm" value="{{ $source === 'admin' ? $username : '' }}" placeholder="{{ $source === 'env' ? 'Set in .env' : 'you@example.com' }}" autocomplete="off"></div>
            <div class="col-md-2"><label class="form-label small">Bigship password</label>
                <input name="password" type="password" class="form-control form-control-sm" placeholder="{{ $configured ? 'Saved - blank keeps it' : 'Password' }}" autocomplete="new-password"></div>
            <div class="col-md-3"><label class="form-label small">Access key (Bigship › API Integration)</label>
                <input name="access_key" type="password" class="form-control form-control-sm" placeholder="{{ $configured ? 'Saved - blank keeps it' : 'Paste access key' }}" autocomplete="off"></div>
            <div class="col-6 col-md-1"><label class="form-label small">Markup %</label>
                <input name="markup_percent" type="number" step="0.1" min="0" class="form-control form-control-sm" value="{{ $markup }}"></div>
            <div class="col-6 col-md-1"><label class="form-label small">For sellers</label>
                <select name="enabled" class="form-select form-select-sm">
                    <option value="1" @selected($enabled)>On</option>
                    <option value="0" @selected(!$enabled)>Off</option>
                </select></div>
            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Save &amp; test</button></div>
        </form>
        <p class="text-muted small mt-3 mb-0">
            Password and access key are stored encrypted. Bigship has no international API, so this covers domestic B2B only.
            Each courier Bigship offers appears below the first time a rate is fetched and stays <b>off</b> until you approve it. Sellers see only the courier name, never "Bigship".
        </p>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Couriers (approve to show to sellers)</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm bs-table mb-0">
                    <thead><tr><th>ID</th><th>Courier</th><th>Plan</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($couriers as $c)
                        <tr>
                            <td class="bs-mono">{{ $c->courier_id }}</td>
                            <td>{{ $c->name }}</td>
                            <td class="text-muted">{{ $c->plan_name }}</td>
                            <td>{!! $c->approved ? '<span class="bs-pill bs-ok">Live</span>' : '<span class="bs-pill bs-warn">Off</span>' !!}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.bigship.courier', $c->id) }}">@csrf
                                    <input type="hidden" name="approved" value="{{ $c->approved ? 0 : 1 }}">
                                    <button class="btn btn-sm {{ $c->approved ? 'btn-outline-danger' : 'btn-success' }}">{{ $c->approved ? 'Turn off' : 'Approve' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted p-3">No couriers yet - run a rate test to load them.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">B2B rate test</h6></div>
            <div class="card-body">
                @php($in = $rateResult['input'] ?? [])
                <form method="POST" action="{{ route('admin.bigship.rate-test') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-6 col-md-2"><label class="form-label small">From</label><input name="from" class="form-control form-control-sm" value="{{ $in['from'] ?? '110053' }}" required></div>
                    <div class="col-6 col-md-2"><label class="form-label small">To</label><input name="to" class="form-control form-control-sm" value="{{ $in['to'] ?? '' }}" required></div>
                    <div class="col-4 col-md-2"><label class="form-label small">Boxes</label><input name="count" type="number" class="form-control form-control-sm" value="{{ $in['count'] ?? 2 }}" required></div>
                    <div class="col-4 col-md-2"><label class="form-label small">L cm</label><input name="length" class="form-control form-control-sm" value="{{ $in['length'] ?? 40 }}" required></div>
                    <div class="col-4 col-md-2"><label class="form-label small">B cm</label><input name="breadth" class="form-control form-control-sm" value="{{ $in['breadth'] ?? 30 }}" required></div>
                    <div class="col-4 col-md-2"><label class="form-label small">H cm</label><input name="height" class="form-control form-control-sm" value="{{ $in['height'] ?? 30 }}" required></div>
                    <div class="col-4 col-md-2"><label class="form-label small">Kg / box</label><input name="weight" class="form-control form-control-sm" value="{{ $in['weight'] ?? 15 }}" required></div>
                    <div class="col-4 col-md-2"><label class="form-label small">Invoice ₹</label><input name="amount" class="form-control form-control-sm" value="{{ $in['amount'] ?? 20000 }}" required></div>
                    <div class="col-6 col-md-2"><label class="form-label small">Payment</label>
                        <select name="payment" class="form-select form-select-sm">
                            <option value="prepaid">Prepaid</option>
                            <option value="cod" @selected(($in['payment'] ?? '') === 'cod')>COD</option>
                        </select></div>
                    <div class="col-6 col-md-2"><button class="btn btn-sm btn-primary w-100">Check</button></div>
                </form>
                @if($rateResult)
                    <hr>
                    @if($rateResult['ok'] && count($rateResult['rates']))
                        <table class="table table-sm bs-table">
                            <thead><tr><th>Courier</th><th>TAT</th><th>Charged kg</th><th>Bigship cost</th><th>Seller price</th><th></th></tr></thead>
                            <tbody>
                            @foreach($rateResult['rates'] as $r)
                                <tr>
                                    <td>{{ $r['courier_name'] }} <span class="text-muted">#{{ $r['courier_id'] }}</span><div class="text-muted small">{{ $r['plan_name'] }}</div></td>
                                    <td>{{ $r['tat'] }}d</td>
                                    <td>{{ $r['charged_weight'] }}</td>
                                    <td>₹{{ number_format($r['cost'], 2) }}</td>
                                    <td><b>₹{{ number_format($r['price'], 2) }}</b></td>
                                    <td>{!! $r['approved'] ? '<span class="bs-pill bs-ok">Live</span>' : '<span class="bs-pill bs-warn">Off</span>' !!}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @elseif($rateResult['ok'])
                        <span class="bs-pill bs-warn">No courier for this route</span>
                    @else
                        <span class="bs-pill bs-bad">{{ $rateResult['error'] }}</span>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Latest B2B orders (all sellers)</h6></div>
    <div class="card-body p-0" style="overflow-x:auto">
        <table class="table table-sm bs-table mb-0">
            <thead><tr><th>Order</th><th>Seller</th><th>Route</th><th>Boxes / kg</th><th>Courier · AWB</th><th>Cost</th><th>Seller paid</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($orders as $o)
                <tr>
                    <td>{{ $o->order_ref }}<div class="text-muted small">{{ $o->created_at->format('d M, H:i') }}</div></td>
                    <td>{{ optional($o->seller)->name }}<div class="text-muted small">{{ optional($o->seller)->email }}</div></td>
                    <td>{{ $o->pickup['pincode'] ?? '' }} → {{ $o->consignee['pincode'] ?? '' }}</td>
                    <td>{{ $o->total_boxes }} / {{ rtrim(rtrim(number_format($o->total_weight, 2), '0'), '.') }}</td>
                    <td>{{ $o->courier_name ?: '—' }}<div class="bs-mono">{{ $o->awb }}</div></td>
                    <td>{{ $o->courier_cost ? '₹' . number_format($o->courier_cost, 2) : '—' }}</td>
                    <td>{{ $o->seller_charge ? '₹' . number_format($o->seller_charge, 2) : '—' }}</td>
                    <td><span class="bs-pill {{ in_array($o->status, ['delivered', 'booked', 'in_transit']) ? 'bs-ok' : ($o->status === 'draft' ? 'bs-warn' : 'bs-bad') }}">{{ $o->statusLabel() }}</span>
                        @if($o->status === 'draft' && $o->error)<div class="text-danger small" style="max-width:260px">{{ \Illuminate\Support\Str::limit($o->error, 120) }}</div>@endif</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-muted p-3">No B2B orders yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">API log (last 50 Bigship calls)</h6></div>
    <div class="card-body p-0" style="overflow-x:auto">
        <table class="table table-sm bs-table mb-0">
            <thead><tr><th>Time</th><th>Endpoint</th><th>HTTP</th><th>Result</th><th>ms</th><th>Order</th><th></th></tr></thead>
            <tbody>
            @forelse($logs as $l)
                <tr>
                    @php($ok = $l->success ?? (($l->http_status ?? $l->status_code ?? 0) >= 200 && ($l->http_status ?? $l->status_code ?? 0) < 300))
                    <td class="text-nowrap">{{ !empty($l->created_at) ? \Carbon\Carbon::parse($l->created_at)->format('d M H:i:s') : '' }}</td>
                    <td class="bs-mono">{{ $l->endpoint ?? $l->url ?? '' }}</td>
                    <td>{{ $l->http_status ?? $l->status_code ?? '' }}</td>
                    <td>{!! $ok ? '<span class="bs-pill bs-ok">OK</span>' : '<span class="bs-pill bs-bad">Fail</span>' !!}</td>
                    <td>{{ $l->duration_ms ?? '' }}</td>
                    <td>{{ $l->order_id ?? $l->awb ?? '' }}</td>
                    <td><a href="{{ route('admin.bigship.log', $l->id) }}" target="_blank">View</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted p-3">No calls yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
