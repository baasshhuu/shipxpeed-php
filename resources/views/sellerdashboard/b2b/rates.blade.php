@extends('layouts.sellerdash')

@section('content')
@include('sellerdashboard.b2b._style')
<div class="b2">
    @include('sellerdashboard.b2b._tabs')
    @include('sellerdashboard.b2b._calc_switch', ['calc' => 'b2b'])

    <div class="b2-head">
        <div>
            <h1>B2B rate calculator</h1>
            <p>Compare courier prices for heavy and multi-box shipments. Prices include GST.</p>
        </div>
    </div>

    @unless($enabled)
        <div class="b2-alert warn">B2B shipping is being set up for your account. Please check back soon or contact support.</div>
    @else
    <form id="rcForm" onsubmit="return false">
        @csrf
        <div class="b2-split">
            <div>
                <div class="b2-card">
                    <h2><span class="n">1</span> Route</h2>
                    <div class="b2-grid">
                        <div class="b2-f">
                            <label>Pickup pincode</label>
                            <input id="rcFrom" inputmode="numeric" maxlength="6" value="{{ optional($warehouses->first())->pincode }}" list="rcWh" required>
                            @if($warehouses->isNotEmpty())
                                <datalist id="rcWh">
                                    @foreach($warehouses as $w)<option value="{{ $w->pincode }}">{{ $w->address_title ?: $w->name }} · {{ $w->city }}</option>@endforeach
                                </datalist>
                                <div class="hint">Your default warehouse is filled in. Type any pincode to compare.</div>
                            @endif
                        </div>
                        <div class="b2-f"><label>Delivery pincode</label><input id="rcTo" inputmode="numeric" maxlength="6" required></div>
                    </div>
                </div>

                <div class="b2-card">
                    <h2><span class="n">2</span> Boxes</h2>
                    <div class="b2-box b2-muted" style="margin-bottom:4px"><span>No. of boxes</span><span>Length (cm)</span><span>Breadth (cm)</span><span>Height (cm)</span><span>Weight / box (kg)</span><span></span></div>
                    <div id="rcBoxes">
                        <div class="b2-box b2-f">
                            <input type="number" data-k="count" value="1" min="1" max="999">
                            <input type="number" step="0.1" data-k="length" min="1" placeholder="L">
                            <input type="number" step="0.1" data-k="breadth" min="1" placeholder="B">
                            <input type="number" step="0.1" data-k="height" min="1" placeholder="H">
                            <input type="number" step="0.1" data-k="weight" min="0.1" placeholder="kg">
                            <button type="button" class="b2-x" title="Remove" data-rm>×</button>
                        </div>
                    </div>
                    <button type="button" class="b2-btn ghost sm" id="rcAdd"><i class="ti ti-plus"></i> Add box size</button>
                    <div class="b2-sum"><span>Total boxes: <b id="rcTB">0</b></span><span>Dead weight: <b id="rcTW">0</b> kg</span><span>Volumetric: <b id="rcTV">0</b> kg</span></div>
                </div>

                <div class="b2-card">
                    <h2><span class="n">3</span> Invoice &amp; payment</h2>
                    <div class="b2-grid">
                        <div class="b2-f"><label>Invoice value (₹)</label><input type="number" step="0.01" min="1" id="rcAmt" required></div>
                        <div class="b2-f">
                            <label>Payment</label>
                            <div class="b2-seg">
                                <label><input type="radio" name="rc_pay" value="prepaid" checked><span>Prepaid</span></label>
                                <label><input type="radio" name="rc_pay" value="cod"><span>COD</span></label>
                            </div>
                        </div>
                        <div class="b2-f" id="rcCodWrap" hidden><label>COD amount (₹)</label><input type="number" step="0.01" min="1" id="rcCod"></div>
                        <div class="b2-f">
                            <label>Transit risk</label>
                            <select id="rcRisk"><option value="2">Owner's risk (cheaper)</option><option value="3">Carrier's risk</option></select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="b2-sticky">
                <div class="b2-card">
                    <h2><i class="ti ti-truck"></i> Courier prices</h2>
                    <div id="rcMsg" class="b2-muted" style="margin-bottom:12px">Enter pincodes, boxes and invoice value, then get rates.</div>
                    <button type="button" class="b2-btn" id="rcGo" style="width:100%;margin-bottom:12px"><i class="ti ti-calculator"></i> Get rates</button>
                    <div class="b2-rates" id="rcRates"></div>
                    <a href="{{ route('seller.b2b.order') }}" class="b2-btn ghost" id="rcShip" style="width:100%;margin-top:12px" hidden><i class="ti ti-circle-plus"></i> Create B2B shipment</a>
                </div>
            </div>
        </div>
    </form>
    @endunless
</div>

@if($enabled)
<script>
(function () {
    var boxes = document.getElementById('rcBoxes'), msg = document.getElementById('rcMsg'), out = document.getElementById('rcRates');
    function money(n) { return '₹' + Number(n).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function pay() { return document.querySelector('input[name=rc_pay]:checked').value; }
    function totals() {
        var tb = 0, tw = 0, tv = 0;
        boxes.querySelectorAll('.b2-box').forEach(function (r) {
            var v = r.querySelectorAll('input'), n = +v[0].value || 0;
            tb += n; tw += n * Math.ceil(+v[4].value || 0);
            tv += n * ((+v[1].value || 0) * (+v[2].value || 0) * (+v[3].value || 0)) / 5000;
        });
        document.getElementById('rcTB').textContent = tb;
        document.getElementById('rcTW').textContent = tw;
        document.getElementById('rcTV').textContent = Math.ceil(tv);
        document.getElementById('rcCodWrap').hidden = pay() !== 'cod';
    }
    boxes.addEventListener('click', function (e) {
        if (e.target.hasAttribute('data-rm') && boxes.children.length > 1) { e.target.closest('.b2-box').remove(); totals(); }
    });
    document.getElementById('rcAdd').addEventListener('click', function () {
        var row = boxes.querySelector('.b2-box').cloneNode(true);
        row.querySelectorAll('input').forEach(function (i) { i.value = i.dataset.k === 'count' ? 1 : ''; });
        boxes.appendChild(row); totals();
    });
    document.getElementById('rcForm').addEventListener('input', totals);
    document.getElementById('rcForm').addEventListener('change', totals);

    document.getElementById('rcGo').addEventListener('click', function () {
        var btn = this, fd = new FormData();
        fd.append('_token', document.querySelector('#rcForm input[name=_token]').value);
        fd.append('from_pincode', document.getElementById('rcFrom').value.trim());
        fd.append('to_pincode', document.getElementById('rcTo').value.trim());
        fd.append('invoice_amount', document.getElementById('rcAmt').value);
        fd.append('payment_mode', pay());
        fd.append('cod_amount', document.getElementById('rcCod').value || 0);
        fd.append('risk_type_id', document.getElementById('rcRisk').value);
        boxes.querySelectorAll('.b2-box').forEach(function (r, i) {
            r.querySelectorAll('input').forEach(function (inp) { fd.append('boxes[' + i + '][' + inp.dataset.k + ']', inp.value); });
        });
        out.innerHTML = ''; document.getElementById('rcShip').hidden = true;
        msg.textContent = 'Fetching rates…'; btn.disabled = true;
        fetch(@json(route('seller.b2b.order.quote')), {method: 'POST', body: fd, headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (r) { return r.json().then(function (j) { return {s: r.status, j: j}; }); })
            .then(function (x) {
                btn.disabled = false;
                if (x.s === 422 && x.j.errors) { msg.textContent = Object.values(x.j.errors)[0][0]; return; }
                if (!x.j.ok) { msg.textContent = x.j.error || x.j.message || 'Could not get rates.'; return; }
                if (!x.j.rates.length) { msg.textContent = 'No courier is available for this route and weight.'; return; }
                msg.textContent = x.j.rates.length + ' courier' + (x.j.rates.length > 1 ? 's' : '') + ' available, cheapest first:';
                out.innerHTML = x.j.rates.map(function (r) {
                    return '<div class="b2-rate static"><div><div class="nm">' + esc(r.courier_name) + '</div><div class="mt">' +
                        (r.tat ? r.tat + ' day' + (r.tat > 1 ? 's' : '') + ' · ' : '') +
                        (r.charged_weight ? 'charged ' + r.charged_weight + ' kg' : '') + '</div>' +
                        (r.note ? '<div class="mt">' + esc(r.note) + '</div>' : '') + '</div>' +
                        '<div class="pr">' + money(r.price) + '</div></div>';
                }).join('');
                document.getElementById('rcShip').hidden = false;
            })
            .catch(function () { btn.disabled = false; msg.textContent = 'Network error - please try again.'; });
    });
    totals();
})();
</script>
@endif
@endsection
