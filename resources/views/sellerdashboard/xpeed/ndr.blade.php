@extends('layouts.sellerdash')

@section('content')
@php
    use App\Support\Xpeed\Kit;
    $actLabel = ['re-attempt' => 'Re-attempt', 'change-address' => 'New address', 'change-phone' => 'New phone', 'rto' => 'Return to me (RTO)'];
    $stLabel = ['sent_to_courier' => ['Sent to courier', 'ok'], 'needs_manual' => ['With Shipxpeed ops', 'warn'], 'failed' => ['Courier rejected', 'bad'],
        'actioned_by_ops' => ['Raised by ops', 'ok'], 'processing' => ['Processing', 'info'], 'awaiting_buyer' => ['Waiting for buyer', 'muted']];
    $list = $tab === 'taken' ? $taken : $pending;
    $minDate = now()->addDay()->toDateString(); $maxDate = now()->addDays(5)->toDateString();
@endphp
@include('sellerdashboard.xpeed._style')
<div class="pc-container xp">
<div class="pc-content">
    <div class="xp-head">
        <div>
            <h1><i class="ti ti-alert-triangle"></i> NDR — failed deliveries</h1>
            <p>Shipments the courier could not deliver. Tell us what to do: re-attempt, give a new address or phone, or return it. Requests go straight to couriers that support it; the rest are raised by our ops team the same day.</p>
        </div>
        <div class="d-flex gap-3">
            <div><div class="stat">{{ $pending->count() }}</div><div class="stat-l" style="color:#c9d4ea">Action needed</div></div>
            <div><div class="stat">{{ $taken->count() }}</div><div class="stat-l" style="color:#c9d4ea">Action taken</div></div>
        </div>
    </div>

    <div class="xp-card">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div class="btn-group btn-group-sm">
                <a href="{{ route('xp.ndr', ['tab' => 'action']) }}" class="btn {{ $tab === 'action' ? 'btn-xp' : 'btn-outline-secondary' }}">Action needed ({{ $pending->count() }})</a>
                <a href="{{ route('xp.ndr', ['tab' => 'taken']) }}" class="btn {{ $tab === 'taken' ? 'btn-xp' : 'btn-outline-secondary' }}">Action taken ({{ $taken->count() }})</a>
                <a href="{{ route('xp.ndr', ['tab' => 'history']) }}" class="btn {{ $tab === 'history' ? 'btn-xp' : 'btn-outline-secondary' }}">History</a>
            </div>
            <form class="d-flex gap-2" method="GET" action="{{ route('xp.ndr') }}">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="AWB or order no." style="max-width:200px">
                <button class="btn btn-sm btn-outline-secondary">Search</button>
            </form>
        </div>

        @if($tab === 'history')
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>When</th><th>AWB</th><th>Action</th><th>Details</th><th>By</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($history as $h)
                        @php [$sl, $sc] = $stLabel[$h->status] ?? [ucfirst(str_replace('_', ' ', $h->status)), 'muted']; @endphp
                        <tr>
                            <td class="text-nowrap">{{ $h->responded_at ? \Carbon\Carbon::parse($h->responded_at)->format('d M, h:i A') : '—' }}</td>
                            <td class="mono">{{ $h->awb_number ?: ($awbOf[$h->order_id] ?? '') }}</td>
                            <td>{{ $actLabel[$h->action] ?? $h->action }}</td>
                            <td class="small">
                                @if($h->reattempt_date) Re-attempt on {{ \Carbon\Carbon::parse($h->reattempt_date)->format('d M') }}<br>@endif
                                @if($h->address) {{ \Illuminate\Support\Str::limit($h->address, 60) }}<br>@endif
                                @if($h->phone) Phone {{ $h->phone }} @endif
                            </td>
                            <td>{{ ucfirst($h->source ?? (str_starts_with((string) $h->remark, 'Buyer') ? 'buyer' : 'seller')) }}</td>
                            <td><span class="pill {{ $sc }}">{{ $sl }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="xp-empty"><i class="ti ti-history"></i>No NDR actions yet.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @else
            @if($list->isEmpty())
                <div class="xp-empty"><i class="ti ti-mood-happy"></i>{{ $tab === 'taken' ? 'No actions taken in the last 90 days.' : 'No failed deliveries need your action right now.' }}</div>
            @else
                @if($tab === 'action')
                <form id="ndrBulk" method="POST" action="{{ route('xp.ndr.bulk') }}" class="d-flex gap-2 align-items-center flex-wrap mb-2">
                    @csrf
                    <span class="small text-muted">With selected:</span>
                    <button name="action" value="re-attempt" class="btn btn-sm btn-xp" onclick="return xpBulkPick()">Re-attempt tomorrow</button>
                    <button name="action" value="rto" class="btn btn-sm btn-outline-danger" onclick="return xpBulkPick() && confirm('Return all selected shipments to you?')">Return to me (RTO)</button>
                </form>
                @endif
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr>
                            @if($tab === 'action')<th style="width:28px"><input type="checkbox" onclick="document.querySelectorAll('.xp-ndr-cb').forEach(c => c.checked = this.checked)"></th>@endif
                            <th>Shipment</th><th>Buyer</th><th>Payment</th><th>Last action</th><th class="text-end">Take action</th>
                        </tr></thead>
                        <tbody>
                        @foreach($list as $o)
                            @php
                                $c = Kit::consignee($o);
                                $last = optional($actions->get($o->id))->first();
                                $risk = \App\Support\Xpeed\RtoRisk::score($o);
                            @endphp
                            <tr>
                                @if($tab === 'action')<td><input type="checkbox" class="xp-ndr-cb" form="ndrBulk" name="order_ids[]" value="{{ $o->id }}"></td>@endif
                                <td>
                                    <div class="mono">{{ $o->awb_number }}</div>
                                    <div class="small text-muted">{{ Kit::courierLabel($o) }} · #{{ Kit::ref($o) }}</div>
                                    <div class="small text-muted">Updated {{ optional($o->updated_at)->diffForHumans() }}</div>
                                </td>
                                <td>
                                    <div>{{ $c['name'] ?? '—' }}</div>
                                    <div class="small text-muted">{{ $c['phone'] ?? '' }}</div>
                                    <div class="small text-muted">{{ $c['city'] ?? '' }} {{ $c['pincode'] ?? '' }}</div>
                                </td>
                                <td>
                                    <span class="pill {{ strtolower($o->payment_type) === 'cod' ? 'warn' : 'info' }}">{{ strtoupper($o->payment_type) }}</span>
                                    <div class="small mt-1">{{ Kit::money($o->order_amount) }}</div>
                                    <span class="pill risk-{{ $risk['level'] }} mt-1" title="{{ implode(' · ', $risk['reasons']) }}">RTO risk {{ $risk['score'] }}</span>
                                </td>
                                <td class="small">
                                    @if($last)
                                        @php [$sl, $sc] = $stLabel[$last->status] ?? [ucfirst($last->status), 'muted']; @endphp
                                        {{ $actLabel[$last->action] ?? $last->action }}<br>
                                        <span class="pill {{ $sc }}">{{ $sl }}</span>
                                        <div class="text-muted">{{ $last->responded_at ? \Carbon\Carbon::parse($last->responded_at)->diffForHumans() : '' }}</div>
                                    @else
                                        <span class="text-muted">None yet</span>
                                    @endif
                                </td>
                                <td class="text-end" style="min-width:290px">
                                    <details>
                                        <summary class="btn btn-sm btn-outline-primary">Choose action</summary>
                                        <form method="POST" action="{{ route('xp.ndr.action') }}" class="text-start mt-2 p-2 rounded" style="border:1px solid rgba(120,130,150,.25)">
                                            @csrf
                                            <input type="hidden" name="order_id" value="{{ $o->id }}">
                                            <select name="action" class="form-select form-select-sm mb-2 xp-ndr-act" onchange="xpNdrToggle(this)">
                                                <option value="re-attempt">Re-attempt delivery</option>
                                                <option value="change-address">Re-attempt at a new address</option>
                                                <option value="change-phone">Re-attempt with a new phone</option>
                                                <option value="rto">Return to me (RTO)</option>
                                            </select>
                                            <div class="xp-f xp-f-date mb-2"><label class="form-label">Re-attempt date</label><input type="date" name="reattempt_date" class="form-control form-control-sm" min="{{ $minDate }}" max="{{ $maxDate }}" value="{{ $minDate }}"></div>
                                            <div class="xp-f xp-f-address mb-2" style="display:none"><label class="form-label">New full address</label><textarea name="address" rows="2" class="form-control form-control-sm" minlength="10" maxlength="300">{{ $c['address'] ?? '' }}</textarea>
                                                <input name="landmark" class="form-control form-control-sm mt-1" placeholder="Landmark (optional)" maxlength="120"></div>
                                            <div class="xp-f xp-f-phone mb-2" style="display:none"><label class="form-label">New mobile</label><input name="phone" class="form-control form-control-sm" pattern="[6-9][0-9]{9}" maxlength="10" placeholder="10-digit mobile"></div>
                                            <input name="remark" class="form-control form-control-sm mb-2" maxlength="200" placeholder="Note for the courier (optional)">
                                            <button class="btn btn-sm btn-xp w-100">Submit</button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
</div>
</div>
<script>
function xpNdrToggle(sel) {
    var f = sel.closest('form'), v = sel.value;
    f.querySelector('.xp-f-date').style.display = v === 'rto' ? 'none' : '';
    f.querySelector('.xp-f-address').style.display = v === 'change-address' ? '' : 'none';
    f.querySelector('.xp-f-phone').style.display = v === 'change-phone' ? '' : 'none';
    f.querySelector('[name=address]').required = v === 'change-address';
    f.querySelector('[name=phone]').required = v === 'change-phone';
}
function xpBulkPick() {
    if (!document.querySelector('.xp-ndr-cb:checked')) { alert('Select at least one shipment.'); return false; }
    return true;
}
</script>
@endsection
