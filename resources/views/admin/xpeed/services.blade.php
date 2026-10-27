@extends('layouts.app')

@section('content')
@php
    use App\Support\Xpeed\Kit;
    $claimSt = ['pending' => ['Pending', 'warn'], 'approved' => ['Approved', 'ok'], 'rejected' => ['Rejected', 'bad']];
    $ecSt = ['requested' => ['Requested', 'warn'], 'active' => ['Active', 'ok'], 'rejected' => ['Rejected', 'bad'], 'stopped' => ['Stopped', 'muted']];
    $set = fn ($k) => $settings[$k] ?? '';
@endphp
<div class="xa">
@include('admin.xpeed._style')
@unless($ready)<div class="alert alert-warning">Run the Control Suite migration to enable this page.</div>@endunless

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Shipment claims</h5>
                <div class="small">Protection fees this month: <b>{{ Kit::money($month->f ?? 0) }}</b> ({{ $month->c ?? 0 }} shipments) · Claims paid this month: <b>{{ Kit::money($paid) }}</b></div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Seller</th><th>AWB</th><th>Claim</th><th>Amounts</th><th>Status</th><th style="min-width:230px">Decision</th></tr></thead>
                        <tbody>
                        @forelse($claims as $c)
                            @php [$cl, $cc] = $claimSt[$c->status] ?? [$c->status, 'muted']; $proofs = json_decode((string) $c->proofs, true) ?: []; @endphp
                            <tr>
                                <td>{{ $sellers[$c->seller_id] ?? '#' . $c->seller_id }}<div class="small text-muted">{{ \Carbon\Carbon::parse($c->created_at)->format('d M Y') }}</div></td>
                                <td class="mono">{{ $c->awb }}@if($c->protected)<div><span class="pill info">Protected</span></div>@endif</td>
                                <td class="small" style="max-width:280px"><b>{{ ucfirst(str_replace('_', ' ', $c->type)) }}</b> — {{ $c->description }}
                                    <div>@foreach($proofs as $i => $p)<a target="_blank" href="{{ route('admin.xp.claims.proof', [$c->id, $i]) }}">file {{ $i + 1 }}</a> @endforeach</div></td>
                                <td class="small">Claimed {{ Kit::money($c->claim_amount) }}<br>Order value {{ Kit::money($c->declared_value) }}
                                    @if($c->approved_amount !== null)<br><b>Paid {{ Kit::money($c->approved_amount) }}</b>@endif</td>
                                <td><span class="pill {{ $cc }}">{{ $cl }}</span>@if($c->admin_remark)<div class="small text-muted">{{ $c->admin_remark }}</div>@endif</td>
                                <td>
                                    @if($c->status === 'pending')
                                        <form method="POST" action="{{ route('admin.xp.claims.review', $c->id) }}" class="row g-1">
                                            @csrf
                                            <div class="col-6"><input type="number" step="0.01" min="0" name="amount" class="form-control form-control-sm" value="{{ (float) $c->claim_amount }}" title="Amount to credit"></div>
                                            <div class="col-6"><input name="remark" class="form-control form-control-sm" placeholder="Remark" maxlength="500"></div>
                                            <div class="col-6"><button name="decision" value="approve" class="btn btn-sm btn-success w-100" onclick="return confirm('Credit this amount to the seller wallet?')">Approve</button></div>
                                            <div class="col-6"><button name="decision" value="reject" class="btn btn-sm btn-outline-danger w-100">Reject</button></div>
                                        </form>
                                        @if($c->protected)<div class="small text-muted mt-1">Capped at the lower of order value and {{ Kit::money($set('protection_max_cover')) }}.</div>@endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No claims yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Early COD &amp; protection by seller</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Seller</th><th>Protection</th><th>Early COD</th><th>Due now</th><th style="min-width:260px">Action</th></tr></thead>
                        <tbody>
                        @forelse($services as $s)
                            @php [$el, $ec] = $ecSt[$s->early_cod_status] ?? ['—', 'muted']; $due = $earlyDue[$s->seller_id] ?? null; @endphp
                            <tr>
                                <td>{{ $sellers[$s->seller_id] ?? '#' . $s->seller_id }}</td>
                                <td><span class="pill {{ $s->protection_on ? 'ok' : 'muted' }}">{{ $s->protection_on ? 'On' : 'Off' }}</span></td>
                                <td>@if($s->early_cod_status)<span class="pill {{ $ec }}">{{ $el }}</span> D+{{ $plans[$s->early_cod_plan]['days'] ?? '?' }} @else — @endif</td>
                                <td class="small">@if($due){{ $due['count'] }} orders · {{ Kit::money($due['amount']) }}<br>fee {{ Kit::money($due['fee']) }} ({{ $due['pct'] }}%)@else — @endif</td>
                                <td>
                                    @if($s->early_cod_status === 'requested')
                                        <form method="POST" action="{{ route('admin.xp.earlycod.decide', $s->seller_id) }}" class="d-flex gap-1">@csrf
                                            <input name="note" class="form-control form-control-sm" placeholder="Note (optional)">
                                            <button name="decision" value="approve" class="btn btn-sm btn-success">Approve</button>
                                            <button name="decision" value="reject" class="btn btn-sm btn-outline-danger">Reject</button></form>
                                    @elseif($s->early_cod_status === 'active')
                                        @if($due && $due['count'])
                                            <a class="btn btn-sm btn-outline-secondary py-0" href="{{ route('admin.xp.earlycod.export', $s->seller_id) }}">Download due list</a>
                                            <form method="POST" action="{{ route('admin.xp.earlycod.remit', $s->seller_id) }}" class="d-flex gap-1 mt-1" onsubmit="return confirm('Mark these COD orders as remitted and debit the fee?')">@csrf
                                                <input name="utr" required class="form-control form-control-sm" placeholder="Bank UTR after paying">
                                                <button class="btn btn-sm btn-primary">Mark remitted</button></form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.xp.earlycod.decide', $s->seller_id) }}" class="mt-1">@csrf<button name="decision" value="stop" class="btn btn-sm btn-link p-0 text-danger">Stop early COD</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No seller has switched on protection or asked for early COD yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="small text-muted mt-2">Flow: pay the due COD to the seller's bank (download the list for amounts and bank details), then enter the UTR and press "Mark remitted" — those orders become Paid and the early-COD fee is debited from the seller's wallet.</div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Settings</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.xp.services.settings') }}" class="row g-2">
                    @csrf
                    <div class="col-12 fw-semibold">Shipment protection</div>
                    <div class="col-12"><select name="protection_enabled" class="form-select form-select-sm"><option value="1" @selected($set('protection_enabled') === '1')>Open for sellers</option><option value="0" @selected($set('protection_enabled') !== '1')>Closed (no new sign-ups, no new fees)</option></select></div>
                    <div class="col-6"><label class="form-label small">Fee % of order value</label><input name="protection_fee_pct" type="number" step="0.01" class="form-control form-control-sm" value="{{ $set('protection_fee_pct') }}"></div>
                    <div class="col-6"><label class="form-label small">Minimum fee ₹</label><input name="protection_min_fee" type="number" step="0.01" class="form-control form-control-sm" value="{{ $set('protection_min_fee') }}"></div>
                    <div class="col-6"><label class="form-label small">Protect orders from ₹</label><input name="protection_min_value" type="number" step="0.01" class="form-control form-control-sm" value="{{ $set('protection_min_value') }}"></div>
                    <div class="col-6"><label class="form-label small">Max payout ₹</label><input name="protection_max_cover" type="number" step="0.01" class="form-control form-control-sm" value="{{ $set('protection_max_cover') }}"></div>
                    <div class="col-12"><label class="form-label small">Claim window (days after booking)</label><input name="claim_window_days" type="number" class="form-control form-control-sm" value="{{ $set('claim_window_days') }}"></div>
                    <div class="col-12 fw-semibold mt-2">Early COD</div>
                    <div class="col-12"><select name="early_cod_enabled" class="form-select form-select-sm"><option value="1" @selected($set('early_cod_enabled') === '1')>Open for requests</option><option value="0" @selected($set('early_cod_enabled') !== '1')>Closed</option></select></div>
                    <div class="col-4"><label class="form-label small">D+2 fee %</label><input name="early_cod_d2_pct" type="number" step="0.01" class="form-control form-control-sm" value="{{ $set('early_cod_d2_pct') }}"></div>
                    <div class="col-4"><label class="form-label small">D+3 fee %</label><input name="early_cod_d3_pct" type="number" step="0.01" class="form-control form-control-sm" value="{{ $set('early_cod_d3_pct') }}"></div>
                    <div class="col-4"><label class="form-label small">D+4 fee %</label><input name="early_cod_d4_pct" type="number" step="0.01" class="form-control form-control-sm" value="{{ $set('early_cod_d4_pct') }}"></div>
                    <div class="col-12 mt-2"><button class="btn btn-primary btn-sm w-100" @disabled(!$ready)>Save settings</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
