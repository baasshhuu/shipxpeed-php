<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="dark">
    <title>@yield('title', 'Seller account') · Shipxpeed</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('site/assets/img/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('site/assets/img/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Inter+Tight:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #070C17; --surface: #0E1729; --field: #0A1221; --line: #1F2C47; --line-2: #2B3B5E;
            --ink: #C9D4E5; --ink-2: #9AA9C2; --ink-3: #8090AD; --heading: #FFFFFF;
            --cyan: #22C7D6; --cyan-hover: #5BE1EC; --cyan-50: rgba(34,199,214,.14); --link: #67DDE8;
            --success: #34D399; --success-50: rgba(52,211,153,.12);
            --danger: #F87171; --danger-50: rgba(248,113,113,.12); --warn: #FBBF24;
            --r-md: 12px; --r-lg: 18px;
            --f-display: "Inter Tight", "Inter", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            --f-body: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: var(--f-body); font-size: 15px; line-height: 1.5; color: var(--ink);
            background: var(--bg); -webkit-font-smoothing: antialiased;
        }
        a { color: var(--link); text-decoration: none; font-weight: 500; }
        a:hover { color: var(--cyan-hover); text-decoration: underline; }
        :focus-visible { outline: 2px solid var(--cyan); outline-offset: 2px; }

        .auth { display: grid; grid-template-columns: minmax(0, 1fr); min-height: 100vh; }
        @media (min-width: 960px) { .auth { grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr); } }

        /* Brand panel (desktop only) */
        .aside {
            display: none; position: relative; overflow: hidden; padding: 40px 56px;
            background:
                radial-gradient(60% 50% at 30% 30%, rgba(139,124,255,.16), transparent 70%),
                radial-gradient(55% 45% at 70% 80%, rgba(34,199,214,.14), transparent 70%),
                #050A14;
            border-right: 1px solid var(--line);
        }
        @media (min-width: 960px) { .aside { display: flex; flex-direction: column; } }
        .aside::before {
            content: ""; position: absolute; inset: 0; pointer-events: none; opacity: .5;
            background-image: linear-gradient(rgba(255,255,255,.035) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.035) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: radial-gradient(70% 60% at 50% 45%, #000 30%, transparent 85%);
            -webkit-mask-image: radial-gradient(70% 60% at 50% 45%, #000 30%, transparent 85%);
        }
        .aside > * { position: relative; }
        .aside .logo img { height: 28px; width: auto; display: block; }
        .aside-body { margin: auto 0; max-width: 520px; padding: 32px 0; }
        .eyebrow { display: inline-flex; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--cyan); }
        .eyebrow::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: var(--cyan); box-shadow: 0 0 12px var(--cyan); }
        .aside h1 { font-family: var(--f-display); font-size: clamp(30px, 2.6vw, 40px); line-height: 1.12; letter-spacing: -.02em; color: var(--heading); margin: 14px 0 14px; }
        .aside .lead { color: var(--ink-2); font-size: 16px; margin: 0 0 24px; max-width: 460px; }
        .points { list-style: none; padding: 0; margin: 0 0 8px; display: grid; gap: 12px; }
        .points li { display: flex; gap: 12px; align-items: flex-start; color: var(--ink); }
        .points svg { flex: none; margin-top: 2px; color: var(--cyan); }
        .art { width: 100%; max-width: 480px; height: auto; display: block; margin: 8px 0 0 -12px;
            mask-image: radial-gradient(closest-side, #000 62%, transparent 100%); -webkit-mask-image: radial-gradient(closest-side, #000 62%, transparent 100%); }
        .couriers { font-size: 13px; color: var(--ink-3); }
        .couriers strong { color: var(--ink-2); font-weight: 500; }

        /* Form side */
        .main { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 32px 16px;
            background: radial-gradient(50% 40% at 50% 0%, rgba(34,199,214,.07), transparent 70%), var(--bg); }
        .mobile-logo { margin-bottom: 24px; }
        .mobile-logo img { height: 26px; width: auto; display: block; }
        @media (min-width: 960px) { .mobile-logo { display: none; } }
        .card { width: 100%; max-width: 440px; background: var(--surface); border: 1px solid var(--line); border-radius: var(--r-lg);
            padding: 32px 28px; box-shadow: 0 40px 80px -24px rgba(0,0,0,.85), 0 0 60px -24px rgba(34,199,214,.2); }
        .card.wide { max-width: 500px; }
        @media (max-width: 480px) { .card { padding: 26px 18px; } }
        .card h2 { font-family: var(--f-display); font-size: 26px; line-height: 1.2; letter-spacing: -.015em; color: var(--heading); margin: 0 0 6px; }
        .card .sub { color: var(--ink-2); margin: 0 0 22px; }
        .card .sub strong { color: var(--heading); font-weight: 500; word-break: break-all; }
        .foot { margin-top: 22px; text-align: center; color: var(--ink-2); font-size: 14px; }
        .legal { margin-top: 20px; font-size: 12.5px; color: var(--ink-3); text-align: center; }

        /* Fields */
        .field { margin-bottom: 16px; }
        .field-row { display: grid; grid-template-columns: 1fr; gap: 0 14px; }
        @media (min-width: 520px) { .field-row.two { grid-template-columns: 1fr 1fr; } }
        .label-row { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
        label, .label { display: block; font-size: 13px; font-weight: 500; color: var(--ink); margin-bottom: 7px; }
        .label-row a { font-size: 13px; }
        .control { position: relative; display: flex; align-items: center; }
        .input {
            width: 100%; height: 46px; padding: 0 14px; font: inherit; font-size: 15px; color: var(--heading);
            background: var(--field); border: 1px solid var(--line); border-radius: var(--r-md);
            transition: border-color .15s, box-shadow .15s, background .15s; -webkit-appearance: none; appearance: none;
        }
        .input::placeholder { color: #5E6E8C; }
        .input:hover { border-color: var(--line-2); }
        .input:focus { outline: none; border-color: var(--cyan); box-shadow: 0 0 0 3px var(--cyan-50); background: #0C1528; }
        .input.is-invalid { border-color: var(--danger); box-shadow: 0 0 0 3px var(--danger-50); }
        .input:-webkit-autofill { -webkit-text-fill-color: var(--heading); -webkit-box-shadow: 0 0 0 40px #0C1528 inset; caret-color: var(--heading); }
        .has-toggle .input { padding-right: 48px; }
        .has-prefix .input { padding-left: 58px; }
        .prefix { position: absolute; left: 1px; top: 1px; bottom: 1px; width: 46px; display: grid; place-items: center; font-size: 14px; color: var(--ink-2);
            border-right: 1px solid var(--line); border-radius: var(--r-md) 0 0 var(--r-md); pointer-events: none; }
        .toggle { position: absolute; right: 4px; width: 40px; height: 38px; border: 0; border-radius: 9px; background: transparent; color: var(--ink-3); cursor: pointer; display: grid; place-items: center; }
        .toggle:hover { color: var(--heading); background: rgba(255,255,255,.04); }
        .error { color: var(--danger); font-size: 13px; margin-top: 6px; min-height: 0; }
        .error:empty { display: none; }
        .hint { color: var(--ink-3); font-size: 12.5px; margin-top: 6px; }

        /* Segmented choice */
        .segmented { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .segmented input { position: absolute; opacity: 0; pointer-events: none; }
        .segmented label { margin: 0; display: flex; align-items: center; gap: 10px; padding: 11px 12px; border: 1px solid var(--line); border-radius: var(--r-md); background: var(--field);
            cursor: pointer; color: var(--ink); font-size: 14px; font-weight: 500; transition: border-color .15s, background .15s; }
        .segmented label:hover { border-color: var(--line-2); }
        .segmented svg { color: var(--ink-3); flex: none; }
        .segmented input:checked + label { border-color: var(--cyan); background: var(--cyan-50); color: var(--heading); }
        .segmented input:checked + label svg { color: var(--cyan); }
        .segmented input:focus-visible + label { outline: 2px solid var(--cyan); outline-offset: 2px; }
        .segmented.is-invalid label { border-color: var(--danger); }

        /* Strength meter */
        .strength { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; margin-top: 8px; }
        .strength i { height: 4px; border-radius: 4px; background: var(--line); transition: background .2s; }
        .strength[data-score="1"] i:nth-child(-n+1) { background: var(--danger); }
        .strength[data-score="2"] i:nth-child(-n+2) { background: var(--warn); }
        .strength[data-score="3"] i:nth-child(-n+3) { background: var(--cyan); }
        .strength[data-score="4"] i { background: var(--success); }

        /* Buttons */
        .btn { width: 100%; height: 48px; border: 0; border-radius: var(--r-md); font: inherit; font-size: 15px; font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; gap: 10px; transition: background .15s, transform .1s, box-shadow .15s; }
        .btn-primary { background: var(--cyan); color: #03151A; box-shadow: 0 8px 24px -10px rgba(34,199,214,.7); }
        .btn-primary:hover { background: var(--cyan-hover); }
        .btn-primary:active { transform: translateY(1px); }
        .btn[disabled] { opacity: .65; cursor: progress; }
        .btn .spin { width: 18px; height: 18px; border-radius: 50%; border: 2px solid rgba(3,21,26,.25); border-top-color: #03151A; animation: spin .7s linear infinite; display: none; }
        .btn.is-loading .spin { display: inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .btn.is-done { background: var(--success); }

        /* Alerts */
        .alert { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: var(--r-md); font-size: 14px; margin-bottom: 18px; border: 1px solid transparent; }
        .alert svg { flex: none; margin-top: 1px; }
        .alert-success { background: var(--success-50); color: #A7F3D0; border-color: rgba(52,211,153,.25); }
        .alert-danger { background: var(--danger-50); color: #FECACA; border-color: rgba(248,113,113,.25); }
        .alert-info { background: var(--cyan-50); color: #CFFAFE; border-color: rgba(34,199,214,.25); }
        #formMessages:empty { display: none; }

        .divider { display: flex; align-items: center; gap: 12px; color: var(--ink-3); font-size: 13px; margin: 20px 0 0; }
        .divider::before, .divider::after { content: ""; flex: 1; height: 1px; background: var(--line); }
        .icon-badge { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; background: var(--cyan-50); color: var(--cyan); margin-bottom: 18px; border: 1px solid rgba(34,199,214,.25); }
        .copyright { margin-top: 28px; font-size: 12.5px; color: var(--ink-3); text-align: center; }

        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { transition: none !important; animation: none !important; } }
    </style>
    @stack('styles')
</head>
<body>
<div class="auth">
    <aside class="aside" aria-hidden="true">
        <a class="logo" href="{{ url('/') }}" tabindex="-1"><img src="{{ asset('site/assets/img/logo-white.png') }}" alt="Shipxpeed"></a>
        <div class="aside-body">
            <span class="eyebrow">@yield('aside_eyebrow', 'Seller panel')</span>
            <h1>@yield('aside_title', 'Ship every order with the right courier.')</h1>
            <p class="lead">@yield('aside_lead', 'Compare rates, book pickups, print labels and track every parcel across India from one dashboard.')</p>
            <ul class="points">
                <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>Rates from top couriers side by side, pick the best in one click</li>
                <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>Bulk orders, labels and tracking in one place</li>
                <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>COD remittance and NDR follow-ups handled for you</li>
            </ul>
            <img class="art" src="{{ asset('site/assets/img/art/parcels.webp') }}" alt="" loading="lazy">
        </div>
        <p class="couriers">Ship with <strong>Delhivery · Ekart · Amazon · Xpressbees · Blue Dart</strong></p>
    </aside>

    <main class="main">
        <a class="mobile-logo" href="{{ url('/') }}"><img src="{{ asset('site/assets/img/logo-white.png') }}" alt="Shipxpeed"></a>
        @yield('content')
        <p class="copyright">© {{ date('Y') }} Shipxpeed. All rights reserved.</p>
    </main>
</div>

<script>
// Shared helpers for the seller auth pages
window.SX = {
    eye: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>',
    eyeOff: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.9 4.24A9 9 0 0 1 12 4c6.5 0 10 7 10 7a17 17 0 0 1-2.16 3.19M6.6 6.6A17 17 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="m2 2 20 20"/></svg>',
    escape(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; },
    alert(type, msg) {
        const icon = type === 'success'
            ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/></svg>'
            : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg>';
        return '<div class="alert alert-' + type + '" role="' + (type === 'danger' ? 'alert' : 'status') + '">' + icon + '<div>' + msg + '</div></div>';
    },
    loading(btn, on, label) {
        btn.disabled = on; btn.classList.toggle('is-loading', on);
        const t = btn.querySelector('.btn-text'); if (t && label) t.textContent = label;
    },
    strength(v) {
        if (!v) return 0;
        let s = 0;
        if (v.length >= 6) s++;
        if (v.length >= 10) s++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
        if (/\d/.test(v) && /[^A-Za-z0-9]/.test(v)) s++;
        return Math.max(1, Math.min(4, s));
    }
};
// Show / hide password buttons
document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    const input = document.getElementById(btn.getAttribute('data-toggle-password'));
    btn.innerHTML = SX.eye;
    btn.addEventListener('click', function () {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.innerHTML = show ? SX.eyeOff : SX.eye;
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        input.focus();
    });
});
// Strength meters
document.querySelectorAll('[data-strength-for]').forEach(function (meter) {
    const input = document.getElementById(meter.getAttribute('data-strength-for'));
    input.addEventListener('input', function () { meter.setAttribute('data-score', SX.strength(input.value)); });
});
// Clear a field's error as soon as the user edits it
document.addEventListener('input', function (e) {
    const el = e.target; if (!el.name) return;
    el.classList.remove('is-invalid');
    const seg = el.closest('.segmented'); if (seg) seg.classList.remove('is-invalid');
    const err = document.querySelector('.error-' + el.name.replace(/[^\w-]/g, ''));
    if (err) err.textContent = '';
});
</script>
@stack('scripts')
</body>
</html>
