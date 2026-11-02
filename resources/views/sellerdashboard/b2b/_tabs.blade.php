{{-- B2B Shipments section tabs (separate from the B2C order pages) --}}
@php
    $b2Tabs = [
        ['seller.b2b.order', 'Create shipment', 'ti ti-circle-plus', ['seller.b2b.order', 'seller.b2b.order.store']],
        ['seller.b2b.orders', 'All shipments', 'ti ti-package', ['seller.b2b.orders', 'seller.b2b.order.show']],
        ['seller.b2b.rates', 'Rate calculator', 'ti ti-calculator', ['seller.b2b.rates']],
        ['seller.b2b.track', 'Track', 'ti ti-map-pin', ['seller.b2b.track']],
    ];
@endphp
<div class="b2-nav">
    <span class="b2-nav-title"><i class="ti ti-truck-delivery"></i> B2B Shipments</span>
    <nav>
        @foreach($b2Tabs as [$rn, $label, $icon, $active])
            <a href="{{ route($rn) }}" class="{{ request()->routeIs(...$active) ? 'on' : '' }}"><i class="{{ $icon }}"></i> {{ $label }}</a>
        @endforeach
    </nav>
</div>
