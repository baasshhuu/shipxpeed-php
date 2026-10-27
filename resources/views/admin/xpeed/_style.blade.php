<style>
    .xa .pill{display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;white-space:nowrap}
    .xa .ok{background:var(--sx-success-soft,#e6f6ec);color:var(--sx-success,#137333)}
    .xa .bad{background:var(--sx-danger-soft,#fdecea);color:var(--sx-danger,#b3261e)}
    .xa .warn{background:var(--sx-warning-soft,#fff4e5);color:var(--sx-warning,#8a5300)}
    .xa .muted{background:rgba(120,130,150,.15);color:inherit;opacity:.85}
    .xa .info{background:rgba(47,111,237,.15);color:#4f8cff}
    .xa .mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px}
    .xa table td,.xa table th{vertical-align:middle;font-size:13px}
    .xa .stat{font-size:1.4rem;font-weight:700;line-height:1.2}.xa .stat-l{font-size:.78rem;opacity:.7}
    .xa .risk-high{background:var(--sx-danger-soft,#fdecea);color:var(--sx-danger,#b3261e)}
    .xa .risk-medium{background:var(--sx-warning-soft,#fff4e5);color:var(--sx-warning,#8a5300)}
    .xa .risk-low{background:var(--sx-success-soft,#e6f6ec);color:var(--sx-success,#137333)}
    .xa .bar{height:6px;border-radius:3px;background:rgba(120,130,150,.2);overflow:hidden}.xa .bar span{display:block;height:100%}
    .xa-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}
    .xa-tabs a{padding:6px 12px;border-radius:999px;font-size:.8rem;font-weight:600;text-decoration:none;border:1px solid rgba(120,130,150,.3);color:inherit}
    .xa-tabs a.on{background:#2f6fed;border-color:#2f6fed;color:#fff}
</style>
<div class="xa-tabs">
    @foreach(['admin.xp.ndr' => 'NDR desk', 'admin.xp.performance' => 'Courier performance', 'admin.xp.risk' => 'RTO risk', 'admin.xp.services' => 'Protection, claims & early COD'] as $r => $l)
        <a href="{{ route($r) }}" class="{{ request()->routeIs($r . '*') ? 'on' : '' }}">{{ $l }}</a>
    @endforeach
</div>
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
