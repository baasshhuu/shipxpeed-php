@extends('layouts.sellerdash')

@section('content')
<style>
    .wds { --ink:#1e2a3b; --muted:#6b7a90; --line:#e4e8f0; --pri:#3d6ef5; --soft:#f5f7fc; }
    .wds { color:var(--ink); }
    .wds, .wds * { box-sizing:border-box; }
    .wds .pc-content { max-width:100%; }
    .wds .muted { color:var(--muted); font-size:12px; }
    .wds-head { display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
    .wds-head h1 { font-size:1.3rem; font-weight:700; margin:0; }
    .wds-wallet { background:#fff; border:1px solid var(--line); border-radius:10px; padding:8px 14px; font-size:12px; color:var(--muted); }
    .wds-wallet b { display:block; font-size:16px; color:var(--ink); }
    .wds-wallet b.neg { color:#d93a4a; }
    .wds-info { background:#eef3ff; border:1px solid #d6e1ff; color:#2b4a9b; border-radius:10px; padding:10px 14px; font-size:13px; margin-bottom:14px; }
    .wds-stats { display:grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap:10px; margin-bottom:14px; }
    .wds-stat { background:#fff; border:1px solid var(--line); border-radius:12px; padding:10px 14px; text-decoration:none; color:var(--ink); transition:border-color .15s; }
    .wds-stat:hover { border-color:#b9c8f2; color:var(--ink); }
    .wds-stat.active { border-color:var(--pri); box-shadow:0 0 0 1px var(--pri) inset; }
    .wds-stat b { display:block; font-size:20px; line-height:1.25; }
    .wds-stat span { font-size:12px; color:var(--muted); }
    .wds-stat.warn b { color:#d97706; }
    .wds-filter { display:flex; gap:8px; flex-wrap:wrap; background:#fff; border:1px solid var(--line); border-radius:12px; padding:10px; margin-bottom:14px; align-items:center; }
    .wds-filter input { border:1px solid var(--line); background:var(--soft); border-radius:8px; padding:8px 10px; font-size:13px; }
    .wds-filter input[type=text] { flex:1; min-width:180px; }
    .wds-btn { border:0; border-radius:8px; padding:8px 14px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
    .wds-btn.pri { background:var(--pri); color:#fff; }
    .wds-btn.ghost { background:#fff; color:var(--ink); border:1px solid var(--line); }
    .wds-btn.warn { background:#f59e0b; color:#fff; }
    .wds-btn:disabled { opacity:.6; cursor:wait; }
    .wds-card { background:#fff; border:1px solid var(--line); border-radius:12px; overflow:hidden; }
    .wds-table { width:100%; border-collapse:collapse; font-size:13px; }
    .wds-table th { background:var(--soft); color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:.04em; font-weight:700; padding:10px 12px; text-align:left; white-space:nowrap; }
    .wds-table td { padding:12px; border-top:1px solid var(--line); vertical-align:middle; }
    .wds-awb { font-weight:700; font-family: ui-monospace, Menlo, monospace; }
    .wds-up { color:#d93a4a; font-weight:700; }
    .wds-badge { font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; white-space:nowrap; display:inline-block; }
    .st-charged { background:#e8efff; color:#2f5bd8; }
    .st-disputed { background:#fff4e0; color:#b86e00; }
    .st-accepted { background:#e5f7ec; color:#178a46; }
    .st-rejected { background:#fde8ea; color:#c42b3b; }
    .st-closed { background:#eef0f4; color:#6b7a90; }
    .wds-left { font-size:11px; color:#b86e00; font-weight:600; display:block; margin-top:3px; }
    .wds-empty { padding:36px; text-align:center; color:var(--muted); }
    .wds-pag { display:flex; justify-content:center; padding:12px; }
    /* modal */
    .wds-modal { position:fixed; inset:0; background:rgba(15,23,42,.55); display:none; align-items:flex-start; justify-content:center; z-index:2000; padding:30px 10px; overflow:auto; }
    .wds-modal.open { display:flex; }
    .wds-box { background:#fff; border-radius:14px; width:100%; max-width:720px; box-shadow:0 20px 60px rgba(0,0,0,.25); }
    .wds-box header { display:flex; justify-content:space-between; align-items:center; padding:14px 18px; border-bottom:1px solid var(--line); }
    .wds-box header h5 { margin:0; font-weight:700; font-size:1.05rem; }
    .wds-x { background:none; border:0; font-size:24px; line-height:1; cursor:pointer; color:var(--muted); }
    .wds-body { padding:16px 18px; }
    .wds-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:8px; }
    .wds-kv { background:var(--soft); border-radius:10px; padding:8px 10px; }
    .wds-kv > span { display:block; font-size:11px; color:var(--muted); }
    .wds-kv b { font-size:14px; }
    .wds-sec { font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin:16px 0 6px; font-weight:700; }
    .wds-note { background:var(--soft); border-radius:8px; padding:8px 12px; font-size:13px; white-space:pre-wrap; }
    .wds-note.admin { border-left:3px solid var(--pri); }
    .wds-proofs { display:grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap:8px; }
    .wds-proofs a, .wds-proofs video { display:block; border-radius:10px; overflow:hidden; background:#000; border:1px solid var(--line); }
    .wds-proofs img, .wds-proofs video { width:100%; height:120px; object-fit:cover; display:block; }
    .wds-tl { list-style:none; padding:0; margin:0; }
    .wds-tl li { position:relative; padding:0 0 10px 18px; font-size:13px; }
    .wds-tl li:before { content:''; position:absolute; left:3px; top:6px; width:8px; height:8px; border-radius:50%; background:var(--pri); }
    .wds-tl small { color:var(--muted); display:block; }
    .wds-form textarea { width:100%; border:1px solid var(--line); border-radius:10px; padding:10px; font-size:13px; min-height:84px; }
    .wds-drop { display:block; border:2px dashed #c9d5f2; border-radius:12px; padding:16px; text-align:center; background:#f8faff; cursor:pointer; margin-top:10px; }
    .wds-drop:hover { border-color:var(--pri); }
    .wds-files { list-style:none; padding:0; margin:8px 0 0; font-size:12px; }
    .wds-files li { display:flex; justify-content:space-between; gap:8px; padding:6px 10px; background:var(--soft); border-radius:8px; margin-top:4px; }
    .wds-files li.bad { background:#fde8ea; color:#c42b3b; }
    .wds-err { color:#c42b3b; font-size:12px; margin-top:6px; min-height:1em; }
    @media (max-width: 640px) {
        .wds-table thead { display:none; }
        .wds-table, .wds-table tbody, .wds-table tr, .wds-table td { display:block; width:100%; }
        .wds-table tr { border-top:1px solid var(--line); padding:10px 12px; }
        .wds-table td { border:0; padding:3px 0; display:flex; justify-content:space-between; gap:10px; }
        .wds-table td:before { content: attr(data-l); color:var(--muted); font-size:12px; flex-shrink:0; }
        .wds-table td > * { text-align:right; min-width:0; word-break:break-word; }
        .wds .pc-content { margin-left:0 !important; padding:0 10px; }
    }
</style>

<div class="pc-container wds">
    <div class="pc-content" style="margin-left:12px;">
        <div class="wds-head">
            <div>
                <h1>Weight Discrepancy</h1>
                <div class="muted">Courier re-weighed charges on your shipments, with proof upload and dispute status.</div>
            </div>
            <div class="wds-wallet">Wallet balance<b class="{{ $totalAmount < 0 ? 'neg' : '' }}">₹{{ number_format($totalAmount, 2) }}</b></div>
        </div>

        @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

        <div class="wds-info">
            <i class="fa-solid fa-circle-info me-1"></i>
            When the courier charges more weight than you declared, the extra amount is debited from your wallet. If it's wrong, click <b>Raise dispute</b> within <b>{{ $windowHours }} hours</b> and upload photos / videos of the packed parcel on a weighing scale and with a measuring tape (up to {{ $maxFiles }} files, {{ $maxMb }} MB each). If accepted, the amount is refunded to your wallet automatically.
        </div>

        @php $f = request('status'); @endphp
        <div class="wds-stats">
            <a class="wds-stat {{ !$f ? 'active' : '' }}" href="{{ route('seller.weight.discrepancy', request()->except('status', 'page')) }}"><b>{{ $stats['total'] }}</b><span>All discrepancies</span></a>
            <a class="wds-stat warn {{ $f === 'open' ? 'active' : '' }}" href="{{ route('seller.weight.discrepancy', array_merge(request()->except('page'), ['status' => 'open'])) }}"><b>{{ $stats['open'] }}</b><span>Can dispute now</span></a>
            <a class="wds-stat {{ $f === 'review' ? 'active' : '' }}" href="{{ route('seller.weight.discrepancy', array_merge(request()->except('page'), ['status' => 'review'])) }}"><b>{{ $stats['review'] }}</b><span>Under review</span></a>
            <a class="wds-stat {{ $f === 'accepted' ? 'active' : '' }}" href="{{ route('seller.weight.discrepancy', array_merge(request()->except('page'), ['status' => 'accepted'])) }}"><b>{{ $stats['accepted'] }}</b><span>Accepted · refunded</span></a>
            <a class="wds-stat {{ $f === 'rejected' ? 'active' : '' }}" href="{{ route('seller.weight.discrepancy', array_merge(request()->except('page'), ['status' => 'rejected'])) }}"><b>{{ $stats['rejected'] }}</b><span>Rejected</span></a>
            <div class="wds-stat"><b>₹{{ number_format($stats['charged_amount'] - $stats['refunded_amount'], 2) }}</b><span>Net charged (₹{{ number_format($stats['refunded_amount'], 2) }} refunded)</span></div>
        </div>

        <form class="wds-filter" method="GET" action="{{ route('seller.weight.discrepancy') }}">
            @if($f)<input type="hidden" name="status" value="{{ $f }}">@endif
            <input type="date" name="start_date" value="{{ request('start_date') }}" title="From">
            <input type="date" name="end_date" value="{{ request('end_date') }}" title="To">
            <input type="text" name="awb" value="{{ request('awb') }}" placeholder="AWB or order number">
            <button class="wds-btn pri" type="submit"><i class="fas fa-filter"></i> Filter</button>
            <a class="wds-btn ghost" href="{{ route('seller.weight.discrepancy.excel', request()->all()) }}"><i class="fa-solid fa-download"></i> Excel</a>
        </form>

        <div class="wds-card">
            @if(count($rows))
                <table class="wds-table">
                    <thead>
                        <tr><th>Date</th><th>AWB / Order</th><th>Courier</th><th>Your weight</th><th>Courier charged</th><th>Charged</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $r)
                        <tr>
                            <td data-l="Date">{{ $r['deducted_at'] }}</td>
                            <td data-l="AWB"><div><span class="wds-awb">{{ $r['awb'] }}</span><div class="muted">{{ $r['order_number'] }}</div></div></td>
                            <td data-l="Courier">{{ $r['courier'] }}</td>
                            <td data-l="Your weight">{{ $r['applied_kg'] !== null ? $r['applied_kg'] . ' kg' : '—' }}</td>
                            <td data-l="Courier charged"><span><span class="wds-up">{{ $r['charged_kg'] !== null ? $r['charged_kg'] . ' kg' : '—' }}</span> <span class="muted">{{ ucfirst((string) $r['weight_type']) }}</span></span></td>
                            <td data-l="Charged"><b>₹{{ number_format($r['excess_charge'], 2) }}</b></td>
                            <td data-l="Status">
                                <span>
                                    <span class="wds-badge st-{{ $r['status'] }}">{{ $r['status_label'] }}</span>
                                    @if($r['can_raise'])<span class="wds-left" data-deadline="{{ $r['deadline_iso'] }}"></span>@endif
                                </span>
                            </td>
                            <td data-l="">
                                @if($r['can_raise'])
                                    <button type="button" class="wds-btn warn" onclick="wdsOpen({{ $r['id'] }}, true)"><i class="fa-solid fa-flag"></i> Raise dispute</button>
                                @else
                                    <button type="button" class="wds-btn ghost" onclick="wdsOpen({{ $r['id'] }}, false)">Details</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="wds-pag">{{ $disputes->links('pagination::bootstrap-4') }}</div>
            @else
                <div class="wds-empty"><i class="fa-solid fa-scale-balanced fa-2x mb-2 d-block"></i>No weight discrepancies found.</div>
            @endif
        </div>
    </div>
</div>

<div class="wds-modal" id="wdsModal" onclick="if(event.target===this)wdsClose()">
    <div class="wds-box wds">
        <header><h5 id="wdsTitle">Weight discrepancy</h5><button class="wds-x" type="button" onclick="wdsClose()">&times;</button></header>
        <div class="wds-body" id="wdsBody"></div>
    </div>
</div>

<script>
(function () {
    const ROWS = {}; @json($rows).forEach(r => ROWS[r.id] = r);
    const RAISE_URL = @json(route('seller.weight.dispute.raise', ['id' => '__ID__']));
    const CSRF = @json(csrf_token());
    const MAX_FILES = {{ (int) $maxFiles }}, MAX_BYTES = {{ (int) $maxMb }} * 1024 * 1024;
    const OK_EXT = ['jpg','jpeg','png','webp','heic','mp4','mov','webm','3gp','mkv'];
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const kv = (k, v) => `<div class="wds-kv"><span>${esc(k)}</span><b>${v}</b></div>`;
    const money = n => '₹' + Number(n || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const size = b => b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
    const cap = s => (s || '').replace(/^./, c => c.toUpperCase());

    function left(iso) {
        const ms = new Date(iso) - new Date();
        if (ms <= 0) return 'Window closed';
        const h = Math.floor(ms / 3600000), m = Math.floor((ms % 3600000) / 60000);
        return (h ? h + 'h ' : '') + m + 'm left to dispute';
    }
    function tick() { document.querySelectorAll('[data-deadline]').forEach(el => el.textContent = left(el.dataset.deadline)); }
    tick(); setInterval(tick, 30000);

    window.wdsOpen = function (id, raise) {
        const r = ROWS[id]; if (!r) return;
        document.getElementById('wdsTitle').textContent = (raise ? 'Raise dispute · ' : '') + 'AWB ' + r.awb;
        let h = `<div class="wds-grid">
            ${kv('Order', esc(r.order_number || '—'))}
            ${kv('Courier', esc(r.courier))}
            ${kv('Status', `<span class="wds-badge st-${esc(r.status)}">${esc(r.status_label)}</span>`)}
            ${kv('Charged on', esc(r.deducted_at || '—'))}
        </div>
        <div class="wds-sec">Weight comparison</div>
        <div class="wds-grid">
            ${kv('Your dead weight', r.applied_dead_kg != null ? esc(r.applied_dead_kg) + ' kg' : '—')}
            ${kv('Your volumetric (L×B×H÷5000)', r.applied_vol_kg != null ? esc(r.applied_vol_kg) + ' kg' : '—')}
            ${kv('You were billed on', esc(r.applied_kg) + ' kg')}
            ${kv('Courier charged', '<span class="wds-up">' + esc(r.charged_kg) + ' kg</span> · ' + esc(cap(r.weight_type)))}
            ${r.courier_dims ? kv('Courier box size', esc(r.courier_dims)) : ''}
            ${kv('Difference', r.diff_kg != null ? '+' + esc(r.diff_kg) + ' kg' : '—')}
            ${kv('Extra slabs (500 g)', esc(r.extra_slabs ?? '—'))}
            ${kv('Amount debited', '<b>' + money(r.excess_charge) + '</b>')}
        </div>`;
        if (r.admin_note) h += `<div class="wds-sec">Note from Shipxpeed</div><div class="wds-note admin">${esc(r.admin_note)}</div>`;
        if (r.seller_remark) h += `<div class="wds-sec">Your dispute</div><div class="wds-note">${esc(r.seller_remark)}</div>`;
        if (r.proofs && r.proofs.length) {
            h += `<div class="wds-sec">Your proof</div><div class="wds-proofs">` + r.proofs.map(p => p.type === 'video'
                ? `<video src="${esc(p.url)}" controls preload="metadata"></video>`
                : `<a href="${esc(p.url)}" target="_blank" rel="noopener"><img src="${esc(p.url)}" alt="${esc(p.name)}" loading="lazy"></a>`).join('') + '</div>';
        }
        if (r.admin_remark) h += `<div class="wds-sec">Review note</div><div class="wds-note admin">${esc(r.admin_remark)}</div>`;
        h += `<div class="wds-sec">Timeline</div><ul class="wds-tl">` + (r.timeline || []).map(t => `<li>${esc(t.text)}<small>${esc(t.at)}</small></li>`).join('') + '</ul>';

        if (r.can_raise) {
            h += `<div class="wds-sec">Raise dispute · <span style="text-transform:none;color:#b86e00">${esc(left(r.deadline_iso))} (until ${esc(r.deadline)})</span></div>
            <form class="wds-form" id="wdsForm" method="POST" enctype="multipart/form-data" action="${esc(RAISE_URL.replace('__ID__', r.id))}">
                <input type="hidden" name="_token" value="${esc(CSRF)}">
                <textarea name="remark" id="wdsRemark" maxlength="1000" placeholder="Why is the courier weight wrong? e.g. Packed weight is 480 g, box 20×15×10 cm — see scale photo and video."></textarea>
                <label class="wds-drop" for="wdsFiles">
                    <i class="fa-solid fa-cloud-arrow-up fa-lg"></i><br>
                    <b>Add photos / videos</b><br><span class="muted">Parcel on weighing scale, tape measure on each side · up to ${MAX_FILES} files · max ${MAX_BYTES / 1048576} MB each</span>
                </label>
                <input type="file" id="wdsFiles" name="proofs[]" multiple accept="image/*,video/*" hidden>
                <ul class="wds-files" id="wdsList"></ul>
                <div class="wds-err" id="wdsErr"></div>
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
                    <button type="button" class="wds-btn ghost" onclick="wdsClose()">Cancel</button>
                    <button type="submit" class="wds-btn warn" id="wdsSubmit"><i class="fa-solid fa-paper-plane"></i> Submit dispute</button>
                </div>
            </form>`;
        }
        document.getElementById('wdsBody').innerHTML = h;
        document.getElementById('wdsModal').classList.add('open');

        const form = document.getElementById('wdsForm');
        if (form) {
            const input = document.getElementById('wdsFiles');
            input.addEventListener('change', () => check(true));
            form.addEventListener('submit', e => { if (!check(false)) { e.preventDefault(); return; }
                const b = document.getElementById('wdsSubmit'); b.disabled = true; b.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading, please wait…'; });
            if (raise) setTimeout(() => document.getElementById('wdsRemark').focus(), 50);
        }
    };

    function check(onlyFiles) {
        const files = Array.from(document.getElementById('wdsFiles').files);
        const list = document.getElementById('wdsList'), err = document.getElementById('wdsErr');
        let bad = '';
        list.innerHTML = files.map(f => {
            const ext = (f.name.split('.').pop() || '').toLowerCase();
            const why = f.size > MAX_BYTES ? 'over ' + (MAX_BYTES / 1048576) + ' MB' : (!OK_EXT.includes(ext) ? 'not a photo/video' : '');
            if (why && !bad) bad = f.name + ' is ' + why + '.';
            return `<li class="${why ? 'bad' : ''}"><span>${esc(f.name)}</span><span>${size(f.size)}${why ? ' · ' + why : ''}</span></li>`;
        }).join('');
        if (!bad && files.length > MAX_FILES) bad = 'Choose at most ' + MAX_FILES + ' files.';
        if (!onlyFiles) {
            const remark = document.getElementById('wdsRemark').value.trim();
            if (!bad && remark.length < 10) bad = 'Describe the issue (at least 10 characters).';
            if (!bad && !files.length) bad = 'Add at least one photo or video as proof.';
        }
        err.textContent = bad;
        return !bad;
    }

    window.wdsClose = function () {
        document.getElementById('wdsModal').classList.remove('open');
        document.querySelectorAll('#wdsBody video').forEach(v => v.pause());
    };
    document.addEventListener('keydown', e => { if (e.key === 'Escape') wdsClose(); });
})();
</script>
@endsection
