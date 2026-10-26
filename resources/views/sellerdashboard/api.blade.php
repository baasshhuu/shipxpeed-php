@extends('layouts.sellerdash')
@section('content')
@php $apiSeller = \Illuminate\Support\Facades\Auth::guard('seller')->user(); @endphp
<div class="pc-container">
    <div class="pc-content">
        @include('apidocs.content', [
            'docs' => app(\App\Services\SellerApiDocs::class),
            'baseUrl' => rtrim(url('/api'), '/'),
            'inPanel' => true,
            'apiToken' => $apiSeller?->api_token,
        ])
    </div>
</div>
@endsection
