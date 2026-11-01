{{-- Value Added Services left menu (shared by every service tab and the Notify page). --}}
@php $vasCurrent = $current ?? ''; @endphp
<aside class="vas-side">
    <h6>Value added services</h6>
    @foreach(\App\Http\Controllers\selleradmin\ValueServicesController::TABS as $vKey => [$vLabel, $vIcon])
        <a href="{{ $vKey === 'notify' ? route('notifications.index') : route('vas.show', $vKey) }}" class="{{ $vasCurrent === $vKey ? 'on' : '' }}">
            <i class="{{ $vIcon }}"></i> {{ $vLabel }}
            @if($vKey === 'brand-boost')<span class="vas-new">New</span>@endif
        </a>
    @endforeach
    <h6 style="margin-top:14px">Related</h6>
    <a href="{{ url('/seller/weight-discrepancy') }}"><i class="ti ti-scale"></i> Weight disputes</a>
    @if(Route::has('xp.ndr'))<a href="{{ route('xp.ndr') }}"><i class="ti ti-alert-triangle"></i> NDR</a>@endif
    @if(Route::has('seller.whatsapp.alerts'))<a href="{{ route('seller.whatsapp.alerts') }}"><i class="ti ti-brand-whatsapp"></i> WhatsApp history</a>@endif
</aside>
