@extends('layouts.app')

@section('css')
<style>
    .zp-tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--sx-border); margin-bottom: 18px; overflow-x: auto; }
    .zp-tab { background: none; border: 0; border-bottom: 2px solid transparent; color: var(--sx-text-2); font-weight: 600; padding: 10px 16px; white-space: nowrap; cursor: pointer; }
    .zp-tab:hover { color: var(--sx-text); }
    .zp-tab.on { color: #9cc0ff; border-bottom-color: var(--sx-primary); }
    .zp-tab .n { color: var(--sx-muted); font-weight: 500; margin-left: 4px; }
    .zp-pane[hidden] { display: none !important; }
    .zp-scope { display: flex; flex-wrap: wrap; gap: 10px; align-items: end; }
    .zp-scope .form-select { min-width: 260px; }
    .zp-group-h td { background: var(--sx-surface-2) !important; color: var(--sx-text-2) !important; font-size: 12px !important; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
    .zp-key { color: var(--sx-muted); font-size: 12px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    .zp-layout { display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 16px; }
    .zp-list { max-height: 640px; overflow: auto; padding: 8px !important; }
    .zp-item { display: flex; justify-content: space-between; align-items: center; gap: 8px; width: 100%; text-align: left; background: none; border: 1px solid transparent; border-radius: 9px; padding: 9px 10px; color: var(--sx-text); cursor: pointer; }
    .zp-item:hover { background: var(--sx-surface-3); }
    .zp-item.on { background: var(--sx-primary-soft); border-color: var(--sx-primary); }
    .zp-item .l { font-weight: 600; font-size: 13.5px; }
    .zp-item .p { color: var(--sx-muted); font-size: 12px; }
    .zp-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .zp-dot.live { background: var(--sx-success); } .zp-dot.off { background: var(--sx-warning); } .zp-dot.none { background: var(--sx-muted); }
    .zp-grid input.form-control { min-width: 92px; min-height: 36px; padding: 6px 10px; font-variant-numeric: tabular-nums; }
    .zp-grid input.inherit::placeholder { color: var(--sx-text-2) !important; opacity: .55; font-style: italic; }
    .zp-grid td, .zp-grid th { padding: 8px 10px !important; }
    .zp-zone { font-weight: 700; font-size: 15px; }
    .zp-logic { background: var(--sx-surface-2); border: 1px solid var(--sx-border); border-radius: 10px; padding: 14px 16px; font-size: 13px; color: var(--sx-text-2); }
    .zp-logic b { color: var(--sx-text); }
    .zp-logic ul { margin: 6px 0 0; padding-left: 18px; }
    .zp-calc { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
    .zp-result { margin-top: 12px; padding: 12px 14px; border-radius: 10px; background: var(--sx-primary-soft); border: 1px solid var(--sx-primary); }
    .zp-result .big { font-size: 22px; font-weight: 700; color: var(--sx-text); font-variant-numeric: tabular-nums; }
    .zp-result .why { color: var(--sx-text-2); font-size: 12.5px; margin-top: 2px; }
    .zp-step { display: inline-grid; place-items: center; width: 24px; height: 24px; border-radius: 50%; background: var(--sx-primary-soft); color: #9cc0ff; font-weight: 700; font-size: 12px; margin-right: 8px; }
    @media (max-width: 991px) { .zp-layout { grid-template-columns: minmax(0, 1fr); } .zp-list { max-height: 260px; } .zp-calc { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>
@endsection

@section('content')
@php
    $liveCount = collect($couriers)->where('live', true)->count();
    $intCount = collect($couriers)->where('integrated', true)->count();
    $scopeLabel = $scope === 'default' ? 'All sellers' : ($sellers->firstWhere('id', $sellerId)->name ?? 'Seller');
    $statusBadge = function ($c) {
        if ($c['live']) return '<span class="badge bg-success">Live</span>';
        if ($c['integrated']) return '<span class="badge bg-warning">Provider off</span>';
        return '<span class="badge bg-secondary">Not integrated</span>';
    };
@endphp

<div class="sx-page-head">
    <div>
        <h2>Zone Price Setting</h2>
        <p>Choose which couriers each seller can use and set their zone-wise rates. Changes apply to the seller panel immediately.</p>
    </div>
    <form method="GET" action="{{ route('zone.pricesetting.add') }}" class="zp-scope" id="scopeForm">
        <input type="hidden" name="tab" id="scopeTab" value="{{ $tab }}">
        <div>
            <label class="form-label" for="scopeSeller">Apply to</label>
            <select class="form-select" name="seller" id="scopeSeller" onchange="this.form.submit()">
                <option value="default" @selected($scope === 'default')>All sellers (default rate card)</option>
                @foreach ($sellers as $s)
                    <option value="{{ $s->id }}" @selected($scope === (string) $s->id)>{{ $s->name ?: 'Seller' }} · #{{ $s->id }}</option>
                @endforeach
            </select>
        </div>
    </form>
</div>

<div class="zp-tabs" role="tablist">
    <button class="zp-tab" data-tab="couriers" role="tab">Couriers <span class="n">{{ $liveCount }} live</span></button>
    <button class="zp-tab" data-tab="rates" role="tab">Rates</button>
    <button class="zp-tab" data-tab="bulk" role="tab">Bulk upload</button>
</div>

{{-- ============ COURIERS ============ --}}
<section class="zp-pane" id="pane-couriers">
    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <div>
                <h5>Couriers for {{ $scopeLabel }}</h5>
                <small class="text-muted">
                    @if ($scope === 'default')
                        A switch here turns the courier on or off for every seller. Pick a seller above to change just one.
                    @else
                        Switch a courier off to hide it from this seller. Their other sellers are not affected.
                    @endif
                </small>
            </div>
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="showNotIntegrated">
                <label class="form-check-label" for="showNotIntegrated">Show not integrated ({{ count($couriers) - $intCount }})</label>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Courier</th>
                            <th>Provider</th>
                            <th>Integration</th>
                            <th>Rates</th>
                            <th class="text-center">{{ $scope === 'default' ? 'On for sellers' : 'Visible to seller' }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($couriers as $key => $c)
                            @php
                                $own = count($ownRates[$key] ?? []);
                                $def = count($defaultRates[$key] ?? []);
                                $st = $state[$key] ?? ['checked' => false];
                            @endphp
                            <tr class="zp-row {{ $c['integrated'] ? '' : 'zp-not-int' }}" @if (!$c['integrated']) hidden @endif>
                                <td>
                                    <div class="fw-semibold">{{ $c['label'] }}</div>
                                    <div class="zp-key">{{ $key }}</div>
                                </td>
                                <td>{{ $c['provider_name'] }}</td>
                                <td>{!! $statusBadge($c) !!}</td>
                                <td>
                                    @if ($scope !== 'default' && $own)
                                        <span class="badge bg-primary">Own rates · {{ $own }} zones</span>
                                    @elseif ($def)
                                        <span class="badge bg-success">Default rates · {{ $def }} zones</span>
                                    @elseif ($c['fallback'] === 'markup')
                                        <span class="badge bg-secondary" title="No rate card yet: priced at the provider's cost plus your markup">Markup pricing</span>
                                    @else
                                        <span class="badge bg-danger" title="Sellers cannot book this courier until it has rates">No rates</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <label class="sx-switch" title="{{ $c['mode'] === 'optout' ? 'On by default for every seller' : 'Off until switched on' }}">
                                        <input type="checkbox" class="zp-toggle" data-key="{{ $key }}" @checked($st['checked'])>
                                        <span class="s"></span>
                                    </label>
                                    @if ($scope === 'default')
                                        <div class="text-muted" style="font-size:12px" data-count="{{ $key }}">{{ $st['on'] ?? 0 }} of {{ $st['of'] ?? 0 }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary zp-edit-rates" data-key="{{ $key }}">Edit rates</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No couriers found. Add a courier provider in Logistics first.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <p class="text-muted mt-2" style="font-size:12.5px">
        <b>Live</b>: the provider is connected and switched on in <a href="{{ route('logistics') }}">Logistics Partners</a>.
        <b>Provider off</b>: connected but switched off there, so no seller sees it.
        Shipclues and Fship couriers are on for every seller unless you switch them off here; direct couriers stay off until you switch them on.
    </p>
</section>

{{-- ============ RATES ============ --}}
<section class="zp-pane" id="pane-rates" hidden>
    <div class="zp-layout">
        <div class="card">
            <div class="card-header"><h5>Courier</h5></div>
            <div class="card-body zp-list" id="rateCourierList">
                @foreach ($couriers as $key => $c)
                    <button type="button" class="zp-item {{ $c['integrated'] ? '' : 'zp-not-int' }}" data-key="{{ $key }}" @if (!$c['integrated']) hidden @endif>
                        <span>
                            <span class="l d-block">{{ $c['label'] }}</span>
                            <span class="p d-block">{{ $c['provider_name'] }}</span>
                        </span>
                        <span class="zp-dot {{ $c['live'] ? 'live' : ($c['integrated'] ? 'off' : 'none') }}" title="{{ $c['live'] ? 'Live' : ($c['integrated'] ? 'Provider off' : 'Not integrated') }}"></span>
                    </button>
                @endforeach
                <div class="form-check form-switch mt-2 ms-1">
                    <input class="form-check-input" type="checkbox" id="showNotIntegrated2">
                    <label class="form-check-label" for="showNotIntegrated2">Show not integrated</label>
                </div>
            </div>
        </div>

        <div class="d-grid gap-3" style="min-width:0">
            <div class="card">
                <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <div>
                        <h5 id="gridTitle">Rates</h5>
                        <small class="text-muted" id="gridSub"></small>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="fillFromFirst" title="Copy the first zone's values into every zone">Copy first zone to all</button>
                        <button type="button" class="btn btn-sm btn-primary" id="saveGrid"><i class="fa-solid fa-floppy-disk me-1"></i>Save rates</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 zp-grid">
                            <thead>
                                <tr>
                                    <th>Zone</th>
                                    <th title="Freight per 500 g slab for prepaid orders, before GST">Prepaid ₹/500 g</th>
                                    <th title="Added to the prepaid rate for COD orders, per 500 g slab, before GST">COD extra ₹/500 g</th>
                                    <th title="If set, prepaid orders pay this flat amount per 500 g slab. GST included, nothing else added">Prepaid fixed ₹</th>
                                    <th title="If set, COD orders pay this flat amount per 500 g slab. GST and COD % included">COD fixed ₹</th>
                                    <th title="Charged on order value above ₹1,400 for COD orders">COD %</th>
                                    <th>RTO credit ₹</th>
                                    <th>Source</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="gridBody">
                                @foreach ($zones as $z)
                                    <tr data-zone="{{ $z }}">
                                        <td>
                                            <span class="zp-zone">{{ $z }}</span>
                                            @if (!in_array($z, $routeZones, true))
                                                <div class="text-muted" style="font-size:11px" title="No pickup → delivery state pair maps to this zone yet">no routes</div>
                                            @endif
                                        </td>
                                        @foreach (['prepaid_price', 'cod_price', 'prepaid_fix_price', 'cod_fix_price', 'cod_charge_parsent', 'rto_credit'] as $f)
                                            <td><input type="number" min="0" step="0.01" inputmode="decimal" class="form-control" data-f="{{ $f }}" id="g-{{ $z }}-{{ $f }}" aria-label="Zone {{ $z }} {{ $f }}"></td>
                                        @endforeach
                                        <td class="zp-src"></td>
                                        <td><button type="button" class="btn btn-sm btn-outline-secondary zp-clear" title="Clear this zone">✕</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header"><h5>Price check</h5></div>
                        <div class="card-body">
                            <div class="zp-calc">
                                <div>
                                    <label class="form-label" for="cZone">Zone</label>
                                    <select class="form-select" id="cZone">
                                        @foreach ($zones as $z)<option>{{ $z }}</option>@endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" for="cWeight">Weight (g)</label>
                                    <input class="form-control" id="cWeight" type="number" min="1" value="500">
                                </div>
                                <div>
                                    <label class="form-label" for="cPay">Payment</label>
                                    <select class="form-select" id="cPay"><option value="prepaid">Prepaid</option><option value="cod">COD</option></select>
                                </div>
                                <div>
                                    <label class="form-label" for="cAmount">Order value ₹</label>
                                    <input class="form-control" id="cAmount" type="number" min="0" value="1000">
                                </div>
                            </div>
                            <div class="zp-result" id="cResult"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="zp-logic h-100">
                        <b>How the seller is charged</b>
                        <ul>
                            <li>Weight is billed in 500 g slabs, rounded up (1.2 kg = 3 slabs).</li>
                            <li><b>Prepaid:</b> prepaid rate × slabs + 18% GST.</li>
                            <li><b>COD:</b> (prepaid rate + COD extra) × slabs, plus COD % of the order value when it is above ₹1,400, plus 18% GST.</li>
                            <li><b>Fixed price</b> replaces the above: fixed × slabs, nothing added.</li>
                            <li>A seller without their own rate uses the <b>default rate card</b> for that courier and zone.</li>
                            <li>Shipclues/Fship couriers without any rate use the provider cost plus your markup.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============ BULK ============ --}}
<section class="zp-pane" id="pane-bulk" hidden>
    @if ($importResult)
        <div class="alert {{ count($importResult['skipped']) ? 'alert-warning' : 'alert-success' }}" style="background:var(--sx-surface-2);color:var(--sx-text)">
            <b>{{ $importResult['saved'] }} rate row{{ $importResult['saved'] === 1 ? '' : 's' }} saved</b> for {{ $importResult['who'] }}.
            @if (count($importResult['skipped']))
                {{ count($importResult['skipped']) }} skipped:
                <ul class="mb-0 mt-1">
                    @foreach (array_slice($importResult['skipped'], 0, 30) as $line)<li>{{ $line }}</li>@endforeach
                    @if (count($importResult['skipped']) > 30)<li>…and {{ count($importResult['skipped']) - 30 }} more</li>@endif
                </ul>
            @endif
        </div>
    @endif
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h5><span class="zp-step">1</span>Download template</h5></div>
                <form class="card-body d-grid gap-3" method="GET" action="{{ route('zone.price.rate.template') }}">
                    <div>
                        <label class="form-label" for="tSeller">Fill with current rates of</label>
                        <select class="form-select" name="seller" id="tSeller">
                            <option value="default" @selected($scope === 'default')>Default rate card</option>
                            @foreach ($sellers as $s)
                                <option value="{{ $s->id }}" @selected($scope === (string) $s->id)>{{ $s->name ?: 'Seller' }} · #{{ $s->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="tCouriers">Couriers in the file</label>
                        <select class="form-select" name="couriers" id="tCouriers">
                            <option value="live">Live couriers ({{ $liveCount }})</option>
                            <option value="integrated">All integrated couriers ({{ $intCount }})</option>
                            <option value="all">Every courier ({{ count($couriers) }})</option>
                        </select>
                    </div>
                    <p class="text-muted mb-0" style="font-size:12.5px">One row per courier and zone ({{ implode(', ', $zones) }}). Keep the <b>zone</b> and <b>logistic_provider</b> columns as they are, edit the prices, and leave a row blank to skip it.</p>
                    <button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-download me-1"></i>Download Excel template</button>
                </form>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h5><span class="zp-step">2</span>Upload rates</h5></div>
                <form class="card-body d-grid gap-3" method="POST" action="{{ route('zone.price.rate.upload') }}" enctype="multipart/form-data" id="uploadForm">
                    @csrf
                    <div>
                        <label class="form-label" for="uTarget">Save these rates for</label>
                        <select class="form-select" name="target" id="uTarget">
                            <option value="default" @selected($scope === 'default')>Default rate card (sellers without their own rates)</option>
                            @foreach ($sellers as $s)
                                <option value="{{ $s->id }}" @selected($scope === (string) $s->id)>{{ $s->name ?: 'Seller' }} · #{{ $s->id }} only</option>
                            @endforeach
                            <option value="every">Every seller individually ({{ $sellers->count() }})</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="uFile">Excel or CSV file</label>
                        <input class="form-control" type="file" name="file" id="uFile" accept=".xlsx,.xls,.csv" required>
                    </div>
                    <p class="text-muted mb-0" style="font-size:12.5px">Existing rates for the same courier and zone are replaced. Rows with an unknown courier or zone are skipped and listed here after upload.</p>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-upload me-1"></i>Upload rates</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@section('js')
<script>
$(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');
    const SCOPE = @json($scope);
    const SCOPE_LABEL = @json($scopeLabel);
    const COURIERS = @json($couriers);
    const OWN = @json((object) $ownRates);
    const DEF = @json((object) $defaultRates);
    const ZONES = @json($zones);
    const FIELDS = ['prepaid_price', 'cod_price', 'prepaid_fix_price', 'cod_fix_price', 'cod_charge_parsent', 'rto_credit'];
    let currentKey = null;

    /* ---------- tabs ---------- */
    function showTab(t) {
        $('.zp-tab').removeClass('on').filter('[data-tab="' + t + '"]').addClass('on');
        $('.zp-pane').attr('hidden', true);
        $('#pane-' + t).removeAttr('hidden');
        $('#scopeTab').val(t);
        const url = new URL(location); url.searchParams.set('tab', t); history.replaceState(null, '', url);
    }
    $('.zp-tab').on('click', function () { showTab($(this).data('tab')); });
    showTab(@json($tab));

    /* ---------- not-integrated filter ---------- */
    function syncNotInt(on) {
        $('#showNotIntegrated, #showNotIntegrated2').prop('checked', on);
        $('.zp-not-int').each(function () { on ? $(this).removeAttr('hidden') : $(this).attr('hidden', true); });
    }
    $('#showNotIntegrated, #showNotIntegrated2').on('change', function () { syncNotInt(this.checked); });

    /* ---------- courier switches ---------- */
    $(document).on('change', '.zp-toggle', function () {
        const $el = $(this), on = $el.is(':checked'), key = $el.data('key');
        $el.prop('disabled', true);
        $.post("{{ route('zone.price.courier.toggle') }}", { _token: csrf, seller: SCOPE, key: key, on: on ? 1 : 0 })
            .done(res => {
                toastr.success(res.message);
                const $c = $('[data-count="' + key + '"]');
                if ($c.length) { const of = parseInt($c.text().split('of')[1]); $c.text((on ? of : 0) + ' of ' + of); }
            })
            .fail(xhr => { $el.prop('checked', !on); toastr.error(xhr.responseJSON?.message || 'Could not save. Please try again.'); })
            .always(() => $el.prop('disabled', false));
    });
    $(document).on('click', '.zp-edit-rates', function () { selectCourier($(this).data('key')); showTab('rates'); });

    /* ---------- rates grid ---------- */
    const fmt = v => (v === undefined || v === null) ? '' : String(+v);
    function selectCourier(key) {
        if (!COURIERS[key]) return;
        currentKey = key;
        const c = COURIERS[key];
        if (!c.integrated) syncNotInt(true);
        $('.zp-item').removeClass('on').filter('[data-key="' + key + '"]').addClass('on');
        $('#gridTitle').text(c.label + ' · ' + c.provider_name);
        $('#gridSub').text(SCOPE === 'default'
            ? 'Default rate card: used for every seller who has no rate of their own.'
            : 'Rates for ' + SCOPE_LABEL + '. Faded values come from the default rate card; type a value to override it.');
        const own = OWN[key] || {}, def = DEF[key] || {};
        ZONES.forEach(z => {
            const $tr = $('#gridBody tr[data-zone="' + z + '"]');
            FIELDS.forEach(f => {
                const $i = $tr.find('[data-f="' + f + '"]');
                $i.val(own[z] ? fmt(own[z][f]) : '');
                const inherit = SCOPE !== 'default' && !own[z] && def[z];
                $i.attr('placeholder', inherit ? fmt(def[z][f]) : '0').toggleClass('inherit', !!inherit);
            });
            markSource($tr);
        });
        calc();
    }
    function rowValues($tr) {
        const v = {}; let any = false;
        FIELDS.forEach(f => { const x = $tr.find('[data-f="' + f + '"]').val(); v[f] = x === '' ? null : x; if (x !== '') any = true; });
        return { v, any };
    }
    function markSource($tr) {
        const z = $tr.data('zone'), { any } = rowValues($tr), def = (DEF[currentKey] || {})[z];
        let html;
        if (any) html = SCOPE === 'default' ? '<span class="badge bg-success">Default</span>' : '<span class="badge bg-primary">Own</span>';
        else if (SCOPE !== 'default' && def) html = '<span class="badge bg-secondary">From default</span>';
        else html = '<span class="badge bg-danger">Not set</span>';
        $tr.find('.zp-src').html(html);
    }
    $('#rateCourierList').on('click', '.zp-item', function () { selectCourier($(this).data('key')); });
    $('#gridBody').on('input', 'input', function () { markSource($(this).closest('tr')); calc(); });
    $('#gridBody').on('click', '.zp-clear', function () { const $tr = $(this).closest('tr'); $tr.find('input').val(''); markSource($tr); calc(); });
    $('#fillFromFirst').on('click', function () {
        const $first = $('#gridBody tr').first(), { v, any } = rowValues($first);
        if (!any) { toastr.info('Fill in the first zone, then copy it.'); return; }
        $('#gridBody tr').not($first).each(function () { const $tr = $(this); FIELDS.forEach(f => $tr.find('[data-f="' + f + '"]').val(v[f] ?? '')); markSource($tr); });
        calc();
    });
    $('#saveGrid').on('click', function () {
        if (!currentKey) return;
        const rows = {};
        $('#gridBody tr').each(function () { rows[$(this).data('zone')] = rowValues($(this)).v; });
        const $b = $(this).prop('disabled', true);
        $.ajax({ url: "{{ route('zone.price.save.grid') }}", type: 'POST', contentType: 'application/json',
            data: JSON.stringify({ _token: csrf, seller: SCOPE, key: currentKey, rows }),
            headers: { 'X-CSRF-TOKEN': csrf } })
            .done(res => {
                toastr.success(res.message);
                const store = {}; Object.entries(rows).forEach(([z, v]) => { if (Object.values(v).some(x => x !== null)) store[z] = Object.fromEntries(FIELDS.map(f => [f, v[f] === null ? 0 : +v[f]])); });
                OWN[currentKey] = store; if (SCOPE === 'default') DEF[currentKey] = store;
                selectCourier(currentKey);
            })
            .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Could not save. Check the numbers and try again.'))
            .always(() => $b.prop('disabled', false));
    });

    /* ---------- price check (same maths as the courier services) ---------- */
    const money = n => '₹' + (Math.round(n * 100) / 100).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    function effectiveRow(z) {
        const $tr = $('#gridBody tr[data-zone="' + z + '"]'), { v, any } = rowValues($tr);
        if (any) return { r: Object.fromEntries(FIELDS.map(f => [f, +(v[f] || 0)])), src: SCOPE === 'default' ? 'default rate card' : 'own rate' };
        const d = (DEF[currentKey] || {})[z];
        if (SCOPE !== 'default' && d) return { r: d, src: 'default rate card' };
        return null;
    }
    function calc() {
        const $out = $('#cResult');
        if (!currentKey) { $out.html('<div class="why">Pick a courier to check a price.</div>'); return; }
        const z = $('#cZone').val(), w = Math.max(1, +$('#cWeight').val() || 0), cod = $('#cPay').val() === 'cod', amt = +$('#cAmount').val() || 0;
        const e = effectiveRow(z);
        if (!e) {
            const c = COURIERS[currentKey];
            $out.html('<div class="big">No rate</div><div class="why">' + (c.fallback === 'markup' ? 'Sellers are charged the provider cost plus your markup for zone ' + z + '.' : 'Sellers cannot book this courier in zone ' + z + ' until a rate is set.') + '</div>');
            return;
        }
        const r = e.r, slabs = Math.max(1, Math.ceil(w / 500));
        let total, why;
        if (cod && r.cod_fix_price > 0) { total = r.cod_fix_price * slabs; why = `COD fixed ${money(r.cod_fix_price)} × ${slabs} slab${slabs > 1 ? 's' : ''}`; }
        else if (!cod && r.prepaid_fix_price > 0) { total = r.prepaid_fix_price * slabs; why = `Prepaid fixed ${money(r.prepaid_fix_price)} × ${slabs} slab${slabs > 1 ? 's' : ''}`; }
        else if (cod) {
            const freight = (r.prepaid_price + r.cod_price) * slabs, codc = amt > 1400 ? amt * r.cod_charge_parsent / 100 : 0;
            total = (freight + codc) * 1.18;
            why = `(${money(r.prepaid_price)} + ${money(r.cod_price)}) × ${slabs} = ${money(freight)}` + (codc ? ` + COD ${r.cod_charge_parsent}% ${money(codc)}` : (amt > 1400 ? '' : ' (no COD % under ₹1,400)')) + ' + 18% GST';
        } else { const freight = r.prepaid_price * slabs; total = freight * 1.18; why = `${money(r.prepaid_price)} × ${slabs} = ${money(freight)} + 18% GST`; }
        $out.html(`<div class="big">${money(total)}</div><div class="why">${why} · from ${e.src}</div>`);
    }
    $('#cZone, #cWeight, #cPay, #cAmount').on('input change', calc);

    // Start on the first live courier (or the one in the URL hash)
    const start = (location.hash || '').slice(1);
    const first = COURIERS[start] ? start : (Object.values(COURIERS).find(c => c.live) || Object.values(COURIERS)[0] || {}).key;
    if (first) selectCourier(first); else calc();

    $('#uploadForm').on('submit', function () { $(this).find('button[type=submit]').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Uploading…'); });
});
</script>
@endsection
