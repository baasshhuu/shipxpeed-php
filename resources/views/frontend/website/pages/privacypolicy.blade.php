@extends('frontend.website.layout.index')

@section('main_contant')
    <div class="container">
        <div class="privacy-policy">
            <h1>{{ $privacy->title }}</h1>
            <div>{!! $privacy->description !!}</div>
        </div>
    </div>
@endsection
