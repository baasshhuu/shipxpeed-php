@extends('frontend.layouts.fullwidth')

@section('content')
<style>
@media (min-width: 576px) {
    /* Hide everything except the form on mobile */
    .only-mobile {
        display: none !important;
    }
    .forgot-form-section {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
    }
}
@media (max-width: 575.98px) {
    .forgot-form-section {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #1a365d;
    }
}
</style>

<div class="forgot-form-section">
    <div class="w-100" style="max-width: 470px;">
        <div class="login-form p-5 shadow rounded" style="background: #1a365d;">
            <h3 class="mb-2 font-weight-bold text-center" style="color: white;">Forgot Password?</h3>
            <p class="mb-3 text-center" style="color: white; font-size: 15px;">
                Enter your email address below and we'll send you a link to reset your password.
            </p>
            @if (session('success'))
                <div class="alert alert-success text-center">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger text-center">{{ session('error') }}</div>
            @endif
            <form method="POST" action="{{ route('seller.password.email') }}" autocomplete="off">
                @csrf
                <div class="form-group mb-3">
                    <label for="email" style="font-weight: 500;color: white;">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" placeholder="Enter your email" required autofocus>
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="d-flex justify-content-center">
                    <button type="submit" class="btn btn-primary mt-3" style="padding: 5px 24px; font-weight: 600; border-radius: 4px; background: linear-gradient(135deg, #1f4da9 0%, #31599d 50%, #0d2464 100%); border: none;">
                        Reset
                    </button>
                </div>
            </form>
            <div class="text-center mt-4" style="font-size: 15px;">
                <span style="color: white;">Remember your password?</span>
                <a href="{{ route('seller.login') }}" style="color: white; font-weight: 500; text-decoration: underline;">Login</a>
            </div>
            <div class="text-center mt-2" style="font-size: 15px;">
                <span style="color:white;">New to Shipxpeed?</span>
                <a href="{{ route('register.get') }}" style="color: white; font-weight: 500; text-decoration: underline;">Create an account</a>
            </div>
        </div>
    </div>
</div>
@endsection
