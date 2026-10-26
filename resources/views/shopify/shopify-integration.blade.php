@extends('layouts.sellerdash')
@section('content')
<style>
    .shx { --bg:#121826; --card:#1a2233; --field:#232c40; --line:#2d3850; --text:#e8ecf4; --muted:#9aa5bb; --accent:#5b6cff; --accent2:#4a59e6; --ok:#2fbf71; --warn:#f2b84b; --err:#ff6b6b; --shop:#95bf47; }
    .shx .wrap { background:var(--bg); border-radius:16px; padding:28px; color:var(--text); }
    .shx h2 { color:#fff; font-size:1.4rem; font-weight:700; margin:0 0 4px; }
    .shx h3 { color:#fff; font-size:1rem; font-weight:700; margin:0; }
    .shx .sub { color:var(--muted); font-size:.92rem; max-width:760px; margin:0; }
    .shx a.btng, .shx a.lnk { text-decoration:none; }
    .shx .lnk { color:#9fb0ff; } .shx .lnk:hover { color:#fff; text-decoration:underline; }
    .shx .top { display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start; margin-bottom:20px; }
    .shx .grid { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(0,1fr); gap:22px; align-items:start; }
    @media (max-width: 576px){ .shx .wrap { padding:16px; } .shx .card2 { padding:16px; } .shx .step { grid-template-columns:28px minmax(0,1fr); gap:10px; } .shx .num { width:28px; height:28px; } }
    @media (max-width: 1100px){ .shx .grid { grid-template-columns:1fr; } .shx .sticky { position:static !important; } }
    .shx .card2 { background:var(--card); border:1px solid var(--line); border-radius:14px; padding:22px; }
    .shx .sticky { position:sticky; top:84px; }
    .shx label { display:block; font-size:.82rem; color:var(--muted); margin:0 0 6px; }
    .shx .in { width:100%; box-sizing:border-box; background:var(--field); color:var(--text); border:1px solid var(--line); border-radius:9px; padding:10px 12px; font-size:.92rem; }
    .shx .in:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(91,108,255,.25); }
    .shx .in::placeholder { color:#6f7a92; }
    .shx .hint { font-size:.76rem; color:#8a95ad; margin-top:5px; line-height:1.45; }
    .shx .fg { margin-bottom:16px; }
    .shx .btnp { background:var(--accent); color:#fff; border:0; border-radius:9px; padding:12px 18px; font-weight:600; font-size:.95rem; width:100%; }
    .shx .btnp:hover { background:var(--accent2); }
    .shx .btnp:disabled { opacity:.6; }
    .shx .btng { background:transparent; color:var(--muted); border:1px solid var(--line); border-radius:8px; padding:6px 11px; font-size:.78rem; white-space:nowrap; }
    .shx .btng:hover { color:#fff; border-color:var(--muted); }
    .shx .code { display:flex; align-items:center; gap:8px; background:#0e1320; border:1px solid var(--line); border-radius:9px; padding:8px 8px 8px 11px; font-family:ui-monospace,Menlo,Consolas,monospace; font-size:.8rem; color:#c9d3ff; word-break:break-all; margin:8px 0 4px; }
    .shx .code span { flex:1; }
    .shx .alert-x { border-radius:10px; padding:11px 14px; font-size:.88rem; margin-bottom:16px; }
    .shx .alert-ok { background:rgba(47,191,113,.12); border:1px solid rgba(47,191,113,.4); color:#9be8bf; }
    .shx .alert-er { background:rgba(255,107,107,.1); border:1px solid rgba(255,107,107,.4); color:#ffb3b3; }
    .shx .chk { display:flex; gap:8px; align-items:flex-start; font-size:.84rem; color:var(--text); }
    .shx .chk input { margin-top:3px; }

    /* progress strip */
    .shx .prog { display:flex; gap:6px; margin-bottom:20px; flex-wrap:wrap; }
    .shx .prog a { flex:1 1 140px; display:flex; gap:8px; align-items:center; background:var(--card); border:1px solid var(--line); border-radius:10px; padding:9px 11px; color:var(--muted); font-size:.8rem; text-decoration:none; }
    .shx .prog a:hover { border-color:var(--accent); color:#fff; }
    .shx .prog b { width:22px; height:22px; flex:0 0 22px; border-radius:50%; background:var(--field); color:#fff; display:grid; place-items:center; font-size:.75rem; }
    .shx .prog a.done b { background:var(--ok); }

    /* steps */
    .shx .step { display:grid; grid-template-columns:34px minmax(0,1fr); gap:14px; padding:18px 0; border-top:1px solid var(--line); }
    .shx .step:first-of-type { border-top:0; padding-top:4px; }
    .shx .num { width:32px; height:32px; border-radius:50%; background:rgba(91,108,255,.18); border:1px solid var(--accent); color:#fff; display:grid; place-items:center; font-weight:700; font-size:.9rem; }
    .shx .step.done .num { background:var(--ok); border-color:var(--ok); }
    .shx .step p, .shx .step li { font-size:.88rem; line-height:1.6; color:var(--text); margin:0 0 6px; }
    .shx .step ul { padding-left:18px; margin:6px 0 0; }
    .shx .ui { display:inline-block; background:#2a3550; border:1px solid #3a4766; color:#fff; border-radius:6px; padding:0 7px; font-size:.8rem; font-weight:600; white-space:nowrap; }
    .shx .path { color:#c9d3ff; font-weight:600; }
    .shx .why { font-size:.78rem; color:#8a95ad; margin-top:6px; }
    .shx .warnbox { font-size:.8rem; color:#ffd88a; background:rgba(242,184,75,.08); border:1px solid rgba(242,184,75,.3); border-radius:9px; padding:9px 11px; margin-top:10px; line-height:1.5; }
    .shx .okbox { font-size:.82rem; color:#b7f0d1; background:rgba(47,191,113,.08); border:1px solid rgba(47,191,113,.3); border-radius:9px; padding:10px 12px; line-height:1.55; }
    .shx .step-tick { margin-top:10px; }
    .shx .scopes { display:flex; flex-wrap:wrap; gap:6px; margin:8px 0 4px; }
    .shx .scopes span { font-family:ui-monospace,Menlo,Consolas,monospace; font-size:.74rem; background:#0e1320; border:1px solid var(--line); color:#c9d3ff; border-radius:6px; padding:3px 7px; max-width:100%; overflow-wrap:anywhere; }

    /* faq */
    .shx details { border-top:1px solid var(--line); padding:12px 0; }
    .shx details:first-of-type { border-top:0; }
    .shx summary { cursor:pointer; color:#fff; font-size:.88rem; font-weight:600; list-style:none; display:flex; justify-content:space-between; gap:10px; }
    .shx summary::-webkit-details-marker { display:none; }
    .shx summary::after { content:'+'; color:var(--muted); font-weight:400; }
    .shx details[open] summary::after { content:'–'; }
    .shx details p { font-size:.84rem; color:var(--muted); margin:8px 0 0; line-height:1.55; }

    .shx .alt { font-size:.8rem; color:var(--muted); margin-top:14px; text-align:center; }
    .shx .alt a { cursor:pointer; }
    .shx .mode-note { font-size:.8rem; color:#ffd88a; margin-bottom:12px; display:none; }
</style>

<div class="pc-container shx">
  <div class="pc-content">
    <div class="wrap">

      <div class="top">
        <div>
          <h2>Connect your Shopify store</h2>
          <p class="sub">Takes about 5 minutes, one time only. You create a small private app in your own Shopify account, paste its Client ID and Secret here, and approve. After that, orders come to Shipxpeed on their own, and every AWB and delivery status goes back to Shopify.</p>
        </div>
        <a href="{{ route('seller.channel.list') }}" class="btng">← Back to channels</a>
      </div>

      @if(session('success'))<div class="alert-x alert-ok">{{ session('success') }}</div>@endif
      @if(session('error'))<div class="alert-x alert-er">{{ session('error') }}</div>@endif
      @if($errors->any())<div class="alert-x alert-er">{{ $errors->first() }}</div>@endif

      <nav class="prog" aria-label="Setup steps">
        <a href="#s1" data-s="1"><b>1</b>Open Dev Dashboard</a>
        <a href="#s2" data-s="2"><b>2</b>Create the app</a>
        <a href="#s3" data-s="3"><b>3</b>URLs &amp; permissions</a>
        <a href="#s4" data-s="4"><b>4</b>Release &amp; install</a>
        <a href="#s5" data-s="5"><b>5</b>Copy keys &amp; connect</a>
      </nav>

      <div class="grid">
        {{-- ======================= STEP-BY-STEP GUIDE ======================= --}}
        <div class="card2">

          <div class="step" id="s1">
            <div class="num">1</div>
            <div>
              <h3>Open the Shopify Dev Dashboard</h3>
              <p>Log in to your Shopify admin <b>in this same browser</b> (you need to be the store owner, or a staff member with app development permission).</p>
              <p>Go to <span class="path">Settings → Apps → Develop apps</span>, then click <span class="ui">Build apps in Dev Dashboard</span>.</p>
              <p class="why">Shortcut: open <a class="lnk" href="https://dev.shopify.com/dashboard" target="_blank" rel="noopener">dev.shopify.com/dashboard</a>. If Shopify asks you to "Allow custom app development", click Allow.</p>
              <label class="chk step-tick"><input type="checkbox" data-done="1"> Done — I'm in the Dev Dashboard</label>
            </div>
          </div>

          <div class="step" id="s2">
            <div class="num">2</div>
            <div>
              <h3>Create the app</h3>
              <p>Click <span class="ui">Create app</span>. In <b>App name</b> type <b>Shipxpeed</b> (any name works) and click <span class="ui">Create</span>.</p>
              <p>Shopify now opens the app's <b>version / configuration</b> page. Keep it open for the next step.</p>
              <label class="chk step-tick"><input type="checkbox" data-done="2"> Done — app created</label>
            </div>
          </div>

          <div class="step" id="s3">
            <div class="num">3</div>
            <div>
              <h3>Fill in the URLs and permissions</h3>

              <p><b>a) App URL</b> — paste:</p>
              <div class="code"><span id="cp-app">{{ $appUrl }}</span><button type="button" class="btng" data-copy="cp-app">Copy</button></div>

              <p style="margin-top:12px"><b>b) Redirect URLs</b> (sometimes called "Allowed redirection URL(s)") — paste <b>exactly</b>:</p>
              <div class="code"><span id="cp-ru">{{ $redirectUri }}</span><button type="button" class="btng" data-copy="cp-ru">Copy</button></div>
              <p class="why">This is where Shopify brings you back after you approve. It must match character-for-character — no extra space or slash — or Shopify shows "redirect_uri is not whitelisted".</p>

              <p style="margin-top:12px"><b>c) Scopes</b> — click <span class="ui">Select scopes</span> and tick each of these (use the search box), or paste the whole list if Shopify shows a text box:</p>
              <div class="scopes">
                @foreach(explode(',', $scopes) as $sc)<span>{{ trim($sc) }}</span>@endforeach
              </div>
              <div class="code"><span id="cp-sc">{{ $scopes }}</span><button type="button" class="btng" data-copy="cp-sc">Copy all</button></div>
              <p class="why">These let Shipxpeed read your orders and mark them Fulfilled with the AWB and tracking link. Shipxpeed cannot change prices, products or payouts.</p>

              <p style="margin-top:12px"><b>d) Webhooks API version</b> — pick the newest one in the list. Leave everything else as it is.</p>

              <div class="warnbox"><b>Customer details:</b> if your app shows <span class="path">API access → Protected customer data</span>, open it and allow <b>Name, Email, Phone and Address</b>. Without this, orders reach Shipxpeed with an empty address and can't be shipped.</div>
              <label class="chk step-tick"><input type="checkbox" data-done="3"> Done — URLs and scopes filled</label>
            </div>
          </div>

          <div class="step" id="s4">
            <div class="num">4</div>
            <div>
              <h3>Release it and choose your store</h3>
              <ul>
                <li>Click <span class="ui">Release</span> at the bottom of the version page.</li>
                <li>Open the app's <b>Home</b> page (left menu). Under <b>Distribution</b> click <span class="ui">Select distribution method</span> → <span class="ui">Custom distribution</span>.</li>
                <li>Enter your store's <b>myshopify.com</b> domain and click <span class="ui">Generate link</span>. No Shopify review is needed for your own store.</li>
              </ul>
              <p class="why">You don't have to open the generated install link. The <b>Connect &amp; authorize</b> button on this page installs the app for you in step 5.</p>
              <label class="chk step-tick"><input type="checkbox" data-done="4"> Done — released, custom distribution set</label>
            </div>
          </div>

          <div class="step" id="s5">
            <div class="num">5</div>
            <div>
              <h3>Copy the keys here and connect</h3>
              <ul>
                <li>In the Dev Dashboard open your app → <span class="ui">Settings</span>. Copy the <b>Client ID</b> and <b>Secret</b> into the form on this page.</li>
                <li>Enter your store domain — <b>your-store.myshopify.com</b>. Find it in Shopify under <span class="path">Settings → Domains</span>, or just paste the address of your Shopify admin page (admin.shopify.com/store/…).</li>
                <li>Click <span class="ui">Connect &amp; authorize on Shopify</span>. Shopify shows the permissions — click <span class="ui">Install</span> / <span class="ui">Approve</span>.</li>
              </ul>
              <div class="okbox" style="margin-top:10px"><b>That's it.</b> You land back on the Channels page with your store marked <b>Connected</b>, and your unfulfilled orders are already imported. From then on:
                <ul style="margin-top:6px">
                  <li>New Shopify orders appear in <b>Orders</b> within seconds (with a 5-minute backup check).</li>
                  <li>When you generate an AWB, the Shopify order becomes <b>Fulfilled</b> with the courier, AWB and tracking link, and the buyer gets Shopify's shipping email (you can turn this off).</li>
                  <li>Picked up, in transit, out for delivery, delivered and RTO updates show on the Shopify order timeline. Delivered COD orders are marked <b>Paid</b>.</li>
                  <li>If you cancel the shipment here, the Shopify order goes back to Unfulfilled.</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        {{-- ======================= FORM (STEP 5) ======================= --}}
        <div class="sticky">
          <div class="card2">
            <h3 style="margin-bottom:4px">Step 5 · Connect a Shopify store</h3>
            <p class="hint" style="margin:0 0 16px">Enter the store's own app credentials (Client ID + Secret). You'll be sent to Shopify to approve, then brought back — no app review needed.</p>

            <div class="mode-note" id="modeNote"></div>

            <form method="POST" action="{{ route('shopify.connect') }}" id="shxForm" autocomplete="off">
              @csrf
              <input type="hidden" name="auth_method" id="auth_method" value="{{ old('auth_method', 'oauth') }}">

              <div class="fg">
                <label for="store_domain">Store domain</label>
                <input class="in" id="store_domain" name="store_domain" placeholder="your-store.myshopify.com"
                       value="{{ old('store_domain', $reconnect->shopify_shop_domain ?? '') }}" required>
                <div class="hint">Example: pusti-feni-2.myshopify.com. Your own website address (www.yourbrand.com) won't work.</div>
              </div>

              <div class="fg m-cred">
                <label for="client_id">Client ID (API key)</label>
                <input class="in" id="client_id" name="client_id" placeholder="e.g. 7211243..." value="{{ old('client_id', $reconnect->client_id ?? '') }}">
              </div>

              <div class="fg m-cred">
                <label for="client_secret">Client secret (API secret key)</label>
                <input class="in" id="client_secret" name="client_secret" type="password" placeholder="e.g. shpss_... or 32-char secret" autocomplete="new-password">
              </div>

              <div class="fg m-legacy" style="display:none">
                <label for="access_token">Admin API access token</label>
                <input class="in" id="access_token" name="access_token" type="password" placeholder="shpat_..." autocomplete="new-password">
                <div class="hint">Only for an old custom app made under Settings › Apps › Develop apps before 1 Jan 2026. Orders sync every 5 minutes (no instant webhooks).</div>
              </div>

              <div class="fg">
                <label for="sync_start_date">Import orders created from</label>
                <input class="in" id="sync_start_date" name="sync_start_date" type="date" max="{{ now()->toDateString() }}"
                       value="{{ old('sync_start_date', now()->subDays(7)->toDateString()) }}">
                <div class="hint">Only unfulfilled, not-cancelled orders from this date are imported.</div>
              </div>

              <div class="fg">
                <label class="chk"><input type="checkbox" name="notify_customer" value="1" checked> Let Shopify email/SMS the buyer the AWB and tracking link when I ship</label>
              </div>

              <button type="submit" class="btnp" id="shxSubmit">Connect &amp; authorize on Shopify →</button>
              <p class="hint" style="text-align:center;margin-top:8px">Stay logged in to Shopify in this browser before clicking.</p>
            </form>

            <div class="alt">
              <span id="altLinks">Other ways: <a class="lnk" data-m="client_credentials">Client ID + Secret without approve screen</a> · <a class="lnk" data-m="legacy_token">old shpat_ token</a></span>
              <a class="lnk" data-m="oauth" id="backRec" style="display:none">← Back to recommended way</a>
            </div>
          </div>

          <div class="card2" style="margin-top:16px">
            <h3 style="margin-bottom:6px">Something not working?</h3>
            <details>
              <summary>"redirect_uri is not whitelisted" / Oauth error</summary>
              <p>The Redirect URL in your Shopify app doesn't exactly match. Copy it again from step 3b, paste it in the app's version page, click <b>Release</b> again, then retry.</p>
            </details>
            <details>
              <summary>"Shopify signature check failed"</summary>
              <p>The Client secret pasted here is wrong or has a space. Copy it again from the app's <b>Settings</b> in Dev Dashboard and reconnect.</p>
            </details>
            <details>
              <summary>Shopify says the app isn't available for this store</summary>
              <p>Distribution isn't set to your store yet. Do step 4: <b>Custom distribution</b> → enter your myshopify.com domain → <b>Generate link</b>.</p>
            </details>
            <details>
              <summary>Orders came in without name / address / phone</summary>
              <p>Allow <b>Protected customer data</b> (Name, Email, Phone, Address) for the app (step 3), release, then click <b>Sync now</b> on the Channels page. Orders already imported can be edited before shipping.</p>
            </details>
            <details>
              <summary>An order isn't marked Fulfilled on Shopify</summary>
              <p>Check the <b>Orders that need attention</b> box on the Channels page. Usually the order is <b>On hold</b> in Shopify, or it was already fulfilled there by another app.</p>
            </details>
            <details>
              <summary>Can I connect more than one store?</summary>
              <p>Yes. Create one app per store (steps 1–5) and connect each one here. They all show on the Channels page.</p>
            </details>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
(function () {
  var m = document.getElementById('auth_method');
  var btn = document.getElementById('shxSubmit');
  var note = document.getElementById('modeNote');
  var notes = {
    oauth: '',
    client_credentials: 'Connects with Client ID + Secret only — no approve screen. Works only if the app was created from the same Shopify account that owns the store, and the app is already installed (open the install link from step 4).',
    legacy_token: 'Using an old Admin API access token (shpat_…).'
  };
  function show(mode) {
    m.value = mode;
    document.querySelectorAll('.shx .m-cred').forEach(function (e) { e.style.display = mode === 'legacy_token' ? 'none' : ''; });
    document.querySelectorAll('.shx .m-legacy').forEach(function (e) { e.style.display = mode === 'legacy_token' ? '' : 'none'; });
    document.getElementById('client_id').required = mode !== 'legacy_token';
    document.getElementById('client_secret').required = mode !== 'legacy_token';
    document.getElementById('access_token').required = mode === 'legacy_token';
    btn.innerHTML = mode === 'oauth' ? 'Connect &amp; authorize on Shopify →' : 'Connect store';
    note.textContent = notes[mode]; note.style.display = notes[mode] ? 'block' : 'none';
    document.getElementById('altLinks').style.display = mode === 'oauth' ? '' : 'none';
    document.getElementById('backRec').style.display = mode === 'oauth' ? 'none' : '';
  }
  document.querySelectorAll('.shx [data-m]').forEach(function (a) { a.addEventListener('click', function () { show(a.dataset.m); }); });
  show(m.value || 'oauth');

  document.querySelectorAll('.shx [data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
      var txt = document.getElementById(b.dataset.copy).innerText.trim();
      var done = function () { var o = b.innerText; b.innerText = 'Copied ✓'; setTimeout(function () { b.innerText = o; }, 1400); };
      if (navigator.clipboard) { navigator.clipboard.writeText(txt).then(done, done); }
      else { var t = document.createElement('textarea'); t.value = txt; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); } catch (e) {} t.remove(); done(); }
    });
  });

  // step ticks (remembered on this device only)
  var KEY = 'shx_steps';
  var saved = {};
  try { saved = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) {}
  function paint() {
    document.querySelectorAll('.shx [data-done]').forEach(function (c) {
      var n = c.dataset.done, on = !!saved[n];
      c.checked = on;
      document.getElementById('s' + n).classList.toggle('done', on);
      var p = document.querySelector('.shx .prog a[data-s="' + n + '"]'); if (p) p.classList.toggle('done', on);
    });
  }
  document.querySelectorAll('.shx [data-done]').forEach(function (c) {
    c.addEventListener('change', function () { saved[c.dataset.done] = c.checked; try { localStorage.setItem(KEY, JSON.stringify(saved)); } catch (e) {} paint(); });
  });
  paint();

  document.getElementById('shxForm').addEventListener('submit', function () {
    btn.disabled = true; btn.innerText = 'Opening Shopify…';
  });
})();
</script>
@endsection
