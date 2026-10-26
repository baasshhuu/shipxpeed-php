@extends('frontend.selleruser.auth_layout')

@section('title', 'Forgot password')
@section('aside_eyebrow', 'Account recovery')
@section('aside_title', 'Locked out? Back to shipping in a minute.')
@section('aside_lead', 'We\'ll email you a secure link to set a new password for your seller account.')

@section('content')
<div class="card">
    <div class="icon-badge" aria-hidden="true">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
    </div>
    <h2>Forgot your password?</h2>
    <p class="sub">Enter the email you signed up with and we'll send you a reset link.</p>

    <div id="formMessages" aria-live="polite">
        @if (session('success'))
            <div class="alert alert-success" role="status"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/></svg><div>{{ session('success') }} Check your inbox and spam folder.</div></div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger" role="alert"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg><div>{{ session('error') }}</div></div>
        @endif
    </div>

    <form method="POST" action="{{ route('seller.password.email') }}" id="forgotForm" novalidate>
        @csrf
        <div class="field">
            <label for="email">Email address</label>
            <input type="email" name="email" id="email" class="input {{ $errors->has('email') ? 'is-invalid' : '' }}" placeholder="you@business.com" autocomplete="email" value="{{ old('email') }}" required autofocus>
            <div class="error error-email">@error('email'){{ str_contains($message, 'invalid') ? 'We couldn\'t find an account with this email.' : $message }}@enderror</div>
        </div>
        <button type="submit" class="btn btn-primary" id="forgotBtn"><span class="spin"></span><span class="btn-text">{{ session('success') ? 'Send the link again' : 'Send reset link' }}</span></button>
    </form>

    <p class="foot">Remembered it? <a href="{{ route('seller.login') }}">Back to log in</a></p>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('forgotForm');
    const btn = document.getElementById('forgotBtn');
    form.addEventListener('submit', function (e) {
        const email = form.email.value.trim();
        const err = form.querySelector('.error-email');
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            e.preventDefault();
            form.email.classList.add('is-invalid');
            err.textContent = email ? 'Enter a valid email address.' : 'Enter your email address.';
            form.email.focus();
            return;
        }
        SX.loading(btn, true, 'Sending link…');
    });
})();
</script>
@endpush
