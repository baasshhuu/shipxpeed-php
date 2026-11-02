@extends('layouts.sellerdash')

@section('content')
@include('sellerdashboard.b2b._style')
@php
    $c = $order->consignee ?? [];
    $p = $order->pickup ?? [];
    $r = $order->return_address ?? [];
    $pick = function ($row, array $keys) { foreach ($keys as $k) { if (!empty($row[$k]) && is_scalar($row[$k])) return $row[$k]; } return null; };
    $checkpoints = $order->tracking['checkpoints'] ?? ($order->tracking['tracking_histories'] ?? []);
    if (!is_array($checkpoints)) $checkpoints = [];
@endphp
<div class="b2">
    @include('sellerdashboard.b2b._tabs')
    <div class="b2-head">
        <div>
            <h1>{{ $order->order_ref }} <span class="b2-pill {{ $order->status }}" style="vertical-align:middle">{{ $order->statusLabel() }}</span></h1>
            <p>Created {{ $order->created_at->format('d M Y, h:i A') }}@if($order->booked_at) · booked {{ $order->booked_at->format('d M Y, h:i A') }}@endif</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="{{ route('seller.b2b.orders') }}" class="b2-btn ghost"><i class="ti ti-arrow-left"></i> All B2B shipments</a>
            @if($enabled)<a href="{{ route('seller.b2b.order') }}" class="b2-btn ghost"><i class="ti ti-circle-plus"></i> New</a>@endif
        </div>
    </div>

    @if(session('success'))<div class="b2-alert ok">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="b2-alert bad">{{ session('error') }}</div>@endif

    <div class="b2-split">
        <div>
            @if($order->status === 'draft')
            <div class="b2-card">
                <h2><i class="ti ti-truck"></i> Not booked yet</h2>
                @if($order->error)<div class="b2-alert bad" style="margin-bottom:12px">{{ $order->error }}</div>@endif
                @if($enabled)
                    <div id="b2RateMsg" class="b2-muted" style="margin-bottom:12px">Loading courier options…</div>
                    <form method="POST" action="{{ route('seller.b2b.order.book', $order->id) }}" id="b2BookForm">
                        @csrf
                        <input type="hidden" name="courier_id" id="b2Courier">
                        <input type="hidden" name="quoted_price" id="b2Price">
                        <div class="b2-rates" id="b2Rates"></div>
                        <div style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap;align-items:center">
                            <button class="b2-btn" id="b2Book" disabled><i class="ti ti-check"></i> Book shipment</button>
                            <span class="b2-muted">Wallet balance ₹{{ number_format($balance, 2) }} · prices include GST</span>
                        </div>
                    </form>
                @endif
                <form method="POST" action="{{ route('seller.b2b.order.cancel', $order->id) }}" style="margin-top:12px" onsubmit="return confirm('Discard this draft?')">
                    @csrf<button class="b2-btn danger sm">Discard draft</button>
                </form>
            </div>
            @endif

            @if(in_array($order->status, ['booked', 'in_transit', 'delivered', 'rto'], true))
            <div class="b2-card">
                <h2><i class="ti ti-map-pin"></i> Tracking</h2>
                <dl class="b2-kv" style="margin-bottom:14px">
                    <dt>Courier</dt><dd>{{ $order->courier_name }}</dd>
                    <dt>AWB</dt><dd><b>{{ $order->awb ?: 'Generating…' }}</b></dd>
                    @if($order->lr_number)<dt>LR number</dt><dd>{{ $order->lr_number }}</dd>@endif
                    <dt>Status</dt><dd>{{ $order->tracking_status ?: $order->statusLabel() }}</dd>
                    @if(!empty($order->tracking['date_expected_delivery']))<dt>Expected delivery</dt><dd>{{ \Illuminate\Support\Str::limit((string) $order->tracking['date_expected_delivery'], 25, '') }}</dd>@endif
                </dl>
                @if(count($checkpoints))
                    <ul class="b2-tl">
                        @foreach(array_reverse($checkpoints) as $cp)
                            @php($cp = is_array($cp) ? $cp : [])
                            <li>
                                <div class="t">{{ $pick($cp, ['status', 'checkpoint_status', 'scan_status', 'remarks', 'remark', 'activity']) ?? 'Update' }}</div>
                                <div class="s">{{ $pick($cp, ['location', 'checkpoint_location', 'city']) }} {{ $pick($cp, ['checkpoint_time', 'time', 'date', 'datetime', 'created_at']) }}</div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="b2-muted">No scans yet. Updates appear once the courier picks up the shipment.</div>
                @endif
                <form method="POST" action="{{ route('seller.b2b.order.track', $order->id) }}" style="margin-top:12px">
                    @csrf<button class="b2-btn ghost sm"><i class="ti ti-refresh"></i> Refresh tracking</button>
                    @if($order->last_tracked_at)<span class="b2-muted">updated {{ $order->last_tracked_at->diffForHumans() }}</span>@endif
                </form>
            </div>
            @endif

            <div class="b2-card">
                <h2><i class="ti ti-package"></i> Shipment</h2>
                <dl class="b2-kv">
                    <dt>Goods</dt><dd>{{ $order->product_name }}</dd>
                    <dt>Invoice</dt><dd>{{ $order->invoice_no }} · ₹{{ number_format($order->invoice_amount, 2) }}@if($order->invoice_date) · {{ $order->invoice_date->format('d M Y') }}@endif</dd>
                    <dt>Payment</dt><dd>{{ $order->isCod() ? 'COD · collect ₹' . number_format($order->cod_amount, 2) : 'Prepaid' }}</dd>
                    <dt>Risk</dt><dd>{{ (int) $order->risk_type_id === 3 ? "Carrier's risk" : "Owner's risk" }}</dd>
                    @if($order->ewaybill_no)<dt>E-way bill</dt><dd>{{ $order->ewaybill_no }}</dd>@endif
                    <dt>Boxes</dt>
                    <dd>
                        @foreach($order->boxes ?? [] as $b)
                            <div>{{ $b['count'] }} × {{ $b['length'] }}×{{ $b['breadth'] }}×{{ $b['height'] }} cm · {{ $b['weight'] }} kg each</div>
                        @endforeach
                        <div class="b2-muted">{{ $order->total_boxes }} boxes · {{ rtrim(rtrim(number_format($order->total_weight, 2), '0'), '.') }} kg dead weight @if($order->charged_weight) · charged {{ rtrim(rtrim(number_format($order->charged_weight, 2), '0'), '.') }} kg @endif</div>
                    </dd>
                </dl>
            </div>
        </div>

        <div>
            @if($order->status !== 'draft' && $order->provider_order_id)
            <div class="b2-card">
                <h2><i class="ti ti-file-download"></i> Documents</h2>
                <div style="display:grid;gap:8px">
                    <a class="b2-btn" href="{{ route('seller.b2b.order.document', [$order->id, 'label']) }}" target="_blank"><i class="ti ti-tag"></i> Shipping label</a>
                    <a class="b2-btn ghost" href="{{ route('seller.b2b.order.document', [$order->id, 'manifest']) }}" target="_blank"><i class="ti ti-file-text"></i> Manifest</a>
                </div>
            </div>
            @endif

            <div class="b2-card">
                <h2><i class="ti ti-receipt"></i> Charges</h2>
                <dl class="b2-kv">
                    <dt>Shipping</dt><dd><b>{{ $order->seller_charge ? '₹' . number_format($order->seller_charge, 2) : '—' }}</b> <span class="b2-muted">incl. GST</span></dd>
                    @if($order->tat)<dt>Transit time</dt><dd>{{ $order->tat }} days</dd>@endif
                </dl>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
                    @if($order->invoice_file)<a class="b2-btn ghost sm" href="{{ route('seller.b2b.order.file', [$order->id, 'invoice']) }}" target="_blank">Invoice file</a>@endif
                    @if($order->ewaybill_file)<a class="b2-btn ghost sm" href="{{ route('seller.b2b.order.file', [$order->id, 'ewaybill']) }}" target="_blank">E-way bill file</a>@endif
                </div>
                @if($order->canCancel())
                    <form method="POST" action="{{ route('seller.b2b.order.cancel', $order->id) }}" style="margin-top:14px" onsubmit="return confirm('Cancel this shipment? The charge is refunded to your wallet.')">
                        @csrf<button class="b2-btn danger" style="width:100%"><i class="ti ti-x"></i> Cancel shipment</button>
                    </form>
                    <div class="b2-muted" style="margin-top:6px">Possible until the courier picks it up.</div>
                @endif
            </div>

            <div class="b2-card">
                <h2><i class="ti ti-route"></i> Addresses</h2>
                <div class="b2-muted">From</div>
                <div style="margin-bottom:10px">{{ $p['name'] ?? '' }}, {{ $p['address_line1'] ?? '' }} {{ $p['address_line2'] ?? '' }}, {{ $p['city'] ?? '' }} {{ $p['pincode'] ?? '' }}</div>
                <div class="b2-muted">To</div>
                <div style="margin-bottom:10px">{{ $c['name'] ?? '' }}@if(!empty($c['company'])) ({{ $c['company'] }})@endif, {{ $c['address_line1'] ?? '' }} {{ $c['address_line2'] ?? '' }}, {{ $c['city'] ?? '' }}, {{ $c['state'] ?? '' }} {{ $c['pincode'] ?? '' }} · {{ $c['phone'] ?? '' }}</div>
                @if(($r['pincode'] ?? null) && (($r['address_line1'] ?? '') !== ($p['address_line1'] ?? '')))
                    <div class="b2-muted">Return to</div>
                    <div>{{ $r['name'] ?? '' }}, {{ $r['address_line1'] ?? '' }}, {{ $r['city'] ?? '' }} {{ $r['pincode'] ?? '' }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($order->status === 'draft' && $enabled)
<script>
(function () {
    var rates = document.getElementById('b2Rates'), msg = document.getElementById('b2RateMsg'), book = document.getElementById('b2Book');
    function money(n) { return '₹' + Number(n).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    fetch(@json(route('seller.b2b.order.rates', $order->id)), {headers: {'Accept': 'application/json'}})
        .then(function (r) { return r.json(); })
        .then(function (j) {
            if (!j.ok) { msg.textContent = j.error || 'Could not get rates.'; return; }
            if (!j.rates.length) { msg.textContent = 'No courier is available for this shipment right now.'; return; }
            msg.textContent = 'Choose a courier:';
            rates.innerHTML = j.rates.map(function (r, i) {
                return '<label class="b2-rate"><input type="radio" name="_rate" value="' + i + '"><div><div class="nm">' + esc(r.courier_name) +
                    '</div><div class="mt">' + (r.tat ? r.tat + ' days · ' : '') + (r.charged_weight ? 'charged ' + r.charged_weight + ' kg' : '') +
                    '</div></div><div class="pr">' + money(r.price) + '</div></label>';
            }).join('');
            rates.querySelectorAll('input').forEach(function (inp) {
                inp.addEventListener('change', function () {
                    var r = j.rates[+inp.value];
                    rates.querySelectorAll('.b2-rate').forEach(function (l) { l.classList.remove('on'); });
                    inp.closest('.b2-rate').classList.add('on');
                    document.getElementById('b2Courier').value = r.courier_id;
                    document.getElementById('b2Price').value = r.price;
                    book.disabled = false; book.innerHTML = '<i class="ti ti-check"></i> Book for ' + money(r.price);
                });
            });
        })
        .catch(function () { msg.textContent = 'Network error - reload the page.'; });
    document.getElementById('b2BookForm').addEventListener('submit', function () { book.disabled = true; book.textContent = 'Booking…'; });
})();
</script>
@endif
@endsection
