@extends('layouts.sellerdash')

@section('content')
@include('sellerdashboard.b2b._style')
@php
    $oldBoxes = old('boxes', [['count' => 1, 'length' => '', 'breadth' => '', 'height' => '', 'weight' => '']]);
    $defaultWh = optional($warehouses->first())->id;
@endphp
<div class="b2">
    @include('sellerdashboard.b2b._tabs')
    <div class="b2-head">
        <div>
            <h1>Create B2B shipment</h1>
            <p>Multi-box shipment to a business. Invoice is required; e-way bill for invoices of ₹{{ number_format($threshold) }} or more.</p>
        </div>
    </div>

    @if(session('error'))<div class="b2-alert bad">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="b2-alert bad">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    @if(!$enabled)
        <div class="b2-alert warn">B2B shipping is being set up for your account. Please check back soon or contact support.</div>
    @elseif($warehouses->isEmpty())
        <div class="b2-alert warn">Add a pickup warehouse first. <a href="/seller/warehouse/index" style="color:inherit;text-decoration:underline">Go to Warehouses</a></div>
    @else
    <form method="POST" action="{{ route('seller.b2b.order.store') }}" enctype="multipart/form-data" id="b2Form">
        @csrf
        <input type="hidden" name="courier_id" id="b2Courier" value="">
        <input type="hidden" name="quoted_price" id="b2Price" value="">
        <div class="b2-split">
            <div>
                <div class="b2-card">
                    <h2><span class="n">1</span> Pickup &amp; return</h2>
                    <div class="b2-grid">
                        <div class="b2-f">
                            <label>Pickup warehouse</label>
                            <select name="pickup_warehouse_id" id="b2Pick" data-q required>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}" @selected(old('pickup_warehouse_id', $defaultWh) == $w->id)>{{ $w->address_title ?: $w->name }} · {{ $w->city }} {{ $w->pincode }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="b2-f">
                            <label>Return address</label>
                            <select name="return_warehouse_id">
                                <option value="">Same as pickup</option>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}" @selected(old('return_warehouse_id') == $w->id)>{{ $w->address_title ?: $w->name }} · {{ $w->city }} {{ $w->pincode }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="b2-card">
                    <h2><span class="n">2</span> Receiver (consignee)</h2>
                    <div class="b2-grid">
                        <div class="b2-f"><label>Contact name</label><input name="consignee[name]" value="{{ old('consignee.name') }}" maxlength="100" required></div>
                        <div class="b2-f"><label>Company name <span class="b2-muted">(optional)</span></label><input name="consignee[company]" value="{{ old('consignee.company') }}" maxlength="100"></div>
                        <div class="b2-f"><label>Mobile</label><input name="consignee[phone]" value="{{ old('consignee.phone') }}" inputmode="numeric" maxlength="14" required></div>
                        <div class="b2-f"><label>Email <span class="b2-muted">(optional)</span></label><input type="email" name="consignee[email]" value="{{ old('consignee.email') }}"></div>
                        <div class="b2-f"><label>GSTIN <span class="b2-muted">(optional)</span></label><input name="consignee[gstin]" value="{{ old('consignee.gstin') }}" maxlength="15" style="text-transform:uppercase"></div>
                    </div>
                    <div class="b2-grid" style="margin-top:12px">
                        <div class="b2-f" style="grid-column:span 2"><label>Address line 1</label><input name="consignee[address_line1]" value="{{ old('consignee.address_line1') }}" maxlength="200" required></div>
                        <div class="b2-f" style="grid-column:span 2"><label>Address line 2 <span class="b2-muted">(optional)</span></label><input name="consignee[address_line2]" value="{{ old('consignee.address_line2') }}" maxlength="200"></div>
                        <div class="b2-f"><label>Landmark <span class="b2-muted">(optional)</span></label><input name="consignee[landmark]" value="{{ old('consignee.landmark') }}" maxlength="100"></div>
                        <div class="b2-f"><label>Pincode</label><input name="consignee[pincode]" id="b2To" data-q value="{{ old('consignee.pincode') }}" inputmode="numeric" maxlength="6" required></div>
                        <div class="b2-f"><label>City</label><input name="consignee[city]" id="b2City" value="{{ old('consignee.city') }}" maxlength="60" required></div>
                        <div class="b2-f"><label>State</label><input name="consignee[state]" id="b2State" value="{{ old('consignee.state') }}" maxlength="60" required></div>
                    </div>
                </div>

                <div class="b2-card">
                    <h2><span class="n">3</span> Boxes</h2>
                    <div class="b2-box b2-muted" style="margin-bottom:4px"><span>No. of boxes</span><span>Length (cm)</span><span>Breadth (cm)</span><span>Height (cm)</span><span>Weight / box (kg)</span><span></span></div>
                    <div id="b2Boxes">
                        @foreach($oldBoxes as $i => $b)
                        <div class="b2-box b2-f">
                            <input type="number" name="boxes[{{ $i }}][count]" value="{{ $b['count'] ?? 1 }}" min="1" max="999" data-q required>
                            <input type="number" step="0.1" name="boxes[{{ $i }}][length]" value="{{ $b['length'] ?? '' }}" min="1" placeholder="L" data-q required>
                            <input type="number" step="0.1" name="boxes[{{ $i }}][breadth]" value="{{ $b['breadth'] ?? '' }}" min="1" placeholder="B" data-q required>
                            <input type="number" step="0.1" name="boxes[{{ $i }}][height]" value="{{ $b['height'] ?? '' }}" min="1" placeholder="H" data-q required>
                            <input type="number" step="0.1" name="boxes[{{ $i }}][weight]" value="{{ $b['weight'] ?? '' }}" min="0.1" placeholder="kg" data-q required>
                            <button type="button" class="b2-x" title="Remove" data-rm>×</button>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" class="b2-btn ghost sm" id="b2AddBox"><i class="ti ti-plus"></i> Add box size</button>
                    <div class="b2-sum"><span>Total boxes: <b id="b2TBoxes">0</b></span><span>Dead weight: <b id="b2TWt">0</b> kg</span><span>Volumetric: <b id="b2TVol">0</b> kg</span></div>
                    <div class="b2-muted" style="margin-top:4px">Weights are rounded up to the next whole kg per box. Courier charges the higher of dead and volumetric weight.</div>
                </div>

                <div class="b2-card">
                    <h2><span class="n">4</span> Invoice &amp; payment</h2>
                    <div class="b2-grid">
                        <div class="b2-f"><label>Product / goods description</label><input name="product_name" value="{{ old('product_name') }}" maxlength="150" required></div>
                        <div class="b2-f"><label>Invoice number</label><input name="invoice_no" value="{{ old('invoice_no') }}" maxlength="60" required></div>
                        <div class="b2-f"><label>Invoice date</label><input type="date" name="invoice_date" value="{{ old('invoice_date', now()->toDateString()) }}"></div>
                        <div class="b2-f"><label>Invoice value (₹)</label><input type="number" step="0.01" min="1" name="invoice_amount" id="b2Amt" data-q value="{{ old('invoice_amount') }}" required></div>
                    </div>
                    <div class="b2-grid" style="margin-top:12px">
                        <div class="b2-f">
                            <label>Payment</label>
                            <div class="b2-seg">
                                <label><input type="radio" name="payment_mode" value="prepaid" data-q @checked(old('payment_mode', 'prepaid') === 'prepaid')><span>Prepaid</span></label>
                                <label><input type="radio" name="payment_mode" value="cod" data-q @checked(old('payment_mode') === 'cod')><span>COD</span></label>
                            </div>
                        </div>
                        <div class="b2-f" id="b2CodWrap" hidden><label>COD amount to collect (₹)</label><input type="number" step="0.01" min="1" name="cod_amount" id="b2Cod" data-q value="{{ old('cod_amount') }}"><div class="hint">Must be less than the invoice value.</div></div>
                        <div class="b2-f">
                            <label>Transit risk</label>
                            <select name="risk_type_id" data-q>
                                <option value="2" @selected(old('risk_type_id', '2') == '2')>Owner's risk (cheaper)</option>
                                <option value="3" @selected(old('risk_type_id') == '3')>Carrier's risk</option>
                            </select>
                        </div>
                    </div>
                    <div class="b2-grid" style="margin-top:12px">
                        <div class="b2-f"><label>Invoice file (PDF / JPG / PNG, max 5 MB)</label><input type="file" name="invoice_file" accept=".pdf,.jpg,.jpeg,.png" required></div>
                    </div>
                    <div class="b2-grid" id="b2Eway" style="margin-top:12px" hidden>
                        <div class="b2-f"><label>E-way bill number</label><input name="ewaybill_no" id="b2EwayNo" value="{{ old('ewaybill_no') }}" inputmode="numeric" maxlength="12"><div class="hint">Required for invoices of ₹{{ number_format($threshold) }} or more.</div></div>
                        <div class="b2-f"><label>E-way bill file</label><input type="file" name="ewaybill_file" id="b2EwayFile" accept=".pdf,.jpg,.jpeg,.png"></div>
                    </div>
                    <div class="b2-muted" style="margin-top:10px">Selected files need to be chosen again if the page reloads with an error.</div>
                </div>
            </div>

            <div class="b2-sticky">
                <div class="b2-card">
                    <h2><i class="ti ti-truck"></i> Courier &amp; price</h2>
                    <div id="b2RateMsg" class="b2-muted" style="margin-bottom:12px">Fill pickup, receiver pincode, boxes and invoice value, then get rates.</div>
                    <button type="button" class="b2-btn ghost" id="b2GetRates" style="width:100%;margin-bottom:12px"><i class="ti ti-refresh"></i> Get rates</button>
                    <div class="b2-rates" id="b2Rates"></div>
                    <div style="border-top:1px solid var(--b2-line);margin-top:14px;padding-top:14px">
                        <div class="b2-sum" style="margin:0 0 12px"><span>Wallet balance: <b>₹{{ number_format($balance, 2) }}</b></span></div>
                        <button type="submit" class="b2-btn" id="b2Book" style="width:100%" disabled><i class="ti ti-check"></i> Book shipment</button>
                        <div class="b2-muted" style="margin-top:8px">Prices include GST. The amount is debited from your wallet when the courier confirms the booking.</div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endif
</div>

@if($enabled && $warehouses->isNotEmpty())
<script>
(function () {
    var form = document.getElementById('b2Form'), boxes = document.getElementById('b2Boxes');
    var TH = {{ (int) $threshold }}, idx = {{ count($oldBoxes) }};
    var rates = document.getElementById('b2Rates'), msg = document.getElementById('b2RateMsg');
    var book = document.getElementById('b2Book'), cId = document.getElementById('b2Courier'), cPr = document.getElementById('b2Price');
    function money(n) { return '₹' + Number(n).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

    function totals() {
        var tb = 0, tw = 0, tv = 0;
        boxes.querySelectorAll('.b2-box').forEach(function (r) {
            var v = r.querySelectorAll('input'), n = +v[0].value || 0;
            tb += n; tw += n * Math.ceil(+v[4].value || 0);
            tv += n * ((+v[1].value || 0) * (+v[2].value || 0) * (+v[3].value || 0)) / 5000;
        });
        document.getElementById('b2TBoxes').textContent = tb;
        document.getElementById('b2TWt').textContent = tw;
        document.getElementById('b2TVol').textContent = Math.ceil(tv);
    }
    function payment() { var c = form.querySelector('input[name=payment_mode]:checked'); return c ? c.value : 'prepaid'; }
    function toggles() {
        document.getElementById('b2CodWrap').hidden = payment() !== 'cod';
        var big = (+document.getElementById('b2Amt').value || 0) >= TH;
        document.getElementById('b2Eway').hidden = !big;
        document.getElementById('b2EwayNo').required = big;
        document.getElementById('b2EwayFile').required = big;
    }
    function clearRates(text) {
        rates.innerHTML = ''; cId.value = ''; cPr.value = ''; book.disabled = true;
        msg.textContent = text || 'Details changed - get rates again.';
    }

    boxes.addEventListener('click', function (e) {
        if (!e.target.hasAttribute('data-rm')) return;
        if (boxes.querySelectorAll('.b2-box').length > 1) { e.target.closest('.b2-box').remove(); totals(); clearRates(); }
    });
    document.getElementById('b2AddBox').addEventListener('click', function () {
        var row = boxes.querySelector('.b2-box').cloneNode(true);
        row.querySelectorAll('input').forEach(function (i) {
            i.name = i.name.replace(/boxes\[\d+\]/, 'boxes[' + idx + ']');
            i.value = i.name.indexOf('[count]') > -1 ? 1 : '';
        });
        idx++; boxes.appendChild(row); totals(); clearRates();
    });
    form.addEventListener('input', function (e) {
        totals(); toggles();
        if (e.target.hasAttribute('data-q') && cId.value) clearRates();
    });
    form.addEventListener('change', function (e) { toggles(); if (e.target.hasAttribute('data-q') && cId.value) clearRates(); });

    // City / state from pincode (India Post); seller can still type them
    document.getElementById('b2To').addEventListener('input', function () {
        var p = this.value.replace(/\D/g, '');
        if (p.length !== 6) return;
        fetch('https://api.postalpincode.in/pincode/' + p).then(function (r) { return r.json(); }).then(function (j) {
            var po = j && j[0] && j[0].PostOffice && j[0].PostOffice[0]; if (!po) return;
            var c = document.getElementById('b2City'), s = document.getElementById('b2State');
            if (!c.value) c.value = po.District || ''; if (!s.value) s.value = po.State || '';
        }).catch(function () {});
    });

    document.getElementById('b2GetRates').addEventListener('click', function () {
        var btn = this, fd = new FormData();
        fd.append('_token', form.querySelector('input[name=_token]').value);
        fd.append('pickup_warehouse_id', document.getElementById('b2Pick').value);
        fd.append('to_pincode', document.getElementById('b2To').value.trim());
        fd.append('invoice_amount', document.getElementById('b2Amt').value);
        fd.append('payment_mode', payment());
        fd.append('cod_amount', document.getElementById('b2Cod').value || 0);
        fd.append('risk_type_id', form.querySelector('select[name=risk_type_id]').value);
        boxes.querySelectorAll('.b2-box input').forEach(function (i) { fd.append(i.name, i.value); });
        clearRates('Fetching rates…'); btn.disabled = true;
        fetch(@json(route('seller.b2b.order.quote')), {method: 'POST', body: fd, headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (r) { return r.json().then(function (j) { return {s: r.status, j: j}; }); })
            .then(function (x) {
                btn.disabled = false;
                if (x.s === 422 && x.j.errors) { msg.textContent = Object.values(x.j.errors)[0][0]; return; }
                if (!x.j.ok) { msg.textContent = x.j.error || x.j.message || 'Could not get rates.'; return; }
                if (!x.j.rates.length) { msg.textContent = 'No courier is available for this route and weight.'; return; }
                msg.textContent = 'Choose a courier:';
                rates.innerHTML = x.j.rates.map(function (r, i) {
                    return '<label class="b2-rate"><input type="radio" name="_rate" value="' + i + '">' +
                        '<div><div class="nm">' + esc(r.courier_name) + '</div><div class="mt">' +
                        (r.tat ? r.tat + ' day' + (r.tat > 1 ? 's' : '') + ' · ' : '') +
                        (r.charged_weight ? 'charged ' + r.charged_weight + ' kg' : '') + '</div>' +
                        (r.note ? '<div class="mt">' + esc(r.note) + '</div>' : '') + '</div>' +
                        '<div class="pr">' + money(r.price) + '</div></label>';
                }).join('');
                rates.querySelectorAll('input').forEach(function (inp) {
                    inp.addEventListener('change', function () {
                        var r = x.j.rates[+inp.value];
                        rates.querySelectorAll('.b2-rate').forEach(function (l) { l.classList.remove('on'); });
                        inp.closest('.b2-rate').classList.add('on');
                        cId.value = r.courier_id; cPr.value = r.price; book.disabled = false;
                        book.innerHTML = '<i class="ti ti-check"></i> Book for ' + money(r.price);
                    });
                });
            })
            .catch(function () { btn.disabled = false; msg.textContent = 'Network error - please try again.'; });
    });
    form.addEventListener('submit', function () { book.disabled = true; book.textContent = 'Booking…'; });

    totals(); toggles();
})();
</script>
@endif
@endsection
