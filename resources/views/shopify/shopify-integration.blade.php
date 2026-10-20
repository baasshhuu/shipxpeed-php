@extends('layouts.sellerdash')
@section('content')
<style>
    .shx { --bg:#121826; --card:#1a2233; --field:#232c40; --line:#2d3850; --text:#e8ecf4; --muted:#9aa5bb; --accent:#5b6cff; --accent2:#4a59e6; --ok:#2fbf71; --warn:#f2b84b; --err:#ff6b6b; }
    .shx .wrap { background:var(--bg); border-radius:16px; padding:28px; color:var(--text); }
    .shx h2 { color:var(--text); font-size:1.35rem; font-weight:700; margin:0 0 4px; }
    .shx .sub { color:var(--muted); font-size:.9rem; margin-bottom:22px; max-width:720px; }
    .shx .wrap > .d-flex { margin-bottom:6px; }
    .shx a.btng { text-decoration:none; display:inline-block; }
    .shx .grid { display:grid; grid-template-columns:minmax(0,1.05fr) minmax(0,1fr); gap:22px; }
    @media (max-width: 992px){ .shx .grid { grid-template-columns:1fr; } }
    .shx .card2 { background:var(--card); border:1px solid var(--line); border-radius:14px; padding:22px; }
    .shx label { display:block; font-size:.82rem; color:var(--muted); margin:0 0 6px; }
    .shx .in { width:100%; box-sizing:border-box; background:var(--field); color:var(--text); border:1px solid var(--line); border-radius:9px; padding:10px 12px; font-size:.92rem; }
    .shx .in:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(91,108,255,.25); }
    .shx .in::placeholder { color:#6f7a92; }
    .shx .hint { font-size:.75rem; color:#7d88a0; margin-top:5px; }
    .shx .fg { margin-bottom:16px; }
    .shx .btnp { background:var(--accent); color:#fff; border:0; border-radius:9px; padding:11px 18px; font-weight:600; font-size:.92rem; }
    .shx .btnp:hover { background:var(--accent2); }
    .shx .btng { background:transparent; color:var(--muted); border:1px solid var(--line); border-radius:9px; padding:7px 12px; font-size:.8rem; }
    .shx .btng:hover { color:var(--text); border-color:var(--muted); }
    .shx .code { display:flex; align-items:center; gap:8px; background:#0e1320; border:1px solid var(--line); border-radius:9px; padding:9px 10px; font-family:ui-monospace,Menlo,Consolas,monospace; font-size:.8rem; color:#c9d3ff; word-break:break-all; }
    .shx .code span { flex:1; }
    .shx ol { padding-left:18px; margin:0; color:var(--text); font-size:.88rem; }
    .shx ol li { margin-bottom:10px; line-height:1.5; }
    .shx ol li b { color:#fff; }
    .shx .muted { color:var(--muted); }
    .shx .tabs { display:flex; gap:6px; margin-bottom:18px; flex-wrap:wrap; }
    .shx .tab { background:var(--field); border:1px solid var(--line); color:var(--muted); border-radius:20px; padding:6px 13px; font-size:.78rem; cursor:pointer; }
    .shx .tab.on { background:rgba(91,108,255,.18); border-color:var(--accent); color:#fff; }
    .shx .alert-x { border-radius:10px; padding:11px 14px; font-size:.88rem; margin-bottom:16px; }
    .shx .alert-ok { background:rgba(47,191,113,.12); border:1px solid rgba(47,191,113,.4); color:#9be8bf; }
    .shx .alert-er { background:rgba(255,107,107,.1); border:1px solid rgba(255,107,107,.4); color:#ffb3b3; }
    .shx .chk { display:flex; gap:8px; align-items:center; font-size:.85rem; color:var(--text); }
    .shx .sec-title { font-size:.95rem; font-weight:700; color:#fff; margin:0 0 12px; }
    .shx .note { font-size:.8rem; color:var(--warn); background:rgba(242,184,75,.08); border:1px solid rgba(242,184,75,.3); border-radius:9px; padding:9px 11px; margin-top:12px; }
</style>

<div class="pc-container shx">
  <div class="pc-content">
    <div class="wrap">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
          <h2>Connect a Shopify store</h2>
          <div class="sub">Enter your store's own app credentials (Client ID + Secret). You'll be sent to Shopify to approve, then brought back — no app review needed.</div>
        </div>
        <a href="{{ route('seller.channel.list') }}" class="btng">← Back to channels</a>
      </div>

      @if(session('success'))<div class="alert-x alert-ok">{{ session('success') }}</div>@endif
      @if(session('error'))<div class="alert-x alert-er">{{ session('error') }}</div>@endif
      @if($errors->any())<div class="alert-x alert-er">{{ $errors->first() }}</div>@endif

      <div class="grid">
        {{-- ---------------- Form ---------------- --}}
        <div class="card2">
          <div class="tabs" role="tablist">
            <span class="tab on" data-m="oauth">Connect &amp; authorize (recommended)</span>
            <span class="tab" data-m="client_credentials">Client ID + Secret only</span>
            <span class="tab" data-m="legacy_token">Old access token (shpat_)</span>
          </div>

          <form method="POST" action="{{ route('shopify.connect') }}" id="shxForm">
            @csrf
            <input type="hidden" name="auth_method" id="auth_method" value="{{ old('auth_method', 'oauth') }}">

            <div class="fg">
              <label for="store_domain">Store domain</label>
              <input class="in" id="store_domain" name="store_domain" placeholder="your-store.myshopify.com"
                     value="{{ old('store_domain', $reconnect->shopify_shop_domain ?? '') }}" required>
              <div class="hint">Example: pusti-feni-2.myshopify.com — Shopify admin › Settings › Domains. Your own website domain will not work.</div>
            </div>

            <div class="fg m-cred">
              <label for="client_id">Client ID (API key)</label>
              <input class="in" id="client_id" name="client_id" placeholder="e.g. 7211243..." value="{{ old('client_id', $reconnect->client_id ?? '') }}" autocomplete="off">
            </div>

            <div class="fg m-cred">
              <label for="client_secret">Client secret (API secret key)</label>
              <input class="in" id="client_secret" name="client_secret" type="password" placeholder="e.g. shpss_... or 32-char secret" autocomplete="new-password">
            </div>

            <div class="fg m-legacy" style="display:none">
              <label for="access_token">Admin API access token</label>
              <input class="in" id="access_token" name="access_token" type="password" placeholder="shpat_..." autocomplete="new-password">
              <div class="hint">Only for stores that already had a custom app from Settings › Apps › Develop apps (created before 1 Jan 2026). Status sync works; instant order webhooks don't — orders sync every 5 minutes.</div>
            </div>

            <div class="fg">
              <label for="sync_start_date">Import orders created from</label>
              <input class="in" id="sync_start_date" name="sync_start_date" type="date" max="{{ now()->toDateString() }}"
                     value="{{ old('sync_start_date', now()->subDays(7)->toDateString()) }}">
              <div class="hint">Only unfulfilled, not-cancelled orders are imported.</div>
            </div>

            <div class="fg">
              <label class="chk"><input type="checkbox" name="notify_customer" value="1" checked> Send Shopify's shipping-confirmation email/SMS to the buyer with the AWB</label>
            </div>

            <button type="submit" class="btnp" id="shxSubmit">Connect &amp; authorize on Shopify →</button>
          </form>
        </div>

        {{-- ---------------- Steps ---------------- --}}
        <div class="card2">
          <p class="sec-title">Redirect URL — paste this in your Shopify app</p>
          <div class="code"><span id="ru">{{ $redirectUri }}</span><button type="button" class="btng" data-copy="ru">Copy</button></div>
          <div class="hint" style="margin-bottom:12px">Same URL for every seller. It must match character-for-character under <b>Allowed redirection URL(s)</b>.</div>

          <label>App URL</label>
          <div class="code" style="margin-bottom:16px"><span id="au">{{ $appUrl }}</span><button type="button" class="btng" data-copy="au">Copy</button></div>

          <p class="sec-title">Steps in Shopify</p>
          <ol>
            <li>Log in to Shopify and open <b>Dev Dashboard</b> (dev.shopify.com/dashboard).</li>
            <li><b>Create an app</b> — name it e.g. <i>Shipxpeed</i>.</li>
            <li>Set <b>App URL</b> and <b>Allowed redirection URL(s)</b> to the URLs above.</li>
            <li>Enable these <b>Admin API scopes</b>:
              <div class="code" style="margin-top:6px"><span id="sc">{{ str_replace(',', ', ', $scopes) }}</span><button type="button" class="btng" data-copy="sc">Copy</button></div>
            </li>
            <li>Under <b>Protected customer data</b>, allow <b>Name, Email, Phone and Address</b> — otherwise orders arrive without the buyer's address.</li>
            <li>Release the version, then under <b>Distribution</b> choose <b>Custom distribution</b> and select your own store (no review needed).</li>
            <li>Open the app's <b>Settings</b>, copy the <b>Client ID</b> and <b>Client secret</b> into the form here.</li>
            <li>Stay logged in to Shopify in this browser and click <b>Connect &amp; authorize</b> → <b>Install / Approve</b> on Shopify. Orders start syncing straight away.</li>
          </ol>
          <div class="note">After connecting: new orders arrive instantly (and every 5 minutes as a backup). When you generate an AWB here, the Shopify order is marked Fulfilled with the courier and tracking link; pickup, in-transit, out-for-delivery, delivered and RTO updates follow automatically.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var tabs = document.querySelectorAll('.shx .tab');
  var m = document.getElementById('auth_method');
  var btn = document.getElementById('shxSubmit');
  function show(mode) {
    m.value = mode;
    tabs.forEach(function (t) { t.classList.toggle('on', t.dataset.m === mode); });
    document.querySelectorAll('.shx .m-cred').forEach(function (e) { e.style.display = mode === 'legacy_token' ? 'none' : ''; });
    document.querySelectorAll('.shx .m-legacy').forEach(function (e) { e.style.display = mode === 'legacy_token' ? '' : 'none'; });
    document.getElementById('client_id').required = mode !== 'legacy_token';
    document.getElementById('client_secret').required = mode !== 'legacy_token';
    document.getElementById('access_token').required = mode === 'legacy_token';
    btn.innerHTML = mode === 'oauth' ? 'Connect &amp; authorize on Shopify →' : 'Connect store';
  }
  tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.dataset.m); }); });
  show(m.value || 'oauth');

  document.querySelectorAll('.shx [data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
      var txt = document.getElementById(b.dataset.copy).innerText.replace(/, /g, ',');
      if (b.dataset.copy !== 'sc') txt = txt.trim();
      navigator.clipboard && navigator.clipboard.writeText(txt);
      var o = b.innerText; b.innerText = 'Copied'; setTimeout(function () { b.innerText = o; }, 1200);
    });
  });

  document.getElementById('shxForm').addEventListener('submit', function () {
    btn.disabled = true; btn.innerText = 'Connecting…';
  });
})();
</script>
@endsection
