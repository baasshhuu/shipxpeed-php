@extends('layouts.auth')

@section('content')
<div class="container">
    <p class="fs-6 mb-4 text-dark">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the
        link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
    <p class="fs-6 mb-4 text-danger">
        {{ __('A new verification link has been sent to the email address you provided during registration.') }}
    </p>
    @endif

    <div class="mt-4 d-flex align-items-center justify-content-start gap-2">
        <form method="POST" action="{{ route('verification.send', $guard) }}">
            @csrf
            <div>
                <button class="btn btn-secondary">
                    {{ __('Resend Verification Email') }}
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-dark">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</div>
@endsection