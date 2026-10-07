@extends('frontend.website.layout.index')

@section('main_contant')
    <div class="container">
        <div class="privacy-policy">
            @if($term)
                <h1>{{ $term->title }}</h1>
                <div>{!! $term->description !!}</div>
            @else
                <h1>No Refund Policy Found</h1>
                <p>The refund policy content is currently not available.</p>
            @endif
        </div>
    </div>
@endsection
