@extends('frontend.selleruser.auth_layout')

@section('title', 'Log in')

@section('content')
<div class="card">
    <h2>Welcome back</h2>
    <p class="sub">Log in to your Shipxpeed seller account.</p>

    <div id="formMessages" aria-live="polite">
        @if (session('success'))
            <div class="alert alert-success" role="status"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/></svg><div>{{ session('success') }}</div></div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger" role="alert"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg><div>{{ session('error') }}</div></div>
        @endif
    </div>

    <form method="POST" id="loginForm" action="{{ route('seller.login') }}" novalidate>
        @csrf
        <div class="field">
            <label for="email">Email address</label>
            <input type="email" name="email" id="email" class="input" placeholder="you@business.com" autocomplete="email" value="{{ old('email') }}" required autofocus>
            <div class="error error-email"></div>
        </div>
        <div class="field">
            <div class="label-row">
                <label for="password">Password</label>
                <a href="{{ route('seller.password.request') }}">Forgot password?</a>
            </div>
            <div class="control has-toggle">
                <input type="password" name="password" id="password" class="input" placeholder="Enter your password" autocomplete="current-password" required>
                <button type="button" class="toggle" data-toggle-password="password" aria-label="Show password"></button>
            </div>
            <div class="error error-password"></div>
        </div>
        <button type="submit" class="btn btn-primary" id="loginBtn"><span class="spin"></span><span class="btn-text">Log in</span></button>
    </form>

    <p class="foot">New to Shipxpeed? <a href="{{ route('register.get') }}">Create an account</a></p>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('loginForm');
    const btn = document.getElementById('loginBtn');
    const msgs = document.getElementById('formMessages');

    function fieldError(name, msg) {
        const input = form.querySelector('[name="' + name + '"]');
        if (input) input.classList.add('is-invalid');
        const el = form.querySelector('.error-' + name);
        if (el) el.textContent = msg;
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        msgs.innerHTML = '';
        form.querySelectorAll('.error').forEach(el => el.textContent = '');
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        const email = form.email.value.trim();
        const password = form.password.value;
        let ok = true;
        if (!email) { fieldError('email', 'Enter your email address.'); ok = false; }
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { fieldError('email', 'Enter a valid email address.'); ok = false; }
        if (!password) { fieldError('password', 'Enter your password.'); ok = false; }
        if (!ok) { form.querySelector('.is-invalid').focus(); return; }

        SX.loading(btn, true, 'Logging in…');
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: new FormData(form),
                credentials: 'same-origin'
            });
            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                btn.classList.add('is-done');
                SX.loading(btn, true, 'Success, opening dashboard…');
                btn.classList.remove('is-loading');
                window.location.href = data.redirect || "{{ route('seller.dashboard') }}";
                return;
            }
            SX.loading(btn, false, 'Log in');
            if (res.status === 422 && data.errors) {
                Object.keys(data.errors).forEach(k => fieldError(k, data.errors[k][0]));
            } else if (res.status === 401) {
                msgs.innerHTML = SX.alert('danger', SX.escape(data.message || 'Invalid email or password.'));
                form.password.select();
            } else if (res.status === 419) {
                msgs.innerHTML = SX.alert('danger', 'This page was open too long. Refreshing, please try again.');
                setTimeout(() => window.location.reload(), 1500);
            } else if (res.status === 429) {
                msgs.innerHTML = SX.alert('danger', 'Too many attempts. Please wait a minute and try again.');
            } else {
                msgs.innerHTML = SX.alert('danger', 'Something went wrong. Please try again.');
            }
        } catch (err) {
            SX.loading(btn, false, 'Log in');
            msgs.innerHTML = SX.alert('danger', 'Could not connect. Check your internet and try again.');
        }
    });
})();
</script>
@endpush
