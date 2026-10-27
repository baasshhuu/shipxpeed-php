@extends('layouts.app')

@section('css')
<style>
    .rm { --line: var(--sx-border, #2a3757); --card: var(--sx-surface-2, #18223a); --card2: var(--sx-surface-3, #1f2b47); --muted: var(--sx-muted, #8592ad); }
    .rm-top { display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end; }
    .rm-top .form-group { flex: 1 1 280px; max-width: 420px; }
    .rm-hint { border:1px solid var(--line); border-radius:10px; padding:10px 14px; margin:14px 0; font-size:13px; color: var(--sx-text-2, #b4bfd6); background: var(--sx-primary-soft, rgba(79,140,255,.12)); }
    .rm-bulk { display:grid; grid-template-columns: 1fr 1.4fr; gap:12px; margin-bottom: 8px; }
    @media (max-width: 900px) { .rm-bulk { grid-template-columns: 1fr; } }
    .rm-box { border:1px solid var(--line); background: var(--card); border-radius:12px; padding:14px 16px; }
    .rm-box h6 { margin:0 0 6px; font-size:14px; }
    .rm-box p { font-size:12.5px; color: var(--muted); margin:0 0 10px; }
    .rm-upload { display:flex; gap:8px; flex-wrap:wrap; }
    .rm-upload select, .rm-upload input[type=file] { flex: 1 1 200px; }
    .rm-group { margin-top: 22px; }
    .rm-group > h6 { font-size:15px; margin-bottom:10px; }
    details.rm-c { border:1px solid var(--line); background: var(--card); border-radius:12px; margin-bottom:10px; overflow:hidden; }
    details.rm-c[open] { background: var(--card); }
    details.rm-c summary { list-style:none; cursor:pointer; display:flex; align-items:center; gap:12px; padding:12px 14px; }
    details.rm-c summary::-webkit-details-marker { display:none; }
    .rm-c img { width:34px; height:34px; object-fit:contain; border-radius:8px; background:#fff; padding:3px; flex: 0 0 34px; }
    .rm-name { font-weight:600; }
    .rm-sub { font-size:12px; color: var(--muted); }
    .rm-pill { font-size:11px; font-weight:700; padding:3px 8px; border-radius:20px; margin-left:auto; white-space:nowrap; }
    .rm-pill.on { background: var(--sx-success-soft, rgba(34,197,94,.15)); color: var(--sx-success, #22c55e); }
    .rm-pill.off { background: var(--sx-danger-soft, rgba(240,79,95,.15)); color: var(--sx-danger, #f04f5f); }
    .rm-pill.warn { background: var(--sx-warning-soft, rgba(245,165,36,.15)); color: var(--sx-warning, #f5a524); margin-left:6px; }
    .rm-body { padding: 0 14px 14px; }
    .rm-table { width:100%; border-collapse:separate; border-spacing:0 6px; }
    .rm-table th { font-size:12px; font-weight:600; color: var(--muted); text-align:left; padding: 0 6px; }
    .rm-table td { padding: 0 6px; }
    .rm-table input { width:100%; min-width:80px; }
    .rm-table td.z { font-weight:600; white-space:nowrap; width:80px; }
    .rm-table .uses-def { font-size:11px; color: var(--muted); }
    .rm-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:8px; }
    .rm-result { border-radius:10px; padding:10px 14px; margin: 8px 0 4px; font-size:13px; border:1px solid var(--line); }
    .rm-result.ok { border-color: var(--sx-success, #22c55e); }
    .rm-result ul { margin:6px 0 0; padding-left:18px; color: var(--sx-warning, #f5a524); }
    .rm-toast { position:fixed; right:18px; bottom:18px; z-index:9999; padding:10px 16px; border-radius:10px; font-size:13px; color:#fff; background:#16a34a; box-shadow:0 8px 24px rgba(0,0,0,.35); display:none; }
    .rm-toast.err { background:#dc2626; }
</style>
@endsection

@section('content')
@php $isDefault = $scope === 'default'; @endphp
<div class="card mb-3 rm">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">Rate Manager</h5>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
                <a href="?seller={{ $scope }}&refresh=1" class="btn btn-outline-secondary"><i class="fa fa-sync me-1"></i> Refresh couriers</a>
                <a href="{{ route('active.slebs.add') }}" class="btn btn-outline-secondary"><i class="fa fa-toggle-on me-1"></i> Courier Status</a>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="rm-top">
            <div class="form-group">
                <label class="form-label" for="rm-seller">Rates for</label>
                <select id="rm-seller" class="form-control">
                    <option value="default" {{ $isDefault ? 'selected' : '' }}>Default rate card (all sellers)</option>
                    @foreach($sellers as $s)
                        <option value="{{ $s->id }}" {{ (string) $scope === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}{{ $s->email ? ' — ' . $s->email : '' }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="rm-hint">
            Seller price = <strong>first 500 g + each additional 500 g</strong>, plus for COD orders the <strong>higher of COD ₹ and COD %</strong>
            of order value, plus <strong>18% GST</strong>. The same amount shows in the seller's rate card, rate calculator and ship screen,
            and is what gets debited from their wallet.
            @if($isDefault)
                Sellers without their own rates use these.
            @else
                Empty zones use the default rate card (shown in grey).
            @endif
        </div>

        @if($importResult)
            <div class="rm-result {{ empty($importResult['skipped']) ? 'ok' : '' }}">
                Uploaded for {{ $importResult['who'] }}: <strong>{{ $importResult['saved'] }}</strong> row(s) saved.
                @if(!empty($importResult['skipped']))
                    {{ count($importResult['skipped']) }} skipped:
                    <ul>
                        @foreach(array_slice($importResult['skipped'], 0, 30) as $msg)<li>{{ $msg }}</li>@endforeach
                    </ul>
                @endif
            </div>
        @endif

        <div class="rm-bulk">
            <div class="rm-box">
                <h6><i class="fa fa-file-excel me-1"></i> Download template</h6>
                <p>Every courier in the panel × every zone, already filled with the current {{ $isDefault ? 'default' : 'seller' }} rates. New couriers are added automatically.</p>
                <a class="btn btn-primary" href="{{ route('rate.manager.template', ['seller' => $scope]) }}"><i class="fa fa-download me-1"></i> Download Excel</a>
            </div>
            <div class="rm-box">
                <h6><i class="fa fa-upload me-1"></i> Upload filled template</h6>
                <p>Only filled rows are saved; empty rows change nothing.</p>
                <form class="rm-upload" method="POST" action="{{ route('rate.manager.upload') }}" enctype="multipart/form-data">
                    @csrf
                    <select name="target" class="form-control" required>
                        <option value="default" {{ $isDefault ? 'selected' : '' }}>Default rate card (all sellers)</option>
                        <option value="every">Every seller's own rates (overwrite)</option>
                        @foreach($sellers as $s)
                            <option value="{{ $s->id }}" {{ (string) $scope === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    <button class="btn btn-success" type="submit"><i class="fa fa-check me-1"></i> Upload</button>
                </form>
            </div>
        </div>

        @foreach($groups as $code => $g)
            <div class="rm-group">
                <h6>{{ $g['title'] }}</h6>
                @forelse($g['couriers'] as $key => $c)
                    @php
                        $mine = $own[$key] ?? [];
                        $dflt = $def[$key] ?? [];
                        $setCount = count($isDefault ? $mine : $mine);
                        $noDefault = empty($dflt);
                    @endphp
                    <details class="rm-c" data-key="{{ $key }}">
                        <summary>
                            <img src="{{ asset($c['logo']) }}" alt="" onerror="this.style.visibility='hidden'">
                            <div>
                                <div class="rm-name">{{ $c['label'] }}</div>
                                <div class="rm-sub">
                                    @if($isDefault)
                                        {{ count($mine) }} of {{ count($zones) }} zones set
                                    @else
                                        {{ count($mine) ? count($mine) . ' zone(s) with own rates' : 'Uses the default rate card' }}
                                    @endif
                                </div>
                            </div>
                            <span class="rm-pill {{ $c['default'] ? 'on' : 'off' }}">{{ !empty($c['new']) ? 'NEW – OFF' : ($c['default'] ? 'LIVE' : 'OFF') }}</span>
                            @if($noDefault)
                                <span class="rm-pill warn" title="No default rate">{{ $code === 'delhivery' ? 'No rate – not offered' : 'No rate – margin pricing' }}</span>
                            @endif
                        </summary>
                        <div class="rm-body">
                            <table class="rm-table">
                                <thead>
                                    <tr><th>Zone</th><th>First 500 g (₹)</th><th>Each extra 500 g (₹)</th><th>COD (₹)</th><th>COD %</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($zones as $z)
                                        @php $m = $mine[$z] ?? null; $d = $dflt[$z] ?? null; @endphp
                                        <tr data-zone="{{ $z }}">
                                            <td class="z">Zone {{ $z }}@if(!$isDefault && !$m && $d)<div class="uses-def">default</div>@endif</td>
                                            @foreach(['first', 'additional', 'cod', 'cod_percent'] as $f)
                                                <td>
                                                    <input type="number" step="0.01" min="0" {{ $f === 'cod_percent' ? 'max=100' : '' }}
                                                           class="form-control form-control-sm" data-f="{{ $f }}"
                                                           value="{{ $m && $m[$f] !== null ? rtrim(rtrim(number_format($m[$f], 2, '.', ''), '0'), '.') : '' }}"
                                                           placeholder="{{ !$isDefault && $d && $d[$f] !== null ? rtrim(rtrim(number_format($d[$f], 2, '.', ''), '0'), '.') : ($f === 'additional' ? 'same as first' : '') }}">
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="rm-actions">
                                <button type="button" class="btn btn-primary btn-sm rm-save"><i class="fa fa-save me-1"></i> Save {{ $c['label'] }}</button>
                            </div>
                        </div>
                    </details>
                @empty
                    <div class="rm-sub">No couriers loaded right now — try “Refresh couriers”.</div>
                @endforelse
            </div>
        @endforeach
    </div>
</div>
<div class="rm-toast" id="rmToast"></div>

<script>
(function () {
    const sel = document.getElementById('rm-seller');
    sel.addEventListener('change', () => { window.location.href = '?seller=' + encodeURIComponent(sel.value); });
    const toastEl = document.getElementById('rmToast');
    let tt;
    function toast(m, err) { toastEl.textContent = m; toastEl.className = 'rm-toast' + (err ? ' err' : ''); toastEl.style.display = 'block'; clearTimeout(tt); tt = setTimeout(() => toastEl.style.display = 'none', 3000); }

    document.querySelectorAll('.rm-save').forEach(btn => btn.addEventListener('click', function () {
        const card = this.closest('.rm-c');
        const rows = {};
        card.querySelectorAll('tr[data-zone]').forEach(tr => {
            const r = {};
            tr.querySelectorAll('input[data-f]').forEach(i => r[i.dataset.f] = i.value);
            rows[tr.dataset.zone] = r;
        });
        btn.disabled = true;
        fetch(@json(route('rate.manager.save')), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
            body: JSON.stringify({ seller: sel.value, key: card.dataset.key, rows })
        })
        .then(r => r.json().then(d => { if (!r.ok || d.success === false) throw new Error(d.message || ('HTTP ' + r.status)); return d; }))
        .then(d => { toast(d.message); setTimeout(() => location.reload(), 900); })
        .catch(e => toast('Not saved: ' + e.message, true))
        .finally(() => btn.disabled = false);
    }));
})();
</script>
@endsection
