@extends('frontend.selleruser.auth_layout')

@section('title', 'Log in')

@php $waLogin = \App\Services\WhatsApp\WhatsAppSettings::otpEnabled(); $otpVia = \App\Services\WhatsApp\WhatsAppSettings::otpChannelLabel(); $otpVerb = $otpVia === 'SMS' ? 'by SMS' : 'on WhatsApp'; @endphp

@push('styles')
<style>
    .tabs { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; padding: 4px; margin: 0 0 18px; background: var(--field); border: 1px solid var(--line); border-radius: var(--r-md); }
    .tabs button { border: 0; background: none; padding: 9px 10px; border-radius: calc(var(--r-md) - 3px); color: var(--muted, #8a94a6); font-family: inherit; font-size: 14px; font-weight: 600; cursor: pointer; }
    .tabs button[aria-selected="true"] { background: var(--cyan-50); color: var(--heading); }
    .tabs button:focus-visible { outline: 2px solid var(--cyan); }
    [hidden] { display: none !important; }
    .otp-row { display: flex; gap: 8px; }
    .otp-row .input { flex: 1; letter-spacing: 6px; font-weight: 600; text-align: center; }
    .linkbtn { background: none; border: 0; padding: 0; color: var(--cyan); font: inherit; font-size: 13px; cursor: pointer; }
    .linkbtn[disabled] { color: var(--muted, #8a94a6); cursor: default; }
</style>
@endpush

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

    @if ($waLogin)
    <div class="tabs" role="tablist" aria-label="Login method">
        <button type="button" role="tab" id="tabEmail" aria-selected="true" aria-controls="loginForm">Email &amp; password</button>
        <button type="button" role="tab" id="tabWa" aria-selected="false" aria-controls="waForm">Mobile OTP</button>
    </div>
    @endif

    <form method="POST" id="loginForm" action="{{ route('seller.login') }}" novalidate role="tabpanel" aria-labelledby="tabEmail">
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

    @if ($waLogin)
    <form id="waForm" novalidate hidden role="tabpanel" aria-labelledby="tabWa">
        @csrf
        <div class="field">
            <label for="wa_phone">Registered mobile number</label>
            <div class="control has-prefix">
                <span class="prefix">+91</span>
                <input type="tel" name="phone" id="wa_phone" class="input" placeholder="10-digit number" inputmode="numeric" autocomplete="tel-national" maxlength="10">
            </div>
            <div class="error error-phone"></div>
        </div>
        <div class="field" id="waOtpField" hidden>
            <div class="label-row">
                <label for="wa_otp">Code sent {{ $otpVerb }}</label>
                <button type="button" class="linkbtn" id="waResend">Resend</button>
            </div>
            <div class="otp-row">
                <input type="text" name="otp" id="wa_otp" class="input" placeholder="••••••" inputmode="numeric" autocomplete="one-time-code" maxlength="6">
            </div>
            <div class="error error-otp"></div>
        </div>
        <button type="submit" class="btn btn-primary" id="waBtn"><span class="spin"></span><span class="btn-text">Send code {{ $otpVerb }}</span></button>
    </form>
    @endif

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

@if ($waLogin)
(function () {
    const tabs = { email: document.getElementById('tabEmail'), wa: document.getElementById('tabWa') };
    const panes = { email: document.getElementById('loginForm'), wa: document.getElementById('waForm') };
    const msgs = document.getElementById('formMessages');
    function show(which) {
        Object.keys(tabs).forEach(k => { tabs[k].setAttribute('aria-selected', k === which); panes[k].hidden = k !== which; });
        msgs.innerHTML = '';
        (which === 'wa' ? document.getElementById('wa_phone') : document.getElementById('email')).focus();
    }
    tabs.email.addEventListener('click', () => show('email'));
    tabs.wa.addEventListener('click', () => show('wa'));

    const form = panes.wa, btn = document.getElementById('waBtn');
    const phone = document.getElementById('wa_phone'), otp = document.getElementById('wa_otp');
    const otpField = document.getElementById('waOtpField'), resend = document.getElementById('waResend');
    const csrf = form.querySelector('[name=_token]').value;
    let sent = false, timer;

    phone.addEventListener('input', () => { phone.value = phone.value.replace(/\D/g, '').slice(0, 10); if (sent) reset(); });
    otp.addEventListener('input', () => { otp.value = otp.value.replace(/\D/g, '').slice(0, 6); if (otp.value.length === 6) verify(); });

    function clearErr() { form.querySelectorAll('.error').forEach(el => el.textContent = ''); form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid')); }
    function err(name, m) { const i = form.querySelector('[name="' + name + '"]'); if (i) i.classList.add('is-invalid'); form.querySelector('.error-' + name).textContent = m; }
    function reset() { sent = false; otpField.hidden = true; otp.value = ''; SX.loading(btn, false, @json('Send code ' . $otpVerb)); }
    function countdown(sec) {
        clearInterval(timer); resend.disabled = true;
        timer = setInterval(() => { resend.textContent = 'Resend in ' + sec + 's'; if (--sec < 0) { clearInterval(timer); resend.disabled = false; resend.textContent = 'Resend'; } }, 1000);
    }
    async function post(url, body) {
        const res = await fetch(url, { method: 'POST', credentials: 'same-origin', body,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } });
        return { res, data: await res.json().catch(() => ({})) };
    }
    function handleFail(res, data) {
        if (data.errors) { Object.keys(data.errors).forEach(k => err(k, data.errors[k][0])); return; }
        if (res.status === 419) { msgs.innerHTML = SX.alert('danger', 'This page was open too long. Refreshing…'); setTimeout(() => location.reload(), 1500); return; }
        msgs.innerHTML = SX.alert('danger', SX.escape(data.message || 'Something went wrong. Please try again.'));
    }

    async function send() {
        clearErr(); msgs.innerHTML = '';
        if (!/^[6-9]\d{9}$/.test(phone.value)) { err('phone', 'Enter a valid 10-digit mobile number.'); phone.focus(); return; }
        SX.loading(btn, true, 'Sending code…');
        try {
            const fd = new FormData(); fd.append('phone', phone.value);
            const { res, data } = await post("{{ route('seller.login.otp.send') }}", fd);
            if (res.ok) {
                sent = true; otpField.hidden = false; otp.focus();
                msgs.innerHTML = SX.alert('success', SX.escape(data.message || @json('Code sent ' . $otpVerb . '.')));
                SX.loading(btn, false, 'Verify and log in'); countdown(data.retry_after || 45);
            } else { SX.loading(btn, false, sent ? 'Verify and log in' : @json('Send code ' . $otpVerb)); handleFail(res, data); }
        } catch (e) { SX.loading(btn, false, @json('Send code ' . $otpVerb)); msgs.innerHTML = SX.alert('danger', 'Could not connect. Check your internet and try again.'); }
    }
    async function verify() {
        if (btn.disabled) return;
        clearErr();
        if (!/^\d{6}$/.test(otp.value)) { err('otp', 'Enter the 6-digit code.'); otp.focus(); return; }
        SX.loading(btn, true, 'Logging in…');
        try {
            const fd = new FormData(); fd.append('phone', phone.value); fd.append('otp', otp.value);
            const { res, data } = await post("{{ route('seller.login.otp.verify') }}", fd);
            if (res.ok) { SX.loading(btn, true, 'Success, opening dashboard…'); location.href = data.redirect || "{{ route('seller.dashboard') }}"; return; }
            SX.loading(btn, false, 'Verify and log in'); handleFail(res, data); otp.select();
        } catch (e) { SX.loading(btn, false, 'Verify and log in'); msgs.innerHTML = SX.alert('danger', 'Could not connect. Check your internet and try again.'); }
    }
    resend.addEventListener('click', send);
    form.addEventListener('submit', e => { e.preventDefault(); sent ? verify() : send(); });
})();
@endif
</script>
@endpush
