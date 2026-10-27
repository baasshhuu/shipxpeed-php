@extends('layouts.app')

@section('content')
@php
    $money = fn ($v) => '₹' . number_format((float) $v, 2);
    $pillClass = [
        'booked' => 'sr-muted', 'picked_up' => 'sr-info', 'in_transit' => 'sr-info', 'out_for_delivery' => 'sr-info',
        'ndr' => 'sr-warn', 'delivered' => 'sr-ok', 'rto' => 'sr-bad', 'rto_delivered' => 'sr-bad',
        'cancelled' => 'sr-muted', 'lost' => 'sr-bad',
    ];
    $bs = $summary['by_status'];
    $cards = [
        ['Shipments', number_format($summary['count']), null],
        ['Delivered', number_format($bs['delivered'] ?? 0), 'delivered'],
        ['In transit', number_format(($bs['picked_up'] ?? 0) + ($bs['in_transit'] ?? 0) + ($bs['out_for_delivery'] ?? 0)), 'in_transit'],
        ['NDR', number_format($bs['ndr'] ?? 0), 'ndr'],
        ['RTO', number_format(($bs['rto'] ?? 0) + ($bs['rto_delivered'] ?? 0)), 'rto'],
        ['Cancelled', number_format($bs['cancelled'] ?? 0), 'cancelled'],
    ];
    $q = request()->except('page');
@endphp
<style>
    .sr-pill{display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;white-space:nowrap}
    .sr-ok{background:rgba(34,197,94,.15);color:#22c55e}.sr-bad{background:rgba(239,68,68,.15);color:#f87171}
    .sr-warn{background:rgba(245,158,11,.15);color:#f59e0b}.sr-info{background:rgba(59,130,246,.15);color:#60a5fa}
    .sr-muted{background:rgba(148,163,184,.15);color:#94a3b8}
    .sr-card{border:1px solid rgba(148,163,184,.2);border-radius:10px;padding:12px 14px;height:100%;display:block;color:inherit;text-decoration:none}
    a.sr-card:hover{border-color:rgba(96,165,250,.6)}
    .sr-card.active{border-color:#60a5fa;background:rgba(96,165,250,.08)}
    .sr-num{font-size:1.35rem;font-weight:700;line-height:1.2}.sr-lbl{font-size:12px;opacity:.7}
    .sr-table td,.sr-table th{vertical-align:middle;font-size:13px;white-space:nowrap}
    .sr-table td.wrap{white-space:normal;min-width:160px}
    .sr-mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px}
    .sr-sub{font-size:11px;opacity:.65}
    .sr-neg{color:#22c55e}.sr-pos{color:#f87171}
</style>

<div class="card mb-3">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">📦 Shipment Report</h5>
        <a href="{{ route('shipment.report.export', $q) }}" class="btn btn-sm btn-success"><i class="fas fa-file-excel me-1"></i> Export Excel ({{ number_format($summary['count']) }} rows, all fields)</a>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('shipment.report') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-4 col-xl-2"><label class="form-label small mb-1">Seller</label>
                <select name="seller_id" class="form-select form-select-sm">
                    <option value="">All sellers</option>
                    @foreach ($sellers as $s)<option value="{{ $s->id }}" @selected($f['seller_id'] === $s->id)>{{ $s->name }} · #{{ $s->id }}</option>@endforeach
                </select></div>
            <div class="col-6 col-md-4 col-xl-2"><label class="form-label small mb-1">From (order date)</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $f['from'] }}"></div>
            <div class="col-6 col-md-4 col-xl-2"><label class="form-label small mb-1">To</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $f['to'] }}"></div>
            <div class="col-6 col-md-4 col-xl-2"><label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All statuses</option>
                    @foreach (\App\Support\ShipmentReport::STATUS_FILTERS as $k => $l)<option value="{{ $k }}" @selected($f['status'] === $k)>{{ $l }}</option>@endforeach
                </select></div>
            <div class="col-6 col-md-4 col-xl-1"><label class="form-label small mb-1">Payment</label>
                <select name="payment" class="form-select form-select-sm">
                    <option value="">All</option><option value="cod" @selected($f['payment'] === 'cod')>COD</option><option value="prepaid" @selected($f['payment'] === 'prepaid')>Prepaid</option>
                </select></div>
            <div class="col-6 col-md-4 col-xl-1"><label class="form-label small mb-1">Courier</label>
                <select name="courier" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach ($couriers as $c)<option value="{{ $c }}" @selected($f['courier'] === $c)>{{ $c }}</option>@endforeach
                </select></div>
            <div class="col-12 col-md-6 col-xl-2"><label class="form-label small mb-1">Search</label>
                <input type="search" name="q" class="form-control form-control-sm" placeholder="AWB, order no., buyer name/phone" value="{{ $f['q'] }}"></div>
            <input type="hidden" name="per_page" value="{{ $perPage }}">
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> Apply</button>
                <a href="{{ route('shipment.report') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-2 mb-3">
    @foreach ($cards as [$label, $num, $key])
        <div class="col-6 col-md-4 col-xl-2">
            <a class="sr-card {{ $key && $f['status'] === $key ? 'active' : '' }}" href="{{ route('shipment.report', array_merge($q, ['status' => $key])) }}">
                <div class="sr-num">{{ $num }}</div><div class="sr-lbl">{{ $label }}</div>
            </a>
        </div>
    @endforeach
</div>
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3"><div class="sr-card"><div class="sr-num">{{ $money($summary['charged']) }}</div><div class="sr-lbl">Shipping charged (incl. {{ $money($summary['gst']) }} GST)</div></div></div>
    <div class="col-6 col-md-3"><div class="sr-card"><div class="sr-num">{{ $money($summary['rto'] + $summary['weight'] + $summary['whatsapp']) }}</div><div class="sr-lbl">RTO {{ $money($summary['rto']) }} · weight {{ $money($summary['weight']) }} · WhatsApp {{ $money($summary['whatsapp']) }}</div></div></div>
    <div class="col-6 col-md-3"><div class="sr-card"><div class="sr-num">{{ $money($summary['refund']) }}</div><div class="sr-lbl">Refunded on cancellation</div></div></div>
    <div class="col-6 col-md-3"><div class="sr-card"><div class="sr-num">{{ $money($summary['net']) }}</div><div class="sr-lbl">Net billed to sellers · COD value {{ $money($summary['cod_value']) }}</div></div></div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover sr-table mb-0">
                <thead>
                    <tr>
                        <th>Order date</th><th>Order / AWB</th><th>Seller</th><th>Buyer</th><th>Courier</th><th>Payment</th>
                        <th>Weight</th><th>Status</th><th class="text-end">Charged</th><th class="text-end">Adjustments</th><th class="text-end">Net</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($rows as $r)
                    <tr>
                        <td>{{ $r['order_date'] }}@if($r['delivered_date'])<div class="sr-sub">Delivered {{ $r['delivered_date'] }}</div>@endif</td>
                        <td><div>{{ $r['order_number'] ?: '—' }}</div><div class="sr-mono">{{ $r['awb'] }}</div></td>
                        <td>{{ $r['seller_name'] ?: '—' }}<div class="sr-sub">#{{ $r['seller_id'] }}</div></td>
                        <td class="wrap">{{ $r['buyer_name'] }}<div class="sr-sub">{{ trim($r['buyer_city'] . ', ' . $r['buyer_state'] . ' ' . $r['buyer_pincode'], ', ') }}</div></td>
                        <td>{{ $r['courier'] ?: '—' }}@if($r['zone'])<div class="sr-sub">Zone {{ $r['zone'] }}</div>@endif</td>
                        <td>{{ $r['payment'] }}@if($r['cod_amount'] > 0)<div class="sr-sub">COD {{ $money($r['cod_amount']) }}</div>@endif</td>
                        <td>{{ $r['weight_kg'] !== null ? rtrim(rtrim(number_format($r['weight_kg'], 3), '0'), '.') . ' kg' : '—' }}</td>
                        <td><span class="sr-pill {{ $pillClass[$r['status_key']] ?? 'sr-muted' }}">{{ $r['status'] }}</span>
                            @if($r['courier_status'] && strcasecmp($r['courier_status'], $r['status']) !== 0)<div class="sr-sub">{{ $r['courier_status'] }}</div>@endif</td>
                        <td class="text-end">{{ $money($r['shipping_charged']) }}<div class="sr-sub">{{ $money($r['freight_taxable']) }} + {{ $money($r['gst']) }} GST</div></td>
                        <td class="text-end">
                            @php $adj = []; @endphp
                            @if($r['rto_charge'] > 0) @php $adj[] = '<span class="sr-pos">+' . e($money($r['rto_charge'])) . ' RTO</span>'; @endphp @endif
                            @if($r['weight_charge'] != 0) @php $adj[] = '<span class="' . ($r['weight_charge'] > 0 ? 'sr-pos">+' : 'sr-neg">') . e($money($r['weight_charge'])) . ' weight</span>'; @endphp @endif
                            @if($r['whatsapp_charge'] > 0) @php $adj[] = '<span class="sr-pos">+' . e($money($r['whatsapp_charge'])) . ' WhatsApp</span>'; @endphp @endif
                            @if($r['refund'] > 0) @php $adj[] = '<span class="sr-neg">−' . e($money($r['refund'])) . ' refund</span>'; @endphp @endif
                            {!! $adj ? implode('<br>', $adj) : '<span class="sr-sub">—</span>' !!}
                        </td>
                        <td class="text-end fw-semibold">{{ $money($r['net']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center py-4 opacity-75">No shipments match these filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-2">
            <div class="small opacity-75">
                Showing {{ $data->firstItem() ?? 0 }}–{{ $data->lastItem() ?? 0 }} of {{ number_format($data->total()) }} ·
                <form method="GET" class="d-inline">
                    @foreach (request()->except(['per_page', 'page']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                    <select name="per_page" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                        @foreach ([25, 50, 100, 200] as $n)<option value="{{ $n }}" @selected($perPage === $n)>{{ $n }} per page</option>@endforeach
                    </select>
                </form>
            </div>
            {{ $data->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
<p class="small opacity-75 mt-2">Charged = amount debited from the seller wallet at booking (freight + COD charge, 18% GST included). Adjustments: RTO charge, weight-dispute charges/refunds and WhatsApp alerts for this AWB, minus the cancellation refund. Net = what the seller paid for the shipment. Totals cover all filtered shipments, not just this page.</p>
@endsection
