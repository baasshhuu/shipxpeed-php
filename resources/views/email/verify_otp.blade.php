@extends('frontend.selleruser.auth_layout')

@section('title', 'Verify your account')
@section('aside_eyebrow', 'Almost there')
@section('aside_title', 'One quick check and you\'re in.')
@section('aside_lead', 'Enter the 6-digit codes we sent you to finish creating your Shipxpeed seller account.')

@push('styles')
<style>
    .otp { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; margin: 4px 0 8px; }
    .otp input { width: 100%; height: 56px; text-align: center; font: 600 22px var(--f-display); color: var(--heading); background: var(--field);
        border: 1px solid var(--line); border-radius: var(--r-md); transition: border-color .15s, box-shadow .15s; caret-color: var(--cyan); }
    .otp input:focus { outline: none; border-color: var(--cyan); box-shadow: 0 0 0 3px var(--cyan-50); }
    .otp input.filled { border-color: var(--line-2); }
    .otp.is-invalid input { border-color: var(--danger); }
    .otp-block + .otp-block { margin-top: 18px; }
    .otp-head { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
    .linkbtn { background: none; border: 0; padding: 0; color: var(--cyan); font: inherit; font-size: 13px; cursor: pointer; }
    .linkbtn[disabled] { color: var(--muted, #8a94a6); cursor: default; }
    @media (max-width: 380px) { .otp { gap: 6px; } .otp input { height: 50px; font-size: 20px; } }
</style>
@endpush

@section('content')
@php
    $otpEmail = session('seller_temp_data')['email'] ?? null;
    $waRequired = !empty(session('seller_temp_data')['wa_required']);
    $waPhone = session('seller_temp_data')['wa_phone'] ?? null;
    $otpVia = \App\Services\WhatsApp\WhatsAppSettings::otpChannelLabel();
    $waMasked = $waPhone ? substr($waPhone, 0, 3) . ' ' . substr($waPhone, 3, 2) . '••••' . substr($waPhone, -3) : null;
@endphp
<div class="card">
    <div class="icon-badge" aria-hidden="true">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="m22 7-10 6L2 7"/></svg>
    </div>
    <h2>{{ $waRequired ? 'Verify email and mobile' : 'Check your email' }}</h2>
    <p class="sub">
        @if ($otpEmail)
            Enter the 6-digit code we sent to <strong>{{ $otpEmail }}</strong>@if($waRequired) and the {{ $otpVia }} code sent to <strong>{{ $waMasked }}</strong>@endif.
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
        <div class="otp-block" data-target="otp-hidden" data-name="email">
            <span class="label" id="otpLabel">Email code</span>
            <div class="otp" role="group" aria-labelledby="otpLabel">
                @for ($i = 0; $i < 6; $i++)
                    <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" aria-label="Email code digit {{ $i + 1 }}" @if($i === 0) autocomplete="one-time-code" autofocus @else autocomplete="off" @endif>
                @endfor
            </div>
            <div class="error error-otp"></div>
        </div>
        <input type="hidden" name="otp" id="otp-hidden">
        @if ($waRequired)
        <div class="otp-block" data-target="mobile-otp-hidden" data-name="{{ $otpVia }}">
            <div class="otp-head">
                <span class="label" id="waLabel">{{ $otpVia }} code</span>
                <button type="button" class="linkbtn" id="waResend">Resend</button>
            </div>
            <div class="otp" role="group" aria-labelledby="waLabel">
                @for ($i = 0; $i < 6; $i++)
                    <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" aria-label="{{ $otpVia }} code digit {{ $i + 1 }}" autocomplete="off">
                @endfor
            </div>
            <div class="error error-otp"></div>
            <div class="hint" id="waMsg" aria-live="polite"></div>
        </div>
        <input type="hidden" name="mobile_otp" id="mobile-otp-hidden">
        @endif
        <button type="submit" class="btn btn-primary" id="otpBtn" style="margin-top:14px"><span class="spin"></span><span class="btn-text">Verify and create account</span></button>
    </form>

    <p class="hint" style="text-align:center;margin-top:16px">Didn't get the email? Check your spam folder. Codes work for this sign-up only.</p>
    <p class="foot">Wrong email? <a href="{{ route('register.get') }}">Start again</a></p>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('otpForm');
    const btn = document.getElementById('otpBtn');
    const blocks = Array.from(form.querySelectorAll('.otp-block')).map(el => ({
        el, wrap: el.querySelector('.otp'), inputs: Array.from(el.querySelectorAll('.otp input')),
        err: el.querySelector('.error-otp'), hidden: document.getElementById(el.dataset.target), name: el.dataset.name
    }));

    const code = b => b.inputs.map(i => i.value).join('');
    const allFilled = () => blocks.every(b => code(b).length === 6);
    function sync(b) { b.inputs.forEach(i => i.classList.toggle('filled', !!i.value)); b.wrap.classList.remove('is-invalid'); b.err.textContent = ''; }
    function next(bi) { const nb = blocks[bi + 1]; if (nb) nb.inputs.find(i => !i.value)?.focus(); }
    function fill(b, bi, from, digits) {
        for (let k = 0; k < digits.length && from + k < b.inputs.length; k++) b.inputs[from + k].value = digits[k];
        b.inputs[Math.min(from + digits.length, b.inputs.length - 1)].focus();
        sync(b);
        if (code(b).length === 6) { allFilled() ? submit() : next(bi); }
    }
    function submit() {
        let ok = true;
        blocks.forEach(b => {
            const v = code(b);
            if (!/^\d{6}$/.test(v)) { ok = false; b.wrap.classList.add('is-invalid'); b.err.textContent = 'Enter all 6 digits of the ' + b.name + ' code.'; }
            b.hidden.value = v;
        });
        if (!ok) { blocks.find(b => code(b).length !== 6)?.inputs.find(i => !i.value)?.focus(); return; }
        SX.loading(btn, true, 'Verifying…');
        form.submit();
    }

    blocks.forEach((b, bi) => b.inputs.forEach((input, idx) => {
        input.addEventListener('input', e => {
            const digits = e.target.value.replace(/\D/g, '');
            if (digits.length > 1) { fill(b, bi, idx, digits.split('')); return; }
            e.target.value = digits;
            sync(b);
            if (digits && idx < b.inputs.length - 1) b.inputs[idx + 1].focus();
            if (code(b).length === 6) { allFilled() ? submit() : next(bi); }
        });
        input.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !input.value && idx > 0) { b.inputs[idx - 1].value = ''; b.inputs[idx - 1].focus(); sync(b); e.preventDefault(); }
            else if (e.key === 'ArrowLeft' && idx > 0) b.inputs[idx - 1].focus();
            else if (e.key === 'ArrowRight' && idx < b.inputs.length - 1) b.inputs[idx + 1].focus();
        });
        input.addEventListener('paste', e => {
            const digits = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            if (digits) { e.preventDefault(); fill(b, bi, idx, digits.split('')); }
        });
        input.addEventListener('focus', () => input.select());
    }));

    form.addEventListener('submit', e => { e.preventDefault(); if (!btn.disabled) submit(); });

    // WhatsApp resend with countdown
    const resend = document.getElementById('waResend');
    const waMsg = document.getElementById('waMsg');
    function countdown(sec) {
        if (!resend) return;
        resend.disabled = true;
        const t = setInterval(() => {
            resend.textContent = 'Resend in ' + sec + 's';
            if (--sec < 0) { clearInterval(t); resend.disabled = false; resend.textContent = 'Resend'; }
        }, 1000);
    }
    if (resend) {
        countdown(45);
        resend.addEventListener('click', async () => {
            resend.disabled = true; waMsg.textContent = 'Sending…';
            try {
                const res = await fetch("{{ route('seller.verify.otp.wa.resend') }}", {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value }
                });
                const data = await res.json().catch(() => ({}));
                waMsg.textContent = data.message || (res.ok ? 'Code sent.' : 'Could not send. Try again.');
                countdown(data.retry_after || 45);
            } catch (e) { waMsg.textContent = 'Could not connect. Try again.'; resend.disabled = false; }
        });
    }
})();
</script>
@endpush
