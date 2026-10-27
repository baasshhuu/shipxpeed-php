@extends('frontend.selleruser.auth_layout')

@section('title', 'Create account')
@section('aside_eyebrow', 'Start shipping')
@section('aside_title', 'Set up your seller account in two minutes.')
@section('aside_lead', 'Sign up, verify your email and start comparing courier rates for your orders right away.')

@section('content')
<div class="card wide">
    <h2>Create your account</h2>
    <p class="sub">We'll email you a 6-digit code to verify it's you.</p>

    <div id="formMessages" aria-live="polite"></div>

    <form id="registerForm" enctype="multipart/form-data" novalidate>
        @csrf
        <div class="field">
            <span class="label" id="userTypeLabel">I'm signing up as</span>
            <div class="segmented" role="radiogroup" aria-labelledby="userTypeLabel">
                <input type="radio" name="user_type" id="ut_individual" value="1">
                <label for="ut_individual"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>Individual</label>
                <input type="radio" name="user_type" id="ut_business" value="2">
                <label for="ut_business"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>Business</label>
            </div>
            <div class="error error-user_type"></div>
        </div>

        <div class="field">
            <label for="name">Full name</label>
            <input type="text" name="name" id="name" class="input" placeholder="Your full name" autocomplete="name" required>
            <div class="error error-name"></div>
        </div>

        <div class="field-row two">
            <div class="field">
                <label for="reg_email">Email address</label>
                <input type="email" name="email" id="reg_email" class="input" placeholder="you@business.com" autocomplete="email" required>
                <div class="error error-email"></div>
            </div>
            <div class="field">
                <label for="phone_number">WhatsApp mobile number</label>
                <div class="control has-prefix">
                    <span class="prefix">+91</span>
                    <input type="tel" name="phone_number" id="phone_number" class="input" placeholder="10-digit number" inputmode="numeric" autocomplete="tel-national" required>
                </div>
                <div class="error error-phone_number"></div>
            </div>
        </div>

        <div class="field-row two">
            <div class="field">
                <label for="password">Password</label>
                <div class="control has-toggle">
                    <input type="password" name="password" id="password" class="input" placeholder="Min. 6 characters" autocomplete="new-password" required>
                    <button type="button" class="toggle" data-toggle-password="password" aria-label="Show password"></button>
                </div>
                <div class="strength" data-strength-for="password" data-score="0" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                <div class="error error-password"></div>
            </div>
            <div class="field">
                <label for="confirm_password">Confirm password</label>
                <div class="control has-toggle">
                    <input type="password" name="password_confirmation" id="confirm_password" class="input" placeholder="Re-enter password" autocomplete="new-password" required>
                    <button type="button" class="toggle" data-toggle-password="confirm_password" aria-label="Show password"></button>
                </div>
                <div class="error error-password_confirmation"></div>
            </div>
        </div>

        <button type="submit" id="submitBtn" class="btn btn-primary"><span class="spin"></span><span class="btn-text">Create account</span></button>
    </form>

    <p class="foot">Already have an account? <a href="{{ route('seller.login') }}">Log in</a></p>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('registerForm');
    const btn = document.getElementById('submitBtn');
    const msgs = document.getElementById('formMessages');
    const phone = document.getElementById('phone_number');

    // Keep digits only; accept pasted "+91 98765 43210" / "098765 43210"
    phone.addEventListener('input', function () {
        let d = this.value.replace(/\D/g, '');
        if (d.length > 10 && d.startsWith('91')) d = d.slice(2);
        if (d.length > 10 && d.startsWith('0')) d = d.slice(1);
        this.value = d.slice(0, 10);
    });

    function fieldError(name, msg) {
        const input = form.querySelector('[name="' + name + '"]');
        if (input) {
            if (input.type === 'radio') input.closest('.segmented').classList.add('is-invalid');
            else input.classList.add('is-invalid');
        }
        const el = form.querySelector('.error-' + name);
        if (el) el.textContent = msg;
    }
    function firstInvalid() {
        const el = form.querySelector('.input.is-invalid, .segmented.is-invalid input');
        if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.focus({ preventScroll: true }); }
    }

    function validate() {
        const v = n => (form.querySelector('[name="' + n + '"]').value || '').trim();
        let ok = true;
        if (!form.querySelector('[name="user_type"]:checked')) { fieldError('user_type', 'Choose Individual or Business.'); ok = false; }
        if (!v('name')) { fieldError('name', 'Enter your full name.'); ok = false; }
        if (!v('email')) { fieldError('email', 'Enter your email address.'); ok = false; }
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v('email'))) { fieldError('email', 'Enter a valid email address.'); ok = false; }
        if (!/^[6-9]\d{9}$/.test(v('phone_number'))) { fieldError('phone_number', 'Enter a valid 10-digit mobile number.'); ok = false; }
        const pw = form.password.value, cpw = form.password_confirmation.value;
        if (pw.length < 6) { fieldError('password', 'Use at least 6 characters.'); ok = false; }
        if (!cpw) { fieldError('password_confirmation', 'Re-enter your password.'); ok = false; }
        else if (pw !== cpw) { fieldError('password_confirmation', 'Passwords do not match.'); ok = false; }
        return ok;
    }

    form.password_confirmation.addEventListener('blur', function () {
        if (this.value && this.value !== form.password.value) fieldError('password_confirmation', 'Passwords do not match.');
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        msgs.innerHTML = '';
        form.querySelectorAll('.error').forEach(el => el.textContent = '');
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        if (!validate()) { firstInvalid(); return; }

        SX.loading(btn, true, 'Creating account…');
        try {
            const res = await fetch("{{ route('register.add') }}", {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: new FormData(form),
                credentials: 'same-origin'
            });
            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                btn.classList.remove('is-loading'); btn.classList.add('is-done');
                btn.querySelector('.btn-text').textContent = 'Code sent, opening verification…';
                msgs.innerHTML = SX.alert('success', SX.escape(data.message || 'OTP sent to your email.'));
                setTimeout(() => { window.location.href = data.redirect_url || "{{ route('seller.verify.otp.view') }}"; }, 900);
                return;
            }
            SX.loading(btn, false, 'Create account');
            if (res.status === 422 && data.errors) {
                Object.keys(data.errors).forEach(k => {
                    const m = data.errors[k][0];
                    fieldError(k, k === 'email' && /taken/i.test(m) ? 'An account with this email already exists. Log in instead.' : m);
                });
                firstInvalid();
            } else if (res.status === 419) {
                msgs.innerHTML = SX.alert('danger', 'This page was open too long. Refreshing, please try again.');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                msgs.innerHTML = SX.alert('danger', 'We couldn\'t create your account right now. Please try again in a moment.');
            }
        } catch (err) {
            SX.loading(btn, false, 'Create account');
            msgs.innerHTML = SX.alert('danger', 'Could not connect. Check your internet and try again.');
        }
    });
})();
</script>
@endpush
