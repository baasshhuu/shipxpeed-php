<style>
    .xp { --xp-accent:#4f8cff; --xp-line:rgba(120,130,150,.2); --xp-soft:rgba(79,140,255,.08); }
    .xp .xp-head { background:linear-gradient(95deg,#1c2541 0%,#1f3357 55%,#244a7a 120%); color:#fff; border-radius:12px; padding:18px 20px; margin-bottom:14px; display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap; }
    .xp .xp-head h1 { font-size:1.2rem; margin:0; color:#fff; display:flex; align-items:center; gap:10px; }
    .xp .xp-head p { margin:4px 0 0; opacity:.82; font-size:.84rem; max-width:720px; }
    .xp .xp-tabs { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:14px; }
    .xp .xp-tabs a { padding:6px 12px; border-radius:999px; font-size:.8rem; font-weight:600; text-decoration:none; border:1px solid var(--xp-line); color:inherit; opacity:.85; }
    .xp .xp-tabs a.on { background:var(--xp-accent); border-color:var(--xp-accent); color:#fff; opacity:1; }
    .xp .xp-card { background:rgba(127,135,150,.05); border:1px solid var(--xp-line); border-radius:12px; padding:18px; margin-bottom:14px; }
    .xp .xp-card h2 { font-size:1rem; margin:0 0 4px; }
    .xp .xp-sub { color:#7a8496; font-size:.82rem; margin:0 0 12px; }
    .xp .pill { display:inline-block; padding:3px 10px; border-radius:999px; font-size:11.5px; font-weight:600; white-space:nowrap; }
    .xp .ok { background:#e3f7ea; color:#137333; } .xp .bad { background:#fdecea; color:#b3261e; }
    .xp .warn { background:#fff4e5; color:#8a5300; } .xp .muted { background:#eef0f4; color:#5f6878; } .xp .info { background:#e8f0fe; color:#1a56c4; }
    .xp .stat { font-size:1.4rem; font-weight:700; line-height:1.2; } .xp .stat-l { color:#7a8496; font-size:.78rem; }
    .xp table { font-size:13px; } .xp td, .xp th { vertical-align:middle; }
    .xp .mono { font-family:ui-monospace,Menlo,Consolas,monospace; font-size:12px; }
    .xp .xp-empty { text-align:center; padding:34px 10px; color:#7a8496; }
    .xp .xp-empty i { font-size:2rem; display:block; margin-bottom:6px; opacity:.6; }
    .xp details > summary { cursor:pointer; list-style:none; } .xp details > summary::-webkit-details-marker { display:none; }
    .xp .btn-xp { background:var(--xp-accent); border-color:var(--xp-accent); color:#fff; } .xp .btn-xp:hover { filter:brightness(.93); color:#fff; }
    .xp .risk-high { background:#fdecea; color:#b3261e; } .xp .risk-medium { background:#fff4e5; color:#8a5300; } .xp .risk-low { background:#e3f7ea; color:#137333; }
    .xp .form-label { font-size:.8rem; font-weight:600; margin-bottom:3px; }
    html[data-theme="dark"] .xp .ok, html[data-theme="dark"] body .xp .ok, html[data-theme="dark"] .xp .risk-low, html[data-theme="dark"] body .xp .risk-low { background:rgba(19,115,51,.25); color:#7ee2a0; }
    html[data-theme="dark"] .xp .bad, html[data-theme="dark"] body .xp .bad, html[data-theme="dark"] .xp .risk-high, html[data-theme="dark"] body .xp .risk-high { background:rgba(179,38,30,.25); color:#ff9b93; }
    html[data-theme="dark"] .xp .warn, html[data-theme="dark"] body .xp .warn, html[data-theme="dark"] .xp .risk-medium, html[data-theme="dark"] body .xp .risk-medium { background:rgba(138,83,0,.3); color:#ffcf85; }
    html[data-theme="dark"] .xp .muted, html[data-theme="dark"] body .xp .muted { background:rgba(120,130,150,.2); color:#c5cad6; }
    html[data-theme="dark"] .xp .info, html[data-theme="dark"] body .xp .info { background:rgba(26,86,196,.3); color:#a9c6ff; }
    @media (max-width: 576px) { .xp .xp-card { padding:14px; } .xp .xp-head { padding:14px; } }
</style>
@php
    $xpTabs = [
        'xp.ndr' => ['NDR', 'ti ti-alert-triangle'], 'xp.risk' => ['RTO risk check', 'ti ti-shield-x'],
        'xp.bulk' => ['Bulk actions', 'ti ti-stack-2'], 'xp.couriers' => ['Courier preferences', 'ti ti-truck'],
        'xp.reports' => ['Reports', 'ti ti-file-spreadsheet'], 'xp.tracking' => ['Tracking page', 'ti ti-map-pin'],
        'xp.protection' => ['Protection & early COD', 'ti ti-shield-check'],
    ];
@endphp
<div class="xp-tabs">
    @foreach($xpTabs as $r => [$label, $icon])
        <a href="{{ route($r) }}" class="{{ request()->routeIs($r . '*') ? 'on' : '' }}"><i class="{{ $icon }}"></i> {{ $label }}</a>
    @endforeach
</div>
{{-- success / error flashes are shown as toasts by the seller layout footer --}}
@if($errors->any())<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>@endif
@unless($xpReady ?? true)<div class="alert alert-warning">This section is being set up on your account. Please check back shortly.</div>@endunless
