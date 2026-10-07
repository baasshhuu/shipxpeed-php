@extends('layouts.error')

@section('title', 'Page Not Found')

@section('content')
<div class="container py-vh-10">
    <div class="row min-vh-80">
        <div class="col-md-6 d-flex justify-content-center align-items-center">
            <img src="{{ asset('assets/img/error.svg') }}" alt="404" class="error-image">
        </div>
        <div class="col-md-6 d-flex flex-column justify-content-center align-items-center">
            <h1 class="error-number">404</h1>
            <p class="h4">Ooops!</p>
            <p class="h6 mb-3 mt-1">The page you requested was not found.!</p>
            <div>
                <a href="{{ route('home') }}" class="btn btn-dark mt-3">Go to Home Page</a>
            </div>
        </div>
    </div>
</div>

@endsection