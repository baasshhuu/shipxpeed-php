@extends('layouts.error')

@section('title', 'Unauthorized Access')

@section('content')
<div class="container py-vh-10">
    <div class="row min-vh-80">
        <div class="col-md-6 d-flex justify-content-center align-items-center">
            <img src="{{ asset('assets/img/error.svg') }}" alt="404" class="error-image">
        </div>
        <div class="col-md-6 d-flex flex-column justify-content-center align-items-center">
            <div class="base">
                <h1 class="error-number">401</h1>
                <h2>Unauthorized Access</h2>
                <h5>(I'm sorry buddy...)</h5>
                <a href="{{ route('home') }}" class="btn btn-dark mt-3">Go to Home Page</a>
            </div>
        </div>
    </div>
</div>

@endsection