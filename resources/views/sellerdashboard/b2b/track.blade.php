@extends('layouts.sellerdash')

@section('content')
<div class="pc-container b2-page">
<div class="pc-content sxd">{{-- sidebar-aware frame, same as every other seller page --}}
@include('sellerdashboard.b2b._style')
<div class="b2">
    @include('sellerdashboard.b2b._tabs')

    <div class="b2-head">
        <div>
            <h1>Track B2B shipment</h1>
            <p>Search by AWB, LR number, B2B order ID or invoice number.</p>
        </div>
    </div>

    <div class="b2-card">
        <form method="GET" action="{{ route('seller.b2b.track') }}" class="b2-f" style="display:flex;gap:8px;flex-wrap:wrap">
            <input name="q" value="{{ $q }}" placeholder="e.g. AWB, LR no., B2B000123 or invoice no." style="flex:1;min-width:220px" autofocus>
            <button class="b2-btn"><i class="ti ti-search"></i> Track</button>
        </form>
    </div>

    @if($q !== '')
        @if($found->isEmpty())
            <div class="b2-alert warn">No B2B shipment found for “{{ $q }}”.</div>
        @else
            <div class="b2-card" style="padding:0;overflow-x:auto">
                <table class="b2-table">
                    <thead><tr><th>Order</th><th>Receiver</th><th>Courier</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($found as $o)
                        <tr>
                            <td><a href="{{ route('seller.b2b.order.show', $o->id) }}">{{ $o->order_ref }}</a><div class="b2-muted">Invoice {{ $o->invoice_no }}</div></td>
                            <td>{{ $o->consignee['name'] ?? '' }}<div class="b2-muted">{{ $o->consignee['city'] ?? '' }} · {{ $o->consignee['pincode'] ?? '' }}</div></td>
                            <td>{{ $o->courier_name ?: '—' }}<div class="b2-muted">{{ $o->awb ? 'AWB ' . $o->awb : '' }}</div></td>
                            <td><span class="b2-pill {{ $o->status }}">{{ $o->statusLabel() }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif

    <div class="b2-card" style="padding:0;overflow-x:auto">
        <div style="padding:14px 16px;border-bottom:1px solid var(--b2-line);font-weight:700">Shipments on the way</div>
        @if($recent->count())
            <table class="b2-table">
                <thead><tr><th>Order</th><th>Receiver</th><th>Courier</th><th>Latest status</th></tr></thead>
                <tbody>
                @foreach($recent as $o)
                    <tr>
                        <td><a href="{{ route('seller.b2b.order.show', $o->id) }}">{{ $o->order_ref }}</a><div class="b2-muted">{{ optional($o->booked_at)->format('d M Y') }}</div></td>
                        <td>{{ $o->consignee['name'] ?? '' }}<div class="b2-muted">{{ $o->consignee['city'] ?? '' }}</div></td>
                        <td>{{ $o->courier_name ?: '—' }}<div class="b2-muted">{{ $o->awb ? 'AWB ' . $o->awb : '' }}</div></td>
                        <td><span class="b2-pill {{ $o->status }}">{{ $o->statusLabel() }}</span>@if($o->tracking_status)<div class="b2-muted">{{ $o->tracking_status }}</div>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <div class="b2-empty"><i class="ti ti-map-pin"></i>No B2B shipments in transit.</div>
        @endif
    </div>
</div>
</div>
</div>
@endsection
