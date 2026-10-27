@extends('layouts.app')

@section('css')
<style>
    .cs-wrap { --line: var(--sx-border, #2a3757); --card: var(--sx-surface-2, #18223a); }
    .cs-mode { display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; }
    .cs-mode .form-group { min-width: 280px; flex: 1 1 280px; max-width: 420px; }
    .cs-hint { border:1px solid var(--line); border-radius:10px; padding:10px 14px; margin:14px 0 6px; font-size:13px; color: var(--sx-text-2, #b4bfd6); background: var(--sx-primary-soft, rgba(79,140,255,.12)); }
    .cs-group { margin-top: 22px; }
    .cs-group-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px; flex-wrap:wrap; }
    .cs-group-head h6 { margin:0; font-size:15px; }
    .cs-group-head .cs-count { font-size:12px; color: var(--sx-muted, #8592ad); }
    .cs-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:10px; }
    .cs-item { display:flex; align-items:center; justify-content:space-between; gap:12px; border:1px solid var(--line); background: var(--card); border-radius:10px; padding:12px 14px; transition: opacity .15s; }
    .cs-item.is-off { opacity:.6; }
    .cs-item img { width:40px; height:40px; object-fit:contain; border-radius:8px; background:#fff; padding:3px; flex:0 0 40px; }
    .cs-name { font-weight:600; font-size:14px; line-height:1.2; }
    .cs-meta { font-size:12px; color: var(--sx-muted, #8592ad); margin-top:3px; }
    .cs-meta a { cursor:pointer; }
    .cs-right { display:flex; align-items:center; gap:8px; }
    .cs-right .form-check-input { width:2.6em; height:1.35em; cursor:pointer; margin:0; }
    .cs-badge { font-size:11px; font-weight:700; letter-spacing:.03em; padding:3px 8px; border-radius:20px; min-width:44px; text-align:center; }
    .cs-badge.on { background: var(--sx-success-soft, rgba(34,197,94,.15)); color: var(--sx-success, #22c55e); }
    .cs-badge.off { background: var(--sx-danger-soft, rgba(240,79,95,.15)); color: var(--sx-danger, #f04f5f); }
    .cs-tag { font-size:11px; padding:1px 7px; border-radius:20px; border:1px solid var(--sx-warning, #f5a524); color: var(--sx-warning, #f5a524); margin-left:6px; }
    .cs-empty { border:1px dashed var(--line); border-radius:10px; padding:14px; font-size:13px; color: var(--sx-muted, #8592ad); }
    .cs-remove { font-size:12px; color: var(--sx-danger, #f04f5f) !important; cursor:pointer; margin-left:8px; }
    .cs-removed { margin-top:26px; border-top:1px solid var(--line); padding-top:14px; font-size:13px; color: var(--sx-muted, #8592ad); }
    .cs-removed span { display:inline-block; border:1px solid var(--line); border-radius:20px; padding:3px 10px; margin:4px 6px 0 0; }
    .cs-removed a { cursor:pointer; margin-left:6px; }
    .cs-toast { position:fixed; right:18px; bottom:18px; z-index:9999; padding:10px 16px; border-radius:10px; font-size:13px; color:#fff; background:#16a34a; box-shadow:0 8px 24px rgba(0,0,0,.35); display:none; }
    .cs-toast.err { background:#dc2626; }
</style>
@endsection

@section('content')
@php $isDefault = $sellerParam === 'all'; @endphp
<div class="card mb-3 cs-wrap">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">Price Setting :: Courier Status</h5>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
                <a href="?seller_id={{ $sellerParam }}&refresh=1" class="btn btn-outline-secondary" title="Re-fetch the courier list from Shipclues and Fship">
                    <i class="fa fa-sync me-1"></i> Refresh couriers
                </a>
                <a href="{{ route('pricesetting.add') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Go Back
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="cs-mode">
            <div class="form-group">
                <label class="form-label" for="seller_id">Apply to</label>
                <select class="form-control" id="seller_id">
                    <option value="all" {{ $isDefault ? 'selected' : '' }}>All Sellers (default)</option>
                    @foreach($SellerList as $seller)
                        <option value="{{ $seller->id }}" {{ $selectedSeller && $selectedSeller->id == $seller->id ? 'selected' : '' }}>
                            {{ $seller->name }}{{ $seller->email ? ' — ' . $seller->email : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="cs-hint">
            @if($isDefault)
                <strong>Default for every seller.</strong> Couriers switched ON here are live for all sellers, including new ones.
                Changing a switch here also resets any seller's own switch for that courier.
            @else
                <strong>Only for {{ $selectedSeller->name }}.</strong> A switch you change here becomes this seller's own setting
                (marked <span class="cs-tag" style="margin:0">Custom</span>). Use “Use default” to undo it.
            @endif
        </div>

        @forelse($groups as $code => $group)
            @php
                $total = count($group['couriers']);
                $liveCount = 0;
                foreach ($group['couriers'] as $k => $c) {
                    $st = (!$isDefault && array_key_exists($k, $overrides)) ? $overrides[$k] : $c['default'];
                    $liveCount += $st ? 1 : 0;
                }
            @endphp
            <div class="cs-group">
                <div class="cs-group-head">
                    <h6>{{ $group['title'] }}</h6>
                    <span class="cs-count">{{ $liveCount }} of {{ $total }} live</span>
                </div>

                @if(!$total)
                    <div class="cs-empty">
                        Couldn't load couriers from {{ ucfirst($code) }} right now. Click “Refresh couriers” in a minute.
                    </div>
                @else
                <div class="cs-grid">
                    @foreach($group['couriers'] as $key => $c)
                        @php
                            $custom = !$isDefault && array_key_exists($key, $overrides);
                            $on = $custom ? $overrides[$key] : $c['default'];
                            $nCustom = $customCounts[$key] ?? 0;
                        @endphp
                        <div class="cs-item {{ $on ? '' : 'is-off' }}" data-key="{{ $key }}">
                            <div class="d-flex align-items-center gap-3" style="min-width:0">
                                <img src="{{ asset($c['logo']) }}" alt="" onerror="this.style.visibility='hidden'">
                                <div style="min-width:0">
                                    <div class="cs-name">{{ $c['label'] }}@if($custom)<span class="cs-tag">Custom</span>@endif</div>
                                    <div class="cs-meta">
                                        @if($isDefault)
                                            {{ $nCustom ? $nCustom . ' seller(s) have their own switch' : 'Default for all sellers' }}
                                            · <a class="cs-remove">Remove permanently</a>
                                        @elseif($custom)
                                            Default is {{ $c['default'] ? 'ON' : 'OFF' }} · <a class="cs-reset">Use default</a>
                                        @else
                                            Following default
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="cs-right">
                                <span class="cs-badge {{ $on ? 'on' : 'off' }}">{{ $on ? 'LIVE' : 'OFF' }}</span>
                                <div class="form-check form-switch m-0">
                                    <input type="checkbox" class="form-check-input cs-toggle" {{ $on ? 'checked' : '' }}
                                           aria-label="{{ $c['label'] }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif
            </div>
        @empty
            <div class="cs-empty">No courier integration is configured yet.</div>
        @endforelse

        @if($isDefault && count($removed))
            <div class="cs-removed">
                Removed from the panel ({{ count($removed) }}) — never offered to any seller:
                <div>
                    @foreach($removed as $rk)
                        <span data-key="{{ $rk }}">{{ trim(str_replace('_', ' ', preg_replace('/^(Shipclues|Fship)_/', '', $rk))) }}<a class="cs-restore">Restore</a></span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
<div class="cs-toast" id="csToast"></div>

<script>
(function () {
    const sellerSel = document.getElementById('seller_id');
    sellerSel.addEventListener('change', () => { window.location.href = '?seller_id=' + encodeURIComponent(sellerSel.value); });

    const isDefault = @json($isDefault);
    const csrf = @json(csrf_token());
    const toastEl = document.getElementById('csToast');
    let toastTimer;
    function toast(msg, err) {
        toastEl.textContent = msg; toastEl.className = 'cs-toast' + (err ? ' err' : ''); toastEl.style.display = 'block';
        clearTimeout(toastTimer); toastTimer = setTimeout(() => toastEl.style.display = 'none', 2600);
    }
    function paint(item, on) {
        item.classList.toggle('is-off', !on);
        const b = item.querySelector('.cs-badge');
        b.className = 'cs-badge ' + (on ? 'on' : 'off'); b.textContent = on ? 'LIVE' : 'OFF';
        item.querySelector('.cs-toggle').checked = on;
    }
    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(body)
        }).then(r => r.json().then(d => { if (!r.ok || d.success === false) throw new Error(d.message || ('HTTP ' + r.status)); return d; }));
    }

    document.querySelectorAll('.cs-toggle').forEach(t => t.addEventListener('change', function () {
        const item = this.closest('.cs-item');
        const on = this.checked;
        const name = item.querySelector('.cs-name').childNodes[0].textContent.trim();
        if (isDefault && !confirm((on ? 'Make ' : 'Turn OFF ') + name + (on ? ' LIVE' : '') + ' for ALL sellers?')) {
            this.checked = !on; return;
        }
        paint(item, on);
        post(@json(route('pricesetting.courier.update')), { seller_id: sellerSel.value, courier: item.dataset.key, status: on ? 1 : 0 })
            .then(d => {
                toast(d.message || 'Saved');
                if (!isDefault) { setTimeout(() => window.location.reload(), 600); }
            })
            .catch(e => { paint(item, !on); toast('Not saved: ' + e.message, true); });
    }));

    document.querySelectorAll('.cs-remove').forEach(a => a.addEventListener('click', function () {
        const item = this.closest('.cs-item');
        const name = item.querySelector('.cs-name').childNodes[0].textContent.trim();
        if (!confirm('Remove ' + name + ' from the panel permanently? It will be hidden here and never offered to any seller.')) return;
        post(@json(route('pricesetting.courier.remove')), { courier: item.dataset.key, remove: 1 })
            .then(d => { toast(d.message); item.remove(); setTimeout(() => window.location.reload(), 700); })
            .catch(e => toast('Not saved: ' + e.message, true));
    }));
    document.querySelectorAll('.cs-restore').forEach(a => a.addEventListener('click', function () {
        const key = this.parentNode.dataset.key;
        post(@json(route('pricesetting.courier.remove')), { courier: key, remove: 0 })
            .then(d => { toast(d.message); setTimeout(() => window.location.reload(), 500); })
            .catch(e => toast('Not saved: ' + e.message, true));
    }));

    document.querySelectorAll('.cs-reset').forEach(a => a.addEventListener('click', function () {
        const item = this.closest('.cs-item');
        post(@json(route('pricesetting.courier.reset')), { seller_id: sellerSel.value, courier: item.dataset.key })
            .then(d => { toast(d.message); setTimeout(() => window.location.reload(), 500); })
            .catch(e => toast('Not saved: ' + e.message, true));
    }));
})();
</script>
@endsection
