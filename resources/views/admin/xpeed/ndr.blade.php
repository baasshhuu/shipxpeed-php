@extends('layouts.app')

@section('content')
@php
    use App\Support\Xpeed\Kit;
    $actLabel = ['re-attempt' => 'Re-attempt', 'change-address' => 'New address', 'change-phone' => 'New phone', 'rto' => 'RTO'];
    $stLabel = ['sent_to_courier' => ['Sent to courier', 'ok'], 'needs_manual' => ['Needs ops', 'warn'], 'failed' => ['Courier rejected', 'bad'],
        'actioned_by_ops' => ['Done by ops', 'ok'], 'processing' => ['Processing', 'info'], 'awaiting_buyer' => ['Waiting for buyer', 'muted']];
@endphp
<div class="xa">
@include('admin.xpeed._style')
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><h5 class="mb-0">NDR desk</h5><div class="small text-muted">Every NDR instruction from buyers (WhatsApp link) and sellers. Couriers without an NDR API land here as "Needs ops" — raise them on the courier portal, then mark done.</div></div>
        <div class="d-flex gap-4">
            <div><div class="stat">{{ $awaiting }}</div><div class="stat-l">Shipments in NDR now</div></div>
            <div><div class="stat">{{ ($counts['needs_manual'] ?? 0) + ($counts['failed'] ?? 0) }}</div><div class="stat-l">Open for ops (30 d)</div></div>
            <div><div class="stat">{{ $counts['sent_to_courier'] ?? 0 }}</div><div class="stat-l">Auto-sent (30 d)</div></div>
        </div>
    </div>
    <div class="card-body">
        @unless($ready)<div class="alert alert-warning">Run the Control Suite migration to enable this page.</div>@endunless
        <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
            <div class="btn-group btn-group-sm">
                @foreach(['open' => 'Open', 'sent_to_courier' => 'Sent to courier', 'actioned_by_ops' => 'Done by ops', 'all' => 'All'] as $k => $l)
                    <a href="{{ route('admin.xp.ndr', ['status' => $k]) }}" class="btn {{ $status === $k ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $l }}</a>
                @endforeach
            </div>
            <form class="d-flex gap-2"><input type="hidden" name="status" value="{{ $status }}"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="AWB"><button class="btn btn-sm btn-outline-secondary">Search</button></form>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>When</th><th>Seller</th><th>Shipment</th><th>Instruction</th><th>From</th><th>Status</th><th class="text-end">Ops</th></tr></thead>
                <tbody>
                @forelse($rows as $r)
                    @php $o = $orders[$r->order_id] ?? null; [$sl, $sc] = $stLabel[$r->status] ?? [$r->status, 'muted']; @endphp
                    <tr>
                        <td class="text-nowrap small">{{ $r->responded_at ? \Carbon\Carbon::parse($r->responded_at)->format('d M, h:i A') : '' }}</td>
                        <td>{{ $sellers[$r->seller_id] ?? ('#' . $r->seller_id) }}</td>
                        <td><span class="mono">{{ $r->awb_number }}</span><div class="small text-muted">{{ $o ? Kit::courierLabel($o) . ' · ' . strtoupper($o->courier_id) : '' }}</div></td>
                        <td class="small">
                            <b>{{ $actLabel[$r->action] ?? $r->action }}</b>
                            @if($r->reattempt_date) on {{ \Carbon\Carbon::parse($r->reattempt_date)->format('d M') }}@endif
                            @if($r->address)<div>{{ $r->address }} {{ $r->landmark }}</div>@endif
                            @if($r->phone)<div>Phone: {{ $r->phone }}</div>@endif
                            @if($r->remark)<div class="text-muted">{{ $r->remark }}</div>@endif
                        </td>
                        <td>{{ ucfirst($r->source ?? (str_starts_with((string) $r->remark, 'Buyer') ? 'buyer' : '—')) }}</td>
                        <td><span class="pill {{ $sc }}">{{ $sl }}</span>
                            @if($r->courier_response)<details><summary class="small text-muted">response</summary><div class="mono" style="max-width:320px;white-space:pre-wrap;word-break:break-all">{{ \Illuminate\Support\Str::limit($r->courier_response, 600) }}</div></details>@endif
                            @if(!empty($r->ops_note))<div class="small text-muted">{{ $r->ops_note }}</div>@endif
                        </td>
                        <td class="text-end" style="min-width:200px">
                            @if(in_array($r->status, ['needs_manual', 'failed', 'processing']))
                                <form method="POST" action="{{ route('admin.xp.ndr.retry', $r->id) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-primary py-0">Retry API</button></form>
                                <form method="POST" action="{{ route('admin.xp.ndr.done', $r->id) }}" class="d-flex gap-1 mt-1">@csrf
                                    <input name="note" class="form-control form-control-sm" placeholder="Ref / note" maxlength="255">
                                    <button class="btn btn-sm btn-success py-0">Done</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Nothing here.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
