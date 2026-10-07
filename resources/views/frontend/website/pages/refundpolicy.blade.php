@extends('frontend.website.layout.index')

@section('main_contant')
    <div class="container">
        <div class="privacy-policy">
            @if($refund)
                <h1>{{ $refund->title }}</h1>
                <div>{!! $refund->description !!}</div>
            @else
                <h1>No Refund Policy Found</h1>
                <p>The refund policy content is currently not available.</p>
            @endif
        </div>
    </div>
@endsection
