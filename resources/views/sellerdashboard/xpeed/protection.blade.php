@extends('layouts.sellerdash')

@section('content')
@php
    use App\Support\Xpeed\Kit;
    $claimSt = ['pending' => ['Under review', 'warn'], 'approved' => ['Approved', 'ok'], 'rejected' => ['Rejected', 'bad']];
    $ecSt = ['requested' => ['Request under review', 'warn'], 'active' => ['Active', 'ok'], 'rejected' => ['Not approved', 'bad'], 'stopped' => ['Stopped', 'muted']];
    $planName = fn ($k) => 'D+' . ($plans[$k]['days'] ?? '?');
@endphp
@include('sellerdashboard.xpeed._style')
<div class="pc-container xp">
<div class="pc-content">
    <div class="xp-head">
        <div>
            <h1><i class="ti ti-shield-check"></i> Protection &amp; early COD</h1>
            <p>Protect high-value shipments against loss and damage, file claims, and get your COD money faster.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="xp-card">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div><h2>Shipment protection</h2><p class="xp-sub">Covers lost or damaged shipments up to the order value (max {{ Kit::money($cfg['max_cover']) }}).</p></div>
                    <span class="pill {{ $svc->protection_on ? 'ok' : 'muted' }}">{{ $svc->protection_on ? 'ON' : 'OFF' }}</span>
                </div>
                <ul class="small ps-3 mb-3">
                    <li>Applies to every shipment of <b>{{ Kit::money($cfg['min_value']) }}</b> or more booked while it's on.</li>
                    <li>Fee: <b>{{ rtrim(rtrim(number_format($cfg['pct'], 2), '0'), '.') }}% of order value</b>, minimum {{ Kit::money($cfg['min']) }} per shipment, debited from your wallet. Refunded if you cancel the shipment.</li>
                    <li>Protected claims are reviewed first and paid into your wallet once approved.</li>
                </ul>
                @if($month)<div class="small text-muted mb-2">This month: {{ $month->c }} shipment(s) protected · fees {{ Kit::money($month->f) }}</div>@endif
                <form method="POST" action="{{ route('xp.protection.save') }}">
                    @csrf
                    @if($svc->protection_on)
                        <button name="on" value="0" class="btn btn-sm btn-outline-secondary" @disabled(!$ready)>Turn off</button>
                    @else
                        <button name="on" value="1" class="btn btn-sm btn-xp" @disabled(!$ready || !$cfg['enabled'])>Protect my shipments</button>
                        @unless($cfg['enabled'])<div class="small text-muted mt-1">Not open for new sign-ups right now.</div>@endunless
                    @endif
                </form>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="xp-card">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div><h2>Early COD remittance</h2><p class="xp-sub">Get COD paid to your bank a few days after delivery instead of the normal cycle, for a small fee on the amount remitted early.</p></div>
                    @if($svc->early_cod_status)
                        @php [$el, $ec] = $ecSt[$svc->early_cod_status] ?? [$svc->early_cod_status, 'muted']; @endphp
                        <span class="pill {{ $ec }}">{{ $el }}</span>
                    @endif
                </div>
                @if($svc->early_cod_status === 'active')
                    <p class="mb-2">Plan <b>{{ $planName($svc->early_cod_plan) }}</b> · {{ $plans[$svc->early_cod_plan]['pct'] ?? 0 }}% fee. Due now: <b>{{ Kit::money($dueAmount) }}</b></p>
                @elseif($svc->early_cod_status === 'rejected' && $svc->early_cod_note)
                    <p class="small text-muted">{{ $svc->early_cod_note }}</p>
                @endif
                <form method="POST" action="{{ route('xp.protection.earlycod') }}" class="row g-2">
                    @csrf
                    @foreach($plans as $k => $p)
                        <div class="col-4">
                            <label class="d-block p-2 rounded text-center" style="border:1px solid rgba(120,130,150,.25);cursor:pointer">
                                <input type="radio" name="plan" value="{{ $k }}" @checked($svc->early_cod_plan === $k || ($loop->first && !$svc->early_cod_plan))>
                                <div class="fw-bold">{{ $planName($k) }}</div>
                                <div class="small text-muted">{{ $p['pct'] }}% fee</div>
                            </label>
                        </div>
                    @endforeach
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-sm btn-xp" @disabled(!$ready || !$cfg['early'] || $svc->early_cod_status === 'requested')>{{ $svc->early_cod_status === 'active' ? 'Change plan' : 'Request early COD' }}</button>
                        @if(in_array($svc->early_cod_status, ['active', 'requested']))
                            <button name="plan" value="stop" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Stop early COD?')">Stop</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="xp-card">
                <h2>File a claim</h2>
                <p class="xp-sub">Lost in transit, damaged or items missing. Add photos / unboxing video for faster approval.</p>
                <form method="POST" action="{{ route('xp.protection.claim') }}" enctype="multipart/form-data" class="row g-2">
                    @csrf
                    <div class="col-7"><label class="form-label">AWB</label><input name="awb" required maxlength="60" class="form-control form-control-sm mono" value="{{ old('awb') }}"></div>
                    <div class="col-5"><label class="form-label">Type</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="lost">Lost</option><option value="damaged">Damaged</option><option value="missing_item">Item missing</option>
                        </select></div>
                    <div class="col-12"><label class="form-label">What happened</label><textarea name="description" rows="3" required minlength="15" maxlength="1500" class="form-control form-control-sm">{{ old('description') }}</textarea></div>
                    <div class="col-5"><label class="form-label">Claim amount ₹</label><input type="number" name="claim_amount" min="1" step="0.01" required class="form-control form-control-sm" value="{{ old('claim_amount') }}"></div>
                    <div class="col-7"><label class="form-label">Proof (up to 5 files, 10 MB each)</label><input type="file" name="proofs[]" multiple accept="image/*,application/pdf,video/mp4,video/quicktime" class="form-control form-control-sm"></div>
                    <div class="col-12"><button class="btn btn-sm btn-xp" @disabled(!$ready)>Submit claim</button></div>
                </form>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="xp-card">
                <h2>My claims</h2>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>AWB</th><th>Type</th><th>Claimed</th><th>Status</th><th>Settled</th></tr></thead>
                        <tbody>
                        @forelse($claims as $c)
                            @php [$cl, $cc] = $claimSt[$c->status] ?? [$c->status, 'muted']; $proofs = json_decode((string) $c->proofs, true) ?: []; @endphp
                            <tr>
                                <td class="mono">{{ $c->awb }}@if($c->protected)<div><span class="pill info">Protected</span></div>@endif</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $c->type)) }}
                                    <div class="small">@foreach($proofs as $i => $p)<a target="_blank" href="{{ route('xp.protection.claim.proof', [$c->id, $i]) }}">file {{ $i + 1 }}</a> @endforeach</div></td>
                                <td>{{ Kit::money($c->claim_amount) }}<div class="small text-muted">{{ \Carbon\Carbon::parse($c->created_at)->format('d M Y') }}</div></td>
                                <td><span class="pill {{ $cc }}">{{ $cl }}</span>@if($c->admin_remark)<div class="small text-muted">{{ $c->admin_remark }}</div>@endif</td>
                                <td>{{ $c->approved_amount !== null ? Kit::money($c->approved_amount) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted small text-center py-3">No claims yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="xp-card">
                <h2>Protected shipments</h2>
                <div class="table-responsive" style="max-height:320px;overflow:auto">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>AWB</th><th>Value</th><th>Fee</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                        @forelse($charges as $ch)
                            <tr><td class="mono">{{ $ch->awb }}</td><td>{{ Kit::money($ch->declared_value) }}</td><td>{{ Kit::money($ch->fee) }}</td>
                                <td><span class="pill {{ $ch->status === 'charged' ? 'ok' : 'muted' }}">{{ $ch->status === 'charged' ? 'Protected' : 'Refunded' }}</span></td>
                                <td class="small">{{ \Carbon\Carbon::parse($ch->created_at)->format('d M Y') }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="text-muted small text-center py-3">Nothing protected yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
