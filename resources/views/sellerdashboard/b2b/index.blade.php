@extends('layouts.sellerdash')

@section('content')
@include('sellerdashboard.b2b._style')
<div class="b2">
    @include('sellerdashboard.b2b._tabs')
    <div class="b2-head">
        <div>
            <h1>All B2B shipments</h1>
            <p>Heavy and multi-box shipments to businesses, with invoice and e-way bill.</p>
        </div>
        @if($enabled)
            <a href="{{ route('seller.b2b.order') }}" class="b2-btn"><i class="ti ti-circle-plus"></i> Create B2B shipment</a>
        @endif
    </div>

    @if(session('success'))<div class="b2-alert ok">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="b2-alert bad">{{ session('error') }}</div>@endif
    @unless($enabled)
        <div class="b2-alert warn">B2B shipping is being set up for your account. Please check back soon or contact support.</div>
    @endunless

    <div class="b2-tabs">
        <a href="{{ route('seller.b2b.orders') }}" class="{{ !$status ? 'on' : '' }}">All · {{ $counts->sum() }}</a>
        @foreach(\App\Models\B2bOrder::STATUS_LABELS as $k => $label)
            @if(($counts[$k] ?? 0) > 0)
                <a href="{{ route('seller.b2b.orders', ['status' => $k]) }}" class="{{ $status === $k ? 'on' : '' }}">{{ $label }} · {{ $counts[$k] }}</a>
            @endif
        @endforeach
    </div>

    <div class="b2-card" style="padding:0;overflow-x:auto">
        <form method="GET" style="padding:14px;border-bottom:1px solid var(--b2-line);display:flex;gap:8px" class="b2-f">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input name="q" value="{{ request('q') }}" placeholder="Search order ID, AWB or invoice no." style="max-width:340px">
            <button class="b2-btn ghost sm" style="height:40px">Search</button>
        </form>
        @if($orders->count())
        <table class="b2-table">
            <thead><tr><th>Order</th><th>Receiver</th><th>Boxes</th><th>Invoice</th><th>Courier</th><th>Charge</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($orders as $o)
                <tr>
                    <td><a href="{{ route('seller.b2b.order.show', $o->id) }}">{{ $o->order_ref }}</a><div class="b2-muted">{{ $o->created_at->format('d M Y, h:i A') }}</div></td>
                    <td>{{ $o->consignee['name'] ?? '' }}<div class="b2-muted">{{ $o->consignee['city'] ?? '' }} · {{ $o->consignee['pincode'] ?? '' }}</div></td>
                    <td>{{ $o->total_boxes }}<div class="b2-muted">{{ rtrim(rtrim(number_format($o->total_weight, 2), '0'), '.') }} kg</div></td>
                    <td>₹{{ number_format($o->invoice_amount, 0) }}<div class="b2-muted">{{ strtoupper($o->payment_mode) }}</div></td>
                    <td>{{ $o->courier_name ?: '—' }}<div class="b2-muted">{{ $o->awb ? 'AWB ' . $o->awb : '' }}</div></td>
                    <td>{{ $o->seller_charge ? '₹' . number_format($o->seller_charge, 2) : '—' }}</td>
                    <td><span class="b2-pill {{ $o->status }}">{{ $o->statusLabel() }}</span>@if($o->tracking_status && $o->status !== 'cancelled')<div class="b2-muted">{{ $o->tracking_status }}</div>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div style="padding:12px">{{ $orders->links() }}</div>
        @else
            <div class="b2-empty"><i class="ti ti-truck-delivery"></i>No B2B shipments yet.</div>
        @endif
    </div>
</div>
@endsection
