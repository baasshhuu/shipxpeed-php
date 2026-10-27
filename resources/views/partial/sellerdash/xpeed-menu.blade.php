{{-- Seller Control Center links (Seller Control Suite). Included from the seller sidebar. --}}
@if(\Illuminate\Support\Facades\Route::has('xp.ndr'))
<li class="nav-item mt-2" style="pointer-events:none">
    <span class="nav-link small text-uppercase" style="opacity:.55;font-size:.68rem;letter-spacing:.06em;padding-bottom:2px">Control Center</span>
</li>
@foreach([
    'xp.ndr' => ['NDR', 'ti ti-alert-triangle'],
    'xp.risk' => ['RTO Risk Check', 'ti ti-shield-x'],
    'xp.bulk' => ['Bulk Actions', 'ti ti-stack-2'],
    'xp.couriers' => ['Courier Preferences', 'ti ti-truck'],
    'xp.reports' => ['Reports', 'ti ti-file-spreadsheet'],
    'xp.tracking' => ['Tracking Page', 'ti ti-map-pin'],
    'xp.protection' => ['Protection & Early COD', 'ti ti-shield-check'],
] as $__xpRoute => [$__xpLabel, $__xpIcon])
<li class="nav-item {{ request()->routeIs($__xpRoute . '*') ? 'active' : '' }}">
    <a href="{{ route($__xpRoute) }}" class="nav-link sidebar-sub-nav-link d-flex align-items-center {{ request()->routeIs($__xpRoute . '*') ? 'active' : '' }}">
        <i class="{{ $__xpIcon }}"></i>
        <span class="ms-2">{{ $__xpLabel }}</span>
    </a>
</li>
@endforeach
@endif
