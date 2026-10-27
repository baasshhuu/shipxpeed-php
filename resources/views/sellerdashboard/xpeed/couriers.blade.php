@extends('layouts.sellerdash')

@section('content')
@php
    $ordered = array_values(array_unique(array_merge(array_intersect($prefs->brand_order, $brands), $brands)));
    $edit = null;
    if (request('edit')) { $edit = $rules->firstWhere('id', (int) request('edit')); }
@endphp
@include('sellerdashboard.xpeed._style')
<style>
    .xp .brand-row { display:flex; align-items:center; gap:8px; padding:7px 10px; border:1px solid rgba(120,130,150,.22); border-radius:8px; margin-bottom:6px; }
    .xp .brand-row .pos { width:22px; text-align:center; font-weight:700; opacity:.7; }
    .xp .brand-row .mv { margin-left:auto; display:flex; gap:4px; }
    .xp .mode-opt { border:1px solid rgba(120,130,150,.25); border-radius:10px; padding:10px 12px; cursor:pointer; display:block; height:100%; }
    .xp .mode-opt input { margin-right:6px; }
    .xp .mode-opt:has(input:checked) { border-color:var(--xp-accent); background:var(--xp-soft); }
</style>
<div class="pc-container xp">
<div class="pc-content">
    <div class="xp-head">
        <div>
            <h1><i class="ti ti-truck"></i> Courier preferences</h1>
            <p>Decide which courier comes first when you ship, set automatic rules (for example "COD above ₹2,000 → Delhivery"), and block pincodes you don't want to deliver to. Applies to single and bulk shipping.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('xp.couriers.prefs') }}">
        @csrf
        <div class="row">
            <div class="col-lg-7">
                <div class="xp-card">
                    <h2>1 · Courier priority</h2>
                    <p class="xp-sub">The courier on top is marked as the recommended one on the shipping screen.</p>
                    <div class="row g-2 mb-3">
                        @foreach(['cheapest' => ['Cheapest first', 'Lowest total charge on top'], 'fastest' => ['Fastest first', 'Air / express services and shortest delivery time on top'], 'custom' => ['My order', 'Your own courier ranking (below)']] as $k => [$t, $d])
                            <div class="col-md-4">
                                <label class="mode-opt"><input type="radio" name="mode" value="{{ $k }}" @checked($prefs->mode === $k) onchange="document.getElementById('xpCustom').style.opacity = this.value === 'custom' ? 1 : .5"><b>{{ $t }}</b><div class="small text-muted mt-1">{{ $d }}</div></label>
                            </div>
                        @endforeach
                    </div>
                    <div id="xpCustom" style="opacity:{{ $prefs->mode === 'custom' ? 1 : .5 }}">
                        <div class="small fw-semibold mb-2">Your courier ranking (used when "My order" is selected)</div>
                        <div id="xpBrands">
                            @foreach($ordered as $b)
                                <div class="brand-row"><span class="pos"></span><span>{{ $b }}</span><input type="hidden" name="brand_order[]" value="{{ $b }}">
                                    <span class="mv"><button type="button" class="btn btn-sm btn-outline-secondary py-0" onclick="xpMove(this,-1)">↑</button><button type="button" class="btn btn-sm btn-outline-secondary py-0" onclick="xpMove(this,1)">↓</button></span></div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="xp-card">
                    <h2>2 · Pincode block list</h2>
                    <p class="xp-sub">No courier will be offered for orders going to these pincodes (fraud-prone or non-serviceable areas).</p>
                    <textarea name="blocked_pincodes" rows="5" class="form-control mono" placeholder="110001, 400001">{{ str_replace(',', ', ', $prefs->blocked_raw) }}</textarea>
                    <div class="small text-muted mt-1">{{ count($prefs->blocked) }} pincode(s) blocked</div>

                    <details class="mt-3">
                        <summary class="small fw-semibold text-primary">Block pincodes for one courier only ▾</summary>
                        <p class="xp-sub mt-2">That courier is hidden for these pincodes; other couriers still show.</p>
                        @foreach($brands as $b)
                            @php $pins = (array) ($prefs->courier_blocks[$b] ?? []); @endphp
                            <label class="form-label mt-1">{{ $b }} @if($pins)<span class="pill warn">{{ count($pins) }}</span>@endif</label>
                            <textarea name="block_brand[{{ $b }}]" rows="1" class="form-control form-control-sm mono" placeholder="pincodes">{{ implode(', ', $pins) }}</textarea>
                        @endforeach
                    </details>

                    <button class="btn btn-xp mt-3 w-100" @disabled(!$xpReady)>Save priority &amp; block list</button>
                </div>

                <div class="xp-card">
                    <h2>Zone lookup</h2>
                    <p class="xp-sub">Check which rate-card zone a route falls in.</p>
                    <div class="d-flex gap-2">
                        <input id="zf" class="form-control form-control-sm" maxlength="6" placeholder="Pickup pincode">
                        <input id="zt" class="form-control form-control-sm" maxlength="6" placeholder="Delivery pincode">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="xpZone()">Check</button>
                    </div>
                    <div id="zr" class="small mt-2"></div>
                </div>
            </div>
        </div>
    </form>

    <div class="xp-card">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div><h2>3 · Courier rules</h2><p class="xp-sub mb-0">Checked from the lowest priority number up; the first rule that matches the order puts its courier on top as "Your rule".</p></div>
        </div>
        <div class="table-responsive mt-2">
            <table class="table table-sm mb-0">
                <thead><tr><th>#</th><th>Rule</th><th>When</th><th>Courier</th><th>Status</th><th class="text-end"></th></tr></thead>
                <tbody>
                @forelse($rules as $r)
                    <tr>
                        <td>{{ $r->priority }}</td>
                        <td class="fw-semibold">{{ $r->name }}</td>
                        <td class="small">
                            {{ ['any' => 'Any payment', 'cod' => 'COD', 'prepaid' => 'Prepaid'][$r->payment] }}
                            @if($r->min_value !== null || $r->max_value !== null) · value {{ $r->min_value !== null ? '≥ ₹' . (float) $r->min_value : '' }} {{ $r->max_value !== null ? '≤ ₹' . (float) $r->max_value : '' }}@endif
                            @if($r->min_weight_g !== null || $r->max_weight_g !== null) · weight {{ $r->min_weight_g !== null ? '≥ ' . $r->min_weight_g / 1000 . ' kg' : '' }} {{ $r->max_weight_g !== null ? '≤ ' . $r->max_weight_g / 1000 . ' kg' : '' }}@endif
                            @if($r->zones) · zones {{ $r->zones }}@endif
                            @if($r->pincodes) · {{ count(explode(',', $r->pincodes)) }} pincode(s)@endif
                        </td>
                        <td>{{ $r->courier_brand }} <span class="text-muted small">{{ $r->courier_mode === 'any' ? '' : ucfirst($r->courier_mode) }}</span></td>
                        <td><span class="pill {{ $r->active ? 'ok' : 'muted' }}">{{ $r->active ? 'On' : 'Paused' }}</span></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('xp.couriers', ['edit' => $r->id]) }}#ruleForm" class="btn btn-sm btn-outline-secondary py-0">Edit</a>
                            <form method="POST" action="{{ route('xp.couriers.rules.toggle', $r->id) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary py-0">{{ $r->active ? 'Pause' : 'Turn on' }}</button></form>
                            <form method="POST" action="{{ route('xp.couriers.rules.delete', $r->id) }}" class="d-inline" onsubmit="return confirm('Delete this rule?')">@csrf<button class="btn btn-sm btn-outline-danger py-0">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="xp-empty"><i class="ti ti-route"></i>No rules yet. Add one below.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('xp.couriers.rules') }}" class="mt-3 pt-3" id="ruleForm" style="border-top:1px solid rgba(120,130,150,.2)">
            @csrf
            <input type="hidden" name="id" value="{{ $edit->id ?? '' }}">
            <div class="fw-semibold mb-2">{{ $edit ? 'Edit rule' : 'Add a rule' }}</div>
            <div class="row g-2">
                <div class="col-md-5"><label class="form-label">Rule name</label><input name="name" class="form-control form-control-sm" required maxlength="120" value="{{ old('name', $edit->name ?? '') }}" placeholder="e.g. High value COD"></div>
                <div class="col-md-2"><label class="form-label">Priority</label><input type="number" name="priority" min="1" max="999" class="form-control form-control-sm" value="{{ old('priority', $edit->priority ?? ($rules->count() + 1) * 10) }}"></div>
                <div class="col-md-3"><label class="form-label">Use courier</label>
                    <select name="courier_brand" class="form-select form-select-sm" required>
                        @foreach($brands as $b)<option value="{{ $b }}" @selected(old('courier_brand', $edit->courier_brand ?? '') === $b)>{{ $b }}</option>@endforeach
                    </select></div>
                <div class="col-md-2"><label class="form-label">Service</label>
                    <select name="courier_mode" class="form-select form-select-sm">
                        @foreach(['any' => 'Any', 'surface' => 'Surface', 'air' => 'Air / express'] as $k => $t)<option value="{{ $k }}" @selected(old('courier_mode', $edit->courier_mode ?? 'any') === $k)>{{ $t }}</option>@endforeach
                    </select></div>
                <div class="col-md-2"><label class="form-label">Payment</label>
                    <select name="payment" class="form-select form-select-sm">
                        @foreach(['any' => 'Any', 'cod' => 'COD', 'prepaid' => 'Prepaid'] as $k => $t)<option value="{{ $k }}" @selected(old('payment', $edit->payment ?? 'any') === $k)>{{ $t }}</option>@endforeach
                    </select></div>
                <div class="col-md-2"><label class="form-label">Order value from ₹</label><input type="number" step="0.01" min="0" name="min_value" class="form-control form-control-sm" value="{{ old('min_value', isset($edit->min_value) ? (float) $edit->min_value : '') }}"></div>
                <div class="col-md-2"><label class="form-label">to ₹</label><input type="number" step="0.01" min="0" name="max_value" class="form-control form-control-sm" value="{{ old('max_value', isset($edit->max_value) ? (float) $edit->max_value : '') }}"></div>
                <div class="col-md-2"><label class="form-label">Weight from kg</label><input type="number" step="0.01" min="0" name="min_weight_kg" class="form-control form-control-sm" value="{{ old('min_weight_kg', isset($edit->min_weight_g) ? $edit->min_weight_g / 1000 : '') }}"></div>
                <div class="col-md-2"><label class="form-label">to kg</label><input type="number" step="0.01" min="0" name="max_weight_kg" class="form-control form-control-sm" value="{{ old('max_weight_kg', isset($edit->max_weight_g) ? $edit->max_weight_g / 1000 : '') }}"></div>
                <div class="col-md-2"><label class="form-label">Zones</label>
                    <div class="d-flex flex-wrap gap-2 pt-1">
                        @php $z = old('zones', $edit && $edit->zones ? explode(',', $edit->zones) : []); @endphp
                        @foreach($zones as $zn)<label class="small"><input type="checkbox" name="zones[]" value="{{ $zn }}" @checked(in_array($zn, (array) $z))> {{ $zn }}</label>@endforeach
                    </div></div>
                <div class="col-12"><label class="form-label">Only these delivery pincodes (optional)</label><input name="pincodes" class="form-control form-control-sm mono" value="{{ old('pincodes', $edit ? str_replace(',', ', ', (string) $edit->pincodes) : '') }}" placeholder="leave empty for all pincodes"></div>
            </div>
            <div class="mt-2 d-flex gap-2">
                <button class="btn btn-xp btn-sm" @disabled(!$xpReady)>{{ $edit ? 'Save rule' : 'Add rule' }}</button>
                @if($edit)<a href="{{ route('xp.couriers') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>@endif
            </div>
        </form>
    </div>
</div>
</div>
<script>
function xpNumber() { document.querySelectorAll('#xpBrands .pos').forEach((p, i) => p.textContent = i + 1); }
function xpMove(btn, d) {
    var row = btn.closest('.brand-row'), box = row.parentNode;
    if (d < 0 && row.previousElementSibling) box.insertBefore(row, row.previousElementSibling);
    if (d > 0 && row.nextElementSibling) box.insertBefore(row.nextElementSibling, row);
    xpNumber();
}
xpNumber();
function xpZone() {
    var f = document.getElementById('zf').value.trim(), t = document.getElementById('zt').value.trim(), r = document.getElementById('zr');
    if (!/^[1-9][0-9]{5}$/.test(f) || !/^[1-9][0-9]{5}$/.test(t)) { r.textContent = 'Enter two valid 6-digit pincodes.'; return; }
    r.textContent = 'Checking…';
    fetch('{{ route('xp.zone') }}?from=' + f + '&to=' + t, { headers: { 'Accept': 'application/json' } })
        .then(x => x.json()).then(d => {
            r.innerHTML = d.zone ? '<span class="pill info">Zone ' + d.zone + '</span> ' + (d.zone_name || '') + '<div class="text-muted">' + (d.from_state || '?') + ' → ' + (d.to_state || '?') + '</div>'
                : 'Zone not found for this route' + (d.from_state || d.to_state ? ' (' + (d.from_state || '?') + ' → ' + (d.to_state || '?') + ')' : '') + '.';
        }).catch(() => r.textContent = 'Could not check right now.');
}
</script>
@endsection
