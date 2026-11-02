@extends('layouts.sellerdash')

@section('content')
<div class="pc-container b2-page">
<div class="pc-content sxd">{{-- sidebar-aware frame, same as every other seller page --}}
@include('sellerdashboard.b2b._style')
@php
    $tabIcons = ['draft' => 'ti ti-clock-hour-4', 'booked' => 'ti ti-package', 'in_transit' => 'ti ti-truck', 'delivered' => 'ti ti-circle-check', 'rto' => 'ti ti-arrow-back-up', 'cancelled' => 'ti ti-circle-x'];
    $docs = ['label' => 'Label', 'manifest' => 'Manifest', 'invoice' => 'Invoice', 'ewaybill' => 'E-way bill'];
@endphp
<style>
    .b2l-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
    .b2l-tabs a { display:flex; align-items:center; gap:7px; padding:8px 14px; border-radius:10px; border:1px solid var(--b2-line); text-decoration:none; color:inherit !important; font-size:.84rem; font-weight:600; background:var(--b2-card, transparent); }
    .b2l-tabs a .c { min-width:22px; text-align:center; padding:1px 7px; border-radius:999px; font-size:.72rem; background:rgba(127,135,150,.16); }
    .b2l-tabs a.on { border-color:var(--sx-blue,#3b7bff); box-shadow:inset 0 0 0 1px var(--sx-blue,#3b7bff); }
    .b2l-tabs a.on .c { background:var(--sx-blue,#3b7bff); color:#fff; }
    .b2l-tabs a.hot .c { background:rgba(212,138,18,.2); color:#e3a33a; }
    .b2l-acts { display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end; }
    .b2l-acts form { margin:0; }
    .b2l-dd { position:relative; }
    .b2l-dd > summary { list-style:none; cursor:pointer; } .b2l-dd > summary::-webkit-details-marker { display:none; }
    .b2l-dd .m { position:absolute; right:0; top:calc(100% + 4px); z-index:30; min-width:170px; background:var(--sx-raised,#152036); border:1px solid var(--b2-line); border-radius:10px; padding:6px; box-shadow:0 10px 30px rgba(0,0,0,.3); }
    .b2l-dd .m a, .b2l-dd .m button { display:flex; width:100%; align-items:center; gap:8px; padding:7px 10px; border-radius:7px; font-size:.82rem; color:inherit !important; text-decoration:none; background:none; border:0; text-align:left; cursor:pointer; }
    .b2l-dd .m a:hover, .b2l-dd .m button:hover { background:rgba(127,135,150,.14); }
    .b2l-modal { position:fixed; inset:0; background:rgba(5,8,16,.6); z-index:2000; display:none; align-items:center; justify-content:center; padding:16px; }
    .b2l-modal.on { display:flex; }
    .b2l-modal .bx { width:100%; max-width:520px; max-height:88vh; overflow:auto; background:var(--sx-surface,#0f1626); border:1px solid var(--b2-line); border-radius:14px; padding:18px; }
    .b2l-modal h3 { font-size:1.05rem; margin:0 0 4px; }
    .b2-table td .b2l-ref { font-weight:600; }
</style>
<div class="b2">
    @include('sellerdashboard.b2b._tabs')
    <div class="b2-head">
        <div>
            <h1>All B2B shipments</h1>
            <p>Create an order, then assign a courier from <b>Unshipped</b>. Download label, manifest, invoice and e-way bill once it is booked.</p>
        </div>
        @if($enabled)
            <a href="{{ route('seller.b2b.order') }}" class="b2-btn"><i class="ti ti-circle-plus"></i> Create B2B order</a>
        @endif
    </div>

    @if(session('success'))<div class="b2-alert ok">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="b2-alert bad">{{ session('error') }}</div>@endif
    @unless($enabled)
        <div class="b2-alert warn">B2B shipping is being set up for your account. Please check back soon or contact support.</div>
    @endunless

    <div class="b2l-tabs">
        <a href="{{ route('seller.b2b.orders', array_filter(['q' => request('q')])) }}" class="{{ !$status ? 'on' : '' }}"><i class="ti ti-list"></i> All <span class="c">{{ $counts->sum() }}</span></a>
        @foreach(\App\Models\B2bOrder::STATUS_LABELS as $k => $label)
            <a href="{{ route('seller.b2b.orders', array_filter(['status' => $k, 'q' => request('q')])) }}" class="{{ $status === $k ? 'on' : '' }} {{ $k === 'draft' && ($counts[$k] ?? 0) > 0 ? 'hot' : '' }}">
                <i class="{{ $tabIcons[$k] ?? 'ti ti-point' }}"></i> {{ $label }} <span class="c">{{ $counts[$k] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div class="b2-card" style="padding:0;overflow:visible">
        <form method="GET" style="padding:14px;border-bottom:1px solid var(--b2-line);display:flex;gap:8px;flex-wrap:wrap" class="b2-f">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input name="q" value="{{ request('q') }}" placeholder="Search order ID, AWB or invoice no." style="max-width:340px">
            <button class="b2-btn ghost sm" style="height:40px">Search</button>
            @if(request('q'))<a href="{{ route('seller.b2b.orders', array_filter(['status' => $status])) }}" class="b2-btn ghost sm" style="height:40px">Clear</a>@endif
        </form>
        @if($orders->count())
        <div style="overflow-x:auto">
        <table class="b2-table">
            <thead><tr><th>Order</th><th>Receiver</th><th>Boxes</th><th>Invoice</th><th>Courier</th><th>Charge</th><th>Status</th><th style="text-align:right">Action</th></tr></thead>
            <tbody>
            @foreach($orders as $o)
                <tr>
                    <td><a href="{{ route('seller.b2b.order.show', $o->id) }}" class="b2l-ref">{{ $o->order_ref }}</a><div class="b2-muted">{{ $o->created_at->format('d M Y, h:i A') }}</div></td>
                    <td>{{ $o->consignee['name'] ?? '' }}<div class="b2-muted">{{ $o->consignee['city'] ?? '' }} · {{ $o->consignee['pincode'] ?? '' }}</div></td>
                    <td>{{ $o->total_boxes }}<div class="b2-muted">{{ rtrim(rtrim(number_format($o->total_weight, 2), '0'), '.') }} kg</div></td>
                    <td>₹{{ number_format($o->invoice_amount, 0) }}<div class="b2-muted">{{ strtoupper($o->payment_mode) }}{{ $o->invoice_no ? ' · ' . $o->invoice_no : '' }}</div></td>
                    <td>{{ $o->courier_name ?: '—' }}<div class="b2-muted">{{ $o->awb ? 'AWB ' . $o->awb : '' }}{{ $o->lr_number ? ' · LR ' . $o->lr_number : '' }}</div></td>
                    <td>{{ $o->seller_charge ? '₹' . number_format($o->seller_charge, 2) : '—' }}</td>
                    <td><span class="b2-pill {{ $o->status }}">{{ $o->statusLabel() }}</span>@if($o->tracking_status && !in_array($o->status, ['cancelled', 'draft']))<div class="b2-muted">{{ $o->tracking_status }}</div>@endif</td>
                    <td>
                        <div class="b2l-acts">
                        @if($o->status === 'draft')
                            @if($enabled)
                                <button type="button" class="b2-btn sm" onclick="b2Assign({{ $o->id }}, @js($o->order_ref))"><i class="ti ti-truck"></i> Assign courier</button>
                            @endif
                            <form method="POST" action="{{ route('seller.b2b.order.cancel', $o->id) }}" onsubmit="return confirm('Cancel order {{ $o->order_ref }}?')">
                                @csrf <input type="hidden" name="from" value="list"><input type="hidden" name="tab" value="{{ $status }}">
                                <button class="b2-btn ghost sm" title="Cancel order"><i class="ti ti-x"></i> Cancel</button>
                            </form>
                        @elseif($o->status !== 'cancelled')
                            @if($o->provider_order_id)
                                <a href="{{ route('seller.b2b.order.document', [$o->id, 'label']) }}" target="_blank" class="b2-btn sm"><i class="ti ti-printer"></i> Label</a>
                            @endif
                            <details class="b2l-dd">
                                <summary class="b2-btn ghost sm"><i class="ti ti-dots"></i> More</summary>
                                <div class="m">
                                    @if($o->provider_order_id)
                                        @foreach($docs as $type => $dl)
                                            @continue($type === 'label')
                                            @continue($type === 'ewaybill' && !$o->ewaybill_no)
                                            <a href="{{ route('seller.b2b.order.document', [$o->id, $type]) }}" target="_blank"><i class="ti ti-download"></i> {{ $dl }}</a>
                                        @endforeach
                                    @endif
                                    @if($o->invoice_file)<a href="{{ route('seller.b2b.order.file', [$o->id, 'invoice']) }}" target="_blank"><i class="ti ti-file-invoice"></i> My invoice upload</a>@endif
                                    <form method="POST" action="{{ route('seller.b2b.order.track', $o->id) }}">
                                        @csrf <input type="hidden" name="from" value="list"><input type="hidden" name="tab" value="{{ $status }}">
                                        <button><i class="ti ti-refresh"></i> Refresh tracking</button>
                                    </form>
                                    <a href="{{ route('seller.b2b.order.show', $o->id) }}"><i class="ti ti-eye"></i> View details</a>
                                    @if($o->canCancel())
                                        <form method="POST" action="{{ route('seller.b2b.order.cancel', $o->id) }}" onsubmit="return confirm('Cancel shipment {{ $o->order_ref }} with the courier?')">
                                            @csrf <input type="hidden" name="from" value="list"><input type="hidden" name="tab" value="{{ $status }}">
                                            <button style="color:#ff8a80 !important"><i class="ti ti-x"></i> Cancel shipment</button>
                                        </form>
                                    @endif
                                </div>
                            </details>
                        @else
                            <a href="{{ route('seller.b2b.order.show', $o->id) }}" class="b2-btn ghost sm">View</a>
                        @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        <div style="padding:12px">{{ $orders->links() }}</div>
        @else
            <div class="b2-empty"><i class="ti ti-truck-delivery"></i>
                {{ $status === 'draft' ? 'No unshipped B2B orders.' : ($status ? 'No ' . strtolower(\App\Models\B2bOrder::STATUS_LABELS[$status] ?? '') . ' shipments.' : 'No B2B shipments yet.') }}
                @if($enabled && (!$status || $status === 'draft'))<div style="margin-top:10px"><a href="{{ route('seller.b2b.order') }}" class="b2-btn sm">Create B2B order</a></div>@endif
            </div>
        @endif
    </div>
</div>

<div class="b2l-modal" id="b2Modal" onclick="if (event.target === this) this.classList.remove('on')">
    <div class="bx">
        <div style="display:flex;justify-content:space-between;align-items:start;gap:10px">
            <div><h3>Assign courier · <span id="b2mRef"></span></h3><div class="b2-muted">Prices include GST. The amount is debited from your wallet when the courier confirms the booking.</div></div>
            <button type="button" class="b2-btn ghost sm" onclick="document.getElementById('b2Modal').classList.remove('on')">×</button>
        </div>
        <div id="b2mMsg" class="b2-muted" style="margin:14px 0 10px">Fetching rates…</div>
        <form method="POST" id="b2mForm">
            @csrf
            <input type="hidden" name="from" value="list"><input type="hidden" name="tab" value="{{ $status }}">
            <input type="hidden" name="courier_id" id="b2mCourier"><input type="hidden" name="quoted_price" id="b2mPrice">
            <div class="b2-rates" id="b2mRates"></div>
            <button class="b2-btn" id="b2mBook" style="width:100%;margin-top:12px" disabled><i class="ti ti-check"></i> Book shipment</button>
        </form>
    </div>
</div>
</div>
</div>
<script>
(function () {
    var BOOK = @json(route('seller.b2b.order.book', 0)), RATES = @json(route('seller.b2b.order.rates', 0));
    function u(t, id) { return t.replace(/\/0\/(book|rates)$/, '/' + id + '/$1'); }
    function money(n) { return '₹' + Number(n).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    window.b2Assign = function (id, ref) {
        var m = document.getElementById('b2Modal'), msg = document.getElementById('b2mMsg'), box = document.getElementById('b2mRates'), btn = document.getElementById('b2mBook');
        document.getElementById('b2mRef').textContent = ref;
        document.getElementById('b2mForm').action = u(BOOK, id);
        box.innerHTML = ''; btn.disabled = true; btn.innerHTML = '<i class="ti ti-check"></i> Book shipment'; msg.textContent = 'Fetching rates…';
        m.classList.add('on');
        fetch(u(RATES, id), {headers: {'Accept': 'application/json'}})
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (!j.ok) { msg.textContent = j.error || 'Could not get rates.'; return; }
                if (!j.rates.length) { msg.textContent = 'No courier is available for this route and weight.'; return; }
                msg.textContent = 'Choose a courier:';
                box.innerHTML = j.rates.map(function (r, i) {
                    return '<label class="b2-rate"><input type="radio" name="_rate" value="' + i + '">' +
                        '<div><div class="nm">' + esc(r.courier_name) + '</div><div class="mt">' +
                        (r.tat ? r.tat + ' day' + (r.tat > 1 ? 's' : '') + ' · ' : '') +
                        (r.charged_weight ? 'charged ' + r.charged_weight + ' kg' : '') + '</div>' +
                        (r.note ? '<div class="mt">' + esc(r.note) + '</div>' : '') + '</div>' +
                        '<div class="pr">' + money(r.price) + '</div></label>';
                }).join('');
                box.querySelectorAll('input').forEach(function (inp) {
                    inp.addEventListener('change', function () {
                        var r = j.rates[+inp.value];
                        box.querySelectorAll('.b2-rate').forEach(function (l) { l.classList.remove('on'); });
                        inp.closest('.b2-rate').classList.add('on');
                        document.getElementById('b2mCourier').value = r.courier_id;
                        document.getElementById('b2mPrice').value = r.price;
                        btn.disabled = false; btn.innerHTML = '<i class="ti ti-check"></i> Book for ' + money(r.price);
                    });
                });
            })
            .catch(function () { msg.textContent = 'Network error - please try again.'; });
    };
    document.getElementById('b2mForm').addEventListener('submit', function () { var b = document.getElementById('b2mBook'); b.disabled = true; b.textContent = 'Booking…'; });
    document.addEventListener('click', function (e) { document.querySelectorAll('.b2l-dd[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); }); });
})();
</script>
@endsection
