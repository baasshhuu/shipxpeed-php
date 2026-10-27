@extends('layouts.app')

@section('content')
@php
    $pct = fn ($a, $b) => $b > 0 ? round($a * 100 / $b, 1) : 0;
@endphp
<div class="xa">
@include('admin.xpeed._style')
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><h5 class="mb-0">Courier performance</h5><div class="small text-muted">From shipments booked in the panel (cancelled excluded). Delivery % and RTO % are of closed shipments (delivered + RTO).</div></div>
        <form class="d-flex gap-2 flex-wrap">
            <select name="days" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach([7, 30, 90, 180] as $d)<option value="{{ $d }}" @selected($days === $d)>Last {{ $d }} days</option>@endforeach
            </select>
            <select name="seller_id" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width:220px">
                <option value="0">All sellers</option>
                @foreach($sellers as $id => $n)<option value="{{ $id }}" @selected($sellerId === (int) $id)>{{ $n }}</option>@endforeach
            </select>
        </form>
    </div>
    <div class="card-body">
        <div class="mb-3 small text-muted">{{ number_format($total) }} shipments analysed.</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Courier</th><th class="text-end">Shipments</th><th style="min-width:150px">Delivered %</th><th style="min-width:130px">RTO %</th><th class="text-end">NDR % (any attempt)</th><th class="text-end">In NDR now</th><th class="text-end">Open</th><th class="text-end">Open &gt; 10 days</th>@if($hasDelivered)<th class="text-end">Avg days to deliver</th>@endif<th class="text-end">COD share</th></tr></thead>
                <tbody>
                @forelse($stats as $label => $s)
                    @php $closed = $s['delivered'] + $s['rto']; $dp = $pct($s['delivered'], $closed); $rp = $pct($s['rto'], $closed); @endphp
                    <tr>
                        <td class="fw-semibold">
                            <details><summary>{{ $label }}</summary>
                                <table class="table table-sm mt-1 mb-0" style="max-width:360px">
                                    <thead><tr><th>Zone</th><th class="text-end">Shipments</th><th class="text-end">Delivered %</th><th class="text-end">RTO %</th></tr></thead>
                                    @foreach(collect($zoneStats[$label] ?? [])->sortKeys() as $z => $zs)
                                        @php $zc = $zs['delivered'] + $zs['rto']; @endphp
                                        <tr><td>{{ $z }}</td><td class="text-end">{{ $zs['total'] }}</td><td class="text-end">{{ $pct($zs['delivered'], $zc) }}%</td><td class="text-end">{{ $pct($zs['rto'], $zc) }}%</td></tr>
                                    @endforeach
                                </table>
                            </details>
                        </td>
                        <td class="text-end">{{ number_format($s['total']) }}</td>
                        <td><div class="d-flex align-items-center gap-2"><div class="bar flex-grow-1"><span style="width:{{ $dp }}%;background:#22a565"></span></div><span>{{ $closed ? $dp . '%' : '—' }}</span></div></td>
                        <td><div class="d-flex align-items-center gap-2"><div class="bar flex-grow-1"><span style="width:{{ min(100, $rp * 2) }}%;background:{{ $rp >= 20 ? '#d64545' : ($rp >= 10 ? '#e0a020' : '#22a565') }}"></span></div><span>{{ $closed ? $rp . '%' : '—' }}</span></div></td>
                        <td class="text-end">{{ $pct($s['ndr_ever'], $s['total']) }}%</td>
                        <td class="text-end">{{ $s['ndr_now'] }}</td>
                        <td class="text-end">{{ $s['open'] }}</td>
                        <td class="text-end">@if($s['stuck'])<span class="pill warn">{{ $s['stuck'] }}</span>@else 0 @endif</td>
                        @if($hasDelivered)<td class="text-end">{{ $s['days_n'] ? number_format($s['days_sum'] / $s['days_n'], 1) : '—' }}</td>@endif
                        <td class="text-end">{{ $pct($s['cod'], $s['total']) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No shipments in this period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="small text-muted mt-2">Tip: a courier with RTO above 20% or many "open &gt; 10 days" shipments in a zone is a good candidate to switch off for that zone on the Courier Status page.</div>
    </div>
</div>
</div>
@endsection
