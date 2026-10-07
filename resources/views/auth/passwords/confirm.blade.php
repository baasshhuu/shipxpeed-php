@extends('layouts.auth')

@section('content')
<div class="card">
    <div class="card-body p-4 p-sm-5 text-center">
        <div class="avatar avatar-4xl">
            <img class="rounded-circle" src="{{ asset('assets/public/assets/img/team/1.jpg') }}" alt="">
        </div>
        <h5 class="mt-3 mb-0">Hi! Emma Watson</h5>
        <small>Please confirm your password before continuing.</small>
        <form class="mt-4 row g-0" action="{{ route('password.confirm') }}">
            @csrf
            <div class="col">
                <input placeholder="Enter your password" aria-label="User's password" aria-describedby="user-password"
                    id="password" type="password" class="form-control me-2 mb-2 @error('password') is-invalid @enderror"
                    name="password" required autocomplete="current-password">
                @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="col-auto ps-2">
                <button class="btn btn-secondary px-3 mb-2" id="user-password" type="button">Login</button>
            </div>
            <div>
                @if (Route::has('password.request'))
                <a class="btn btn-link fs--1" href="{{ route('password.request') }}">
                    {{ __('Forgot Your Password?') }}
                </a>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection