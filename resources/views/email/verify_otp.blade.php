@extends('frontend.selleruser.auth_layout')

@section('title', 'Verify your email')
@section('aside_eyebrow', 'Almost there')
@section('aside_title', 'One quick check and you\'re in.')
@section('aside_lead', 'Enter the 6-digit code we emailed you to finish creating your Shipxpeed seller account.')

@push('styles')
<style>
    .otp { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; margin: 4px 0 8px; }
    .otp input { width: 100%; height: 56px; text-align: center; font: 600 22px var(--f-display); color: var(--heading); background: var(--field);
        border: 1px solid var(--line); border-radius: var(--r-md); transition: border-color .15s, box-shadow .15s; caret-color: var(--cyan); }
    .otp input:focus { outline: none; border-color: var(--cyan); box-shadow: 0 0 0 3px var(--cyan-50); }
    .otp input.filled { border-color: var(--line-2); }
    .otp.is-invalid input { border-color: var(--danger); }
    @media (max-width: 380px) { .otp { gap: 6px; } .otp input { height: 50px; font-size: 20px; } }
</style>
@endpush

@section('content')
@php $otpEmail = session('seller_temp_data')['email'] ?? null; @endphp
<div class="card">
    <div class="icon-badge" aria-hidden="true">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="m22 7-10 6L2 7"/></svg>
    </div>
    <h2>Check your email</h2>
    <p class="sub">
        @if ($otpEmail)
            Enter the 6-digit code we sent to <strong>{{ $otpEmail }}</strong>.
        @else
            Enter the 6-digit code we sent to your email.
        @endif
    </p>

    <div id="formMessages" aria-live="polite">
        @if (session('error'))
            <div class="alert alert-danger" role="alert"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg><div>{{ session('error') }}</div></div>
        @endif
        @if (session('success'))
            <div class="alert alert-success" role="status"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/></svg><div>{{ session('success') }}</div></div>
        @endif
        @if (!$otpEmail && !session('error'))
            <div class="alert alert-info" role="status"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg><div>No sign-up in progress on this device. <a href="{{ route('register.get') }}">Start a new sign-up</a>.</div></div>
        @endif
    </div>

    <form method="POST" action="{{ route('seller.verify.otp') }}" id="otpForm" novalidate>
        @csrf
        <span class="label" id="otpLabel">Verification code</span>
        <div class="otp" role="group" aria-labelledby="otpLabel">
            @for ($i = 0; $i < 6; $i++)
                <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" aria-label="Digit {{ $i + 1 }}" @if($i === 0) autocomplete="one-time-code" autofocus @else autocomplete="off" @endif>
            @endfor
        </div>
        <div class="error error-otp"></div>
        <input type="hidden" name="otp" id="otp-hidden">
        <button type="submit" class="btn btn-primary" id="otpBtn" style="margin-top:14px"><span class="spin"></span><span class="btn-text">Verify and create account</span></button>
    </form>

    <p class="hint" style="text-align:center;margin-top:16px">Didn't get it? Check your spam folder. The code works for this sign-up only.</p>
    <p class="foot">Wrong email? <a href="{{ route('register.get') }}">Start again</a></p>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('otpForm');
    const wrap = form.querySelector('.otp');
    const inputs = Array.from(wrap.querySelectorAll('input'));
    const hidden = document.getElementById('otp-hidden');
    const btn = document.getElementById('otpBtn');
    const err = form.querySelector('.error-otp');

    function code() { return inputs.map(i => i.value).join(''); }
    function sync() {
        inputs.forEach(i => i.classList.toggle('filled', !!i.value));
        wrap.classList.remove('is-invalid'); err.textContent = '';
    }
    function fill(from, digits) {
        for (let k = 0; k < digits.length && from + k < inputs.length; k++) inputs[from + k].value = digits[k];
        inputs[Math.min(from + digits.length, inputs.length - 1)].focus();
        sync();
        if (code().length === 6) submit();
    }
    function submit() {
        const otp = code();
        if (!/^\d{6}$/.test(otp)) { wrap.classList.add('is-invalid'); err.textContent = 'Enter all 6 digits of the code.'; inputs.find(i => !i.value)?.focus(); return; }
        hidden.value = otp;
        SX.loading(btn, true, 'Verifying…');
        form.submit();
    }

    inputs.forEach((input, idx) => {
        input.addEventListener('input', e => {
            const digits = e.target.value.replace(/\D/g, '');
            if (digits.length > 1) { fill(idx, digits.split('')); return; }
            e.target.value = digits;
            sync();
            if (digits && idx < inputs.length - 1) inputs[idx + 1].focus();
            if (code().length === 6) submit();
        });
        input.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !input.value && idx > 0) { inputs[idx - 1].value = ''; inputs[idx - 1].focus(); sync(); e.preventDefault(); }
            else if (e.key === 'ArrowLeft' && idx > 0) inputs[idx - 1].focus();
            else if (e.key === 'ArrowRight' && idx < inputs.length - 1) inputs[idx + 1].focus();
        });
        input.addEventListener('paste', e => {
            const digits = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            if (digits) { e.preventDefault(); fill(idx, digits.split('')); }
        });
        input.addEventListener('focus', () => input.select());
    });

    form.addEventListener('submit', e => { e.preventDefault(); if (!btn.disabled) submit(); });
})();
</script>
@endpush
