@extends('layouts.sellerdash')

@section('content')
@include('sellerdashboard.vas._style')
<div class="pc-container vas">
<div class="pc-content sxd">{{-- "sxd": keeps the global theme from repainting this page --}}
    <div class="vas-crumb"><a href="{{ url('/seller/dashboard') }}">Dashboard</a><i class="ti ti-chevron-right"></i><a href="{{ route('vas.index') }}">Value added services</a><i class="ti ti-chevron-right"></i><span style="color:var(--v-fg)">{{ $tabs[$tab][0] ?? '' }}</span></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if(isset($errors) && $errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="vas-wrap">
        @include('sellerdashboard.vas._nav', ['current' => $tab])
        <section class="vas-main">
            @yield('vas')
        </section>
    </div>
</div>
</div>
@endsection
