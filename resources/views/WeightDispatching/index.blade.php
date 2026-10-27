@extends('layouts.app')

@section('css')
<style>
    .wd { --line: var(--sx-border, #2a3757); --card: var(--sx-surface-2, #18223a); --muted: var(--sx-muted, #8592ad); --pri: var(--sx-primary, #4f8cff); }
    .wd .muted { color: var(--muted); font-size: 12px; }
    .wd-steps { display:grid; grid-template-columns: 1fr 1.4fr; gap:12px; margin-bottom:16px; }
    @media (max-width: 900px) { .wd-steps { grid-template-columns: 1fr; } }
    .wd-step { border:1px solid var(--line); background:var(--card); border-radius:12px; padding:14px 16px; }
    .wd-step h6 { margin:0 0 4px; font-weight:700; }
    .wd-step .n { display:inline-flex; width:22px; height:22px; border-radius:50%; background:var(--pri); color:#fff; font-size:12px; align-items:center; justify-content:center; margin-right:6px; }
    .wd-upload { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
    .wd-upload input[type=file] { flex:1; min-width:220px; }
    .wd-stats { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
    .wd-stat { border:1px solid var(--line); background:var(--card); border-radius:10px; padding:9px 14px; min-width:112px; text-decoration:none; color:inherit; }
    .wd-stat.active { border-color: var(--pri); box-shadow: 0 0 0 1px var(--pri) inset; }
    .wd-stat b { display:block; font-size:19px; line-height:1.2; }
    .wd-stat span { font-size:12px; color:var(--muted); }
    .wd-stat.hot b { color:#f5a524; }
    .wd-money { margin-left:auto; display:flex; gap:16px; align-items:center; font-size:12px; color:var(--muted); }
    .wd-money b { color:inherit; font-size:15px; display:block; }
    .wd-filter { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
    .wd-filter input[type=text] { max-width:300px; }
    .wd-filter input[type=date] { max-width:160px; }
    .wd-table { width:100%; border-collapse:separate; border-spacing:0; font-size:13px; }
    .wd-table th { font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:600; padding:9px 10px; border-bottom:1px solid var(--line); white-space:nowrap; }
    .wd-table td { padding:10px; border-bottom:1px solid var(--line); vertical-align:middle; }
    .wd-table tr.need td { background: rgba(245,165,36,.05); }
    .wd-awb { font-weight:700; font-family: ui-monospace, Menlo, monospace; }
    .wd-up { color:#f04f5f; font-weight:600; }
    .wd-badge { font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; white-space:nowrap; display:inline-block; }
    .st-charged { background: rgba(79,140,255,.15); color:#4f8cff; }
    .st-disputed { background: rgba(245,165,36,.16); color:#f5a524; }
    .st-accepted { background: rgba(34,197,94,.15); color:#22c55e; }
    .st-rejected { background: rgba(240,79,95,.15); color:#f04f5f; }
    .st-closed { background: rgba(133,146,173,.16); color:var(--muted); }
    .wd-empty { border:1px dashed var(--line); border-radius:10px; padding:28px; text-align:center; color:var(--muted); }
    .wd-result { border:1px solid var(--line); border-radius:10px; margin-bottom:16px; overflow:hidden; }
    .wd-result summary { padding:10px 14px; cursor:pointer; background:var(--card); font-weight:600; }
    .wd-result .scroll { max-height:280px; overflow:auto; }
    .r-charged { color:#22c55e; } .r-skipped { color:var(--muted); } .r-error { color:#f04f5f; }
    /* modal */
    .wd-modal { position:fixed; inset:0; background:rgba(5,10,22,.68); display:none; align-items:flex-start; justify-content:center; z-index:1080; padding:40px 12px; overflow:auto; }
    .wd-modal.open { display:flex; }
    .wd-box { color: var(--sx-text, #e6ebf5); background: var(--sx-surface, #111a2e); border:1px solid var(--line); border-radius:14px; width:100%; max-width:760px; }
    .wd-box header { display:flex; justify-content:space-between; align-items:center; padding:14px 18px; border-bottom:1px solid var(--line); }
    .wd-box header h5 { margin:0; }
    .wd-x { background:none; border:0; color:inherit; font-size:22px; line-height:1; cursor:pointer; }
    .wd-body { padding:16px 18px; }
    .wd-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:10px; margin-bottom:14px; }
    .wd-kv { border:1px solid var(--line); border-radius:10px; padding:8px 10px; }
    .wd-kv > span { display:block; font-size:11px; color:var(--muted); }
    .wd-kv b { font-size:14px; }
    .wd-sec { font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin:14px 0 6px; font-weight:700; }
    .wd-remark { border-left:3px solid #f5a524; padding:8px 12px; background:rgba(245,165,36,.06); white-space:pre-wrap; border-radius:0 8px 8px 0; font-size:13px; }
    .wd-proofs { display:grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap:10px; }
    .wd-proofs a, .wd-proofs video { display:block; border:1px solid var(--line); border-radius:10px; overflow:hidden; background:#000; }
    .wd-proofs img, .wd-proofs video { width:100%; height:130px; object-fit:cover; display:block; }
    .wd-tl { list-style:none; padding:0; margin:0; }
    .wd-tl li { position:relative; padding:0 0 10px 18px; font-size:13px; }
    .wd-tl li:before { content:''; position:absolute; left:4px; top:6px; width:7px; height:7px; border-radius:50%; background:var(--pri); }
    .wd-tl small { color:var(--muted); display:block; }
    .wd-actions { display:flex; gap:8px; margin-top:10px; flex-wrap:wrap; }
</style>
@endsection

@section('content')
<div class="card mb-3 wd">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-0">Weight Disputes</h5>
            <div class="muted">Upload courier-charged weights → excess debited from seller wallet → seller can dispute within {{ $windowHours }} hours → accept (auto refund) or reject.</div>
        </div>
        <a href="{{ route('weight.dispatching.download', request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="fa fa-download me-1"></i> Export list</a>
    </div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

        <div class="wd-steps">
            <div class="wd-step">
                <h6><span class="n">1</span>Download template</h6>
                <div class="muted">Columns: AWB, charged weight (kg), Dead / Volumetric, optional courier L×B×H, optional manual ₹, note.</div>
                <a href="{{ route('weight.dispute.template') }}" class="btn btn-outline-primary btn-sm mt-2"><i class="fa fa-file-excel me-1"></i> Download template</a>
            </div>
            <div class="wd-step">
                <h6><span class="n">2</span>Upload filled file — charges are debited immediately</h6>
                <div class="muted">Excess = seller's own rate card for the extra 500 g slabs + 18% GST. Same AWB is never charged twice.</div>
                <form class="wd-upload" method="POST" action="{{ route('import.weight.dispute') }}" enctype="multipart/form-data" onsubmit="return wdConfirmUpload(this)">
                    @csrf
                    <input class="form-control form-control-sm" type="file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                    <button class="btn btn-primary btn-sm" type="submit"><i class="fa fa-upload me-1"></i> Upload &amp; charge</button>
                </form>
            </div>
        </div>

        @if($res = session('wd_result'))
            <details class="wd-result" open>
                <summary>Upload result — {{ $res['charged'] }} charged (₹{{ number_format($res['amount'], 2) }}), {{ count($res['rows']) - $res['charged'] }} not charged · batch {{ $res['batch'] }}</summary>
                <div class="scroll">
                    <table class="wd-table">
                        <thead><tr><th>Row</th><th>AWB</th><th>Result</th><th>Details</th></tr></thead>
                        <tbody>
                        @foreach($res['rows'] as [$line, $awb, $result, $message])
                            <tr><td>{{ $line }}</td><td class="wd-awb">{{ $awb }}</td><td class="r-{{ $result }}">{{ ucfirst($result) }}</td><td>{{ $message }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif

        <div class="wd-stats">
            @foreach($tabs as $k => $label)
                <a href="{{ route('weight.dispatching.index', array_merge(request()->except('page', 'tab'), ['tab' => $k])) }}"
                   class="wd-stat {{ $tab === $k ? 'active' : '' }} {{ $k === 'disputed' && $counts[$k] ? 'hot' : '' }}">
                    <b>{{ $counts[$k] }}</b><span>{{ $label }}</span>
                </a>
            @endforeach
            <div class="wd-money">
                <div><b>₹{{ number_format($totals['charged'], 2) }}</b>Total charged</div>
                <div><b>₹{{ number_format($totals['refunded'], 2) }}</b>Refunded</div>
            </div>
        </div>

        <form class="wd-filter" method="GET" action="{{ route('weight.dispatching.index') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="AWB, order no., seller name or email">
            <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" title="From">
            <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm" title="To">
            <button class="btn btn-primary btn-sm">Search</button>
            @if(request('q') || request('from') || request('to'))<a href="{{ route('weight.dispatching.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary btn-sm">Clear</a>@endif
        </form>

        @if(count($rows))
            <div class="table-responsive">
                <table class="wd-table">
                    <thead>
                        <tr>
                            <th>Charged on</th><th>Seller</th><th>AWB / Order</th><th>Courier</th>
                            <th>Billed</th><th>Courier charged</th><th>Excess ₹</th><th>Status</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $r)
                        <tr class="{{ $r['status'] === 'disputed' ? 'need' : '' }}">
                            <td>{{ $r['deducted_at'] ?? '—' }}</td>
                            <td>{{ $r['seller'] }}</td>
                            <td><div class="wd-awb">{{ $r['awb'] }}</div><div class="muted">{{ $r['order_number'] }}</div></td>
                            <td>{{ $r['courier'] }}</td>
                            <td>{{ $r['applied_kg'] !== null ? $r['applied_kg'] . ' kg' : '—' }}</td>
                            <td><span class="wd-up">{{ $r['charged_kg'] !== null ? $r['charged_kg'] . ' kg' : '—' }}</span> <span class="muted">{{ $r['weight_type'] ? ucfirst($r['weight_type']) : '' }}</span></td>
                            <td><b>₹{{ number_format($r['excess_charge'], 2) }}</b></td>
                            <td>
                                <span class="wd-badge st-{{ $r['status'] }}">{{ $r['status_label'] }}</span>
                                @if($r['status'] === 'charged')<div class="muted">dispute until {{ $r['deadline'] }}</div>@endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm {{ $r['status'] === 'disputed' ? 'btn-warning' : 'btn-outline-secondary' }}" onclick="wdOpen({{ $r['id'] }})">
                                    {{ $r['status'] === 'disputed' ? 'Review' : 'View' }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">{{ $disputes->links('pagination::bootstrap-4') }}</div>
        @else
            <div class="wd-empty">No weight disputes here yet.</div>
        @endif
    </div>
</div>

<div class="wd-modal" id="wdModal" onclick="if(event.target===this)wdClose()">
    <div class="wd-box wd">
        <header><h5 id="wdTitle">Weight dispute</h5><button class="wd-x" type="button" onclick="wdClose()">&times;</button></header>
        <div class="wd-body" id="wdBody"></div>
    </div>
</div>
@endsection

@section('js')
<script>
(function () {
    const ROWS = @json($rows->keyBy('id'));
    const REVIEW_URL = @json(route('weight.dispute.review', ['id' => '__ID__']));
    const CSRF = @json(csrf_token());
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const kv = (k, v) => `<div class="wd-kv"><span>${esc(k)}</span><b>${v}</b></div>`;
    const money = n => '₹' + Number(n || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    window.wdConfirmUpload = function (f) {
        return confirm('Charges for every AWB in this file will be debited from seller wallets right now. Continue?');
    };

    window.wdOpen = function (id) {
        const r = ROWS[id]; if (!r) return;
        document.getElementById('wdTitle').textContent = 'AWB ' + r.awb;
        let h = `<div class="wd-grid">
            ${kv('Seller', esc(r.seller))}
            ${kv('Order', esc(r.order_number || '—'))}
            ${kv('Courier', esc(r.courier))}
            ${kv('Status', `<span class="wd-badge st-${esc(r.status)}">${esc(r.status_label)}</span>`)}
        </div>
        <div class="wd-sec">Weight</div>
        <div class="wd-grid">
            ${kv('Seller dead weight', r.applied_dead_kg != null ? esc(r.applied_dead_kg) + ' kg' : '—')}
            ${kv('Seller volumetric', r.applied_vol_kg != null ? esc(r.applied_vol_kg) + ' kg' : '—')}
            ${kv('Billed on', esc(r.applied_kg) + ' kg')}
            ${kv('Courier charged', '<span class="wd-up">' + esc(r.charged_kg) + ' kg</span> · ' + esc((r.weight_type || '').replace(/^./, c => c.toUpperCase())))}
            ${r.courier_dims ? kv('Courier box size', esc(r.courier_dims)) : ''}
            ${kv('Extra slabs (500 g)', esc(r.extra_slabs ?? '—'))}
            ${kv('Excess charged', '<b>' + money(r.excess_charge) + '</b>')}
            ${kv('Calculated by', esc({rate_card: 'Seller rate card', proportional: 'Proportional to paid', manual: 'Manual amount'}[r.calc_method] || '—'))}
        </div>`;
        if (r.admin_note) h += `<div class="wd-sec">Note to seller</div><div class="muted">${esc(r.admin_note)}</div>`;
        if (r.seller_remark) h += `<div class="wd-sec">Seller's dispute</div><div class="wd-remark">${esc(r.seller_remark)}</div>`;
        if (r.proofs && r.proofs.length) {
            h += `<div class="wd-sec">Proof (${r.proofs.length})</div><div class="wd-proofs">` + r.proofs.map(p => p.type === 'video'
                ? `<video src="${esc(p.url)}" controls preload="metadata"></video>`
                : `<a href="${esc(p.url)}" target="_blank" rel="noopener"><img src="${esc(p.url)}" alt="${esc(p.name)}" loading="lazy"></a>`).join('') + '</div>';
        }
        if (r.admin_remark) h += `<div class="wd-sec">Admin decision note</div><div class="muted">${esc(r.admin_remark)}</div>`;
        h += `<div class="wd-sec">Timeline</div><ul class="wd-tl">` + (r.timeline || []).map(t => `<li>${esc(t.text)}<small>${esc(t.at)}</small></li>`).join('') + '</ul>';

        if (r.status === 'disputed') {
            h += `<div class="wd-sec">Decision</div>
            <form method="POST" action="${esc(REVIEW_URL.replace('__ID__', r.id))}" id="wdReview">
                <input type="hidden" name="_token" value="${esc(CSRF)}">
                <input type="hidden" name="decision" id="wdDecision">
                <textarea class="form-control" name="admin_remark" id="wdRemark" rows="2" maxlength="1000" placeholder="Note for the seller (required when rejecting)"></textarea>
                <div class="wd-actions">
                    <button type="button" class="btn btn-success" onclick="wdDecide('accept')"><i class="fa fa-check me-1"></i> Accept &amp; refund ${money(r.excess_charge)}</button>
                    <button type="button" class="btn btn-danger" onclick="wdDecide('reject')"><i class="fa fa-times me-1"></i> Reject &amp; close</button>
                </div>
            </form>`;
        }
        document.getElementById('wdBody').innerHTML = h;
        document.getElementById('wdModal').classList.add('open');
    };

    window.wdDecide = function (d) {
        const remark = document.getElementById('wdRemark');
        if (d === 'reject' && !remark.value.trim()) { remark.focus(); remark.placeholder = 'Write the reason for rejecting — the seller will see it'; return; }
        if (!confirm(d === 'accept' ? 'Accept this dispute and refund the amount to the seller wallet?' : 'Reject this dispute? It will be closed and the charge stays.')) return;
        document.getElementById('wdDecision').value = d;
        document.getElementById('wdReview').submit();
    };

    window.wdClose = function () {
        document.getElementById('wdModal').classList.remove('open');
        document.querySelectorAll('#wdBody video').forEach(v => v.pause());
    };
    document.addEventListener('keydown', e => { if (e.key === 'Escape') wdClose(); });
})();
</script>
@endsection
