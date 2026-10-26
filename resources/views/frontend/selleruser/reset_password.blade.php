@extends('frontend.selleruser.auth_layout')

@section('title', 'Set a new password')
@section('aside_eyebrow', 'Account recovery')
@section('aside_title', 'Choose a new password and you\'re back in.')
@section('aside_lead', 'Use at least 6 characters. A mix of letters, numbers and symbols makes it much stronger.')

@section('content')
<div class="card">
    <div class="icon-badge" aria-hidden="true">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.6 7.6a5.5 5.5 0 1 1-7.78 7.78 5.5 5.5 0 0 1 7.78-7.78Zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
    </div>
    <h2>Set a new password</h2>
    <p class="sub">Enter your account email and choose a new password.</p>

    <div id="formMessages" aria-live="polite">
        @if (session('error'))
            <div class="alert alert-danger" role="alert"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg><div>{{ session('error') === 'Invalid token!' ? 'This reset link is invalid or has already been used.' : session('error') }} <a href="{{ route('seller.password.request') }}">Get a new link</a></div></div>
        @endif
    </div>

    <form method="POST" action="{{ route('seller.reset.password') }}" id="resetForm" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="field">
            <label for="email">Email address</label>
            <input type="email" name="email" id="email" class="input {{ $errors->has('email') ? 'is-invalid' : '' }}" placeholder="you@business.com" autocomplete="email" value="{{ old('email', request('email')) }}" required autofocus>
            <div class="error error-email">@error('email'){{ str_contains($message, 'invalid') ? 'We couldn\'t find an account with this email.' : $message }}@enderror</div>
        </div>

        <div class="field">
            <label for="password">New password</label>
            <div class="control has-toggle">
                <input type="password" name="password" id="password" class="input {{ $errors->has('password') ? 'is-invalid' : '' }}" placeholder="At least 6 characters" autocomplete="new-password" required>
                <button type="button" class="toggle" data-toggle-password="password" aria-label="Show password"></button>
            </div>
            <div class="strength" data-strength-for="password" data-score="0" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
            <div class="error error-password">@error('password'){{ $message }}@enderror</div>
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm new password</label>
            <div class="control has-toggle">
                <input type="password" name="password_confirmation" id="password_confirmation" class="input" placeholder="Re-enter new password" autocomplete="new-password" required>
                <button type="button" class="toggle" data-toggle-password="password_confirmation" aria-label="Show password"></button>
            </div>
            <div class="error error-password_confirmation"></div>
        </div>

        <button type="submit" class="btn btn-primary" id="resetBtn"><span class="spin"></span><span class="btn-text">Update password</span></button>
    </form>

    <p class="foot"><a href="{{ route('seller.login') }}">Back to log in</a></p>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('resetForm');
    const btn = document.getElementById('resetBtn');
    function fieldError(name, msg) {
        form.querySelector('[name="' + name + '"]').classList.add('is-invalid');
        form.querySelector('.error-' + name).textContent = msg;
    }
    form.addEventListener('submit', function (e) {
        form.querySelectorAll('.error').forEach(el => el.textContent = '');
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        let ok = true;
        const email = form.email.value.trim(), pw = form.password.value, cpw = form.password_confirmation.value;
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { fieldError('email', email ? 'Enter a valid email address.' : 'Enter your email address.'); ok = false; }
        if (pw.length < 6) { fieldError('password', 'Use at least 6 characters.'); ok = false; }
        if (!cpw) { fieldError('password_confirmation', 'Re-enter your new password.'); ok = false; }
        else if (pw !== cpw) { fieldError('password_confirmation', 'Passwords do not match.'); ok = false; }
        if (!ok) { e.preventDefault(); form.querySelector('.is-invalid').focus(); return; }
        SX.loading(btn, true, 'Updating password…');
    });
})();
</script>
@endpush
