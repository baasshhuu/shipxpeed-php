{{-- Admin Control Center links (Seller Control Suite). Included from the admin sidebar. --}}
@if(\Illuminate\Support\Facades\Route::has('admin.xp.ndr'))
<li class="sx-menu-heading">Control Center</li>
@foreach([
    'admin.xp.ndr' => ['NDR Desk', 'fa-solid fa-triangle-exclamation'],
    'admin.xp.performance' => ['Courier Performance', 'fa-solid fa-gauge-high'],
    'admin.xp.risk' => ['RTO Risk', 'fa-solid fa-shield-halved'],
    'admin.xp.services' => ['Claims & Early COD', 'fa-solid fa-hand-holding-dollar'],
] as $__xpRoute => [$__xpLabel, $__xpIcon])
<li class="menu {{ request()->routeIs($__xpRoute . '*') ? 'active' : '' }}">
    <a href="{{ route($__xpRoute) }}" class="dropdown-toggle">
        <div class="">
            <i class="{{ $__xpIcon }}"></i>
            <span>{{ $__xpLabel }}</span>
        </div>
    </a>
</li>
@endforeach
@endif
