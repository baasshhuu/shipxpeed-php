{{-- Value Added Services left menu: every service tab plus the Control Center pages. --}}
@php
    $vasCls = \App\Http\Controllers\selleradmin\ValueServicesController::class;
    $vasCurrent = $current ?? $vasCls::current();
@endphp
<aside class="vas-side" aria-label="Value added services">
    <h6>Value added services</h6>
    @foreach($vasCls::TABS as $vKey => [$vLabel, $vIcon])
        @php $vUrl = $vasCls::url($vKey); @endphp
        @if($vUrl)
        <a href="{{ $vUrl }}" class="{{ $vasCurrent === $vKey ? 'on' : '' }}" @if($vasCurrent === $vKey) aria-current="page" @endif>
            <i class="{{ $vIcon }}"></i> {{ $vLabel }}
        </a>
        @endif
    @endforeach
    <h6 style="margin-top:14px">More tools</h6>
    <a href="{{ url('/seller/weight-discrepancy') }}"><i class="ti ti-scale"></i> Weight disputes</a>
    @if(Route::has('xp.reports'))<a href="{{ route('xp.reports') }}" class="{{ request()->routeIs('xp.reports*') ? 'on' : '' }}"><i class="ti ti-file-text"></i> Reports</a>@endif
    @if(Route::has('xp.tracking'))<a href="{{ route('xp.tracking') }}" class="{{ request()->routeIs('xp.tracking*') ? 'on' : '' }}"><i class="ti ti-map-pin"></i> Tracking page</a>@endif
    @if(Route::has('xp.protection'))<a href="{{ route('xp.protection') }}#claim" class="{{ request()->routeIs('xp.protection*') ? 'on' : '' }}"><i class="ti ti-file-alert"></i> Claims</a>@endif
    @if(Route::has('seller.whatsapp.alerts'))<a href="{{ route('seller.whatsapp.alerts') }}"><i class="ti ti-brand-whatsapp"></i> WhatsApp history</a>@endif
</aside>
