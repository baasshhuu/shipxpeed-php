@php
    /** @var \App\Services\ApiV1Docs $docs */
    $base = rtrim(url('/api'), '/');
    $endpoints = $docs->endpoints();
    $groups = collect($endpoints)->groupBy('group');
    $couriers = collect($docs->liveCouriers());
    $j = fn ($v) => json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $panelApi = \Illuminate\Support\Facades\Route::has('seller.api') ? route('seller.api') : url('/seller/api');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="dark">
<title>Shipxpeed API v1 · Developers</title>
<meta name="description" content="Shipxpeed shipping API: live courier rates from your rate card, B2C, reverse and B2B booking, labels, tracking, NDR and webhooks.">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('site/assets/img/favicon-32.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
:root{--bg:#070b16;--panel:#0d1424;--panel2:#111a2e;--line:rgba(196,206,235,.11);--text:#e9ecf5;--muted:#9aa3bd;--pri:#3b7bff;--ok:#2fbf71;--warn:#f0a63a;--bad:#ef5b5b;--mono:"JetBrains Mono",ui-monospace,monospace}
*{box-sizing:border-box}html,body{margin:0;background:var(--bg);color:var(--text);font:15px/1.6 Inter,system-ui,-apple-system,"Segoe UI",sans-serif}
a{color:#8fb2ff}code{font-family:var(--mono);font-size:.88em;background:var(--panel2);padding:1px 6px;border-radius:5px}
.top{position:sticky;top:0;z-index:20;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;background:rgba(7,11,22,.88);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
.top img{height:24px;display:block}.top nav{display:flex;gap:8px}
.btn{color:#fff;text-decoration:none;font:600 13px Inter,sans-serif;padding:8px 12px;border-radius:9px;border:1px solid rgba(196,206,235,.18);white-space:nowrap;background:none;cursor:pointer}
.btn.pri{background:var(--pri);border-color:var(--pri)}
.layout{display:grid;grid-template-columns:250px minmax(0,1fr);max-width:1320px;margin:0 auto}
aside{position:sticky;top:57px;height:calc(100vh - 57px);overflow:auto;padding:20px 12px 40px 16px;border-right:1px solid var(--line)}
aside h4{margin:16px 8px 6px;font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
aside a{display:flex;gap:8px;align-items:center;padding:5px 8px;border-radius:7px;color:var(--text);text-decoration:none;font-size:13.5px}
aside a:hover{background:var(--panel2)}aside .m{font:600 10px var(--mono);width:34px;color:var(--muted)}
main{padding:24px 28px 80px;min-width:0}
h1{font-size:32px;line-height:1.2;margin:8px 0 8px}h2{font-size:22px;margin:0 0 6px}h3{font-size:15px;margin:22px 0 8px}
.lead{color:var(--muted);font-size:16px;max-width:760px}
.card{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:18px 20px;margin:18px 0}
.sec{padding-top:26px;margin-top:26px;border-top:1px solid var(--line);scroll-margin-top:70px}
.ep{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin:8px 0 10px;font-family:var(--mono);font-size:13.5px}
.meth{font-weight:600;padding:3px 8px;border-radius:6px;background:rgba(59,123,255,.16);color:#9cbcff}.meth.GET{background:rgba(47,191,113,.15);color:#7fe0a9}
.path{word-break:break-all}.path span{color:var(--muted)}
.grid2{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:18px}.grid2>*,.code{min-width:0;max-width:100%}
table{width:100%;border-collapse:collapse;font-size:13.5px}th,td{text-align:left;padding:8px 10px;border-bottom:1px solid var(--line);vertical-align:top}
th{color:var(--muted);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.04em}
td .nm{font-family:var(--mono);font-size:12.5px;color:#d9e2ff}.req{font-size:10.5px;font-weight:600;color:var(--warn);margin-left:6px;text-transform:uppercase}
.ty{font-family:var(--mono);font-size:12px;color:var(--muted)}
.code{background:#060a14;border:1px solid var(--line);border-radius:12px;overflow:hidden;margin:10px 0}
.tabs{display:flex;gap:2px;border-bottom:1px solid var(--line);padding:6px 6px 0;overflow-x:auto}
.tab{background:none;border:0;color:var(--muted);font:600 12px Inter,sans-serif;padding:7px 10px;border-radius:7px 7px 0 0;cursor:pointer;white-space:nowrap}
.tab.on{color:#fff;background:var(--panel2)}.st{font-family:var(--mono);margin-right:6px}.st.ok{color:var(--ok)}.st.bad{color:var(--bad)}
pre{margin:0;padding:14px 16px;overflow:auto;max-height:440px;font:12.5px/1.55 var(--mono);color:#cfd8f2}pre[hidden]{display:none}
.copy{float:right;margin:6px 8px 0 0;font-size:11px;padding:4px 8px}
ul.notes{margin:8px 0;padding-left:18px;color:#cdd3e6}ul.notes li{margin:3px 0}
.pill{display:inline-block;font-size:11px;font-weight:600;padding:2px 8px;border-radius:999px;background:var(--panel2);color:var(--muted);border:1px solid var(--line)}
.couriers{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:10px}
.cr{display:flex;gap:10px;align-items:center;padding:10px 12px;background:var(--panel2);border:1px solid var(--line);border-radius:10px}
.cr img{width:34px;height:34px;object-fit:contain;border-radius:7px;background:#fff;padding:3px}.cr .ph{width:34px;height:34px;border-radius:7px;background:#1a2440;display:grid;place-items:center;font-weight:700;color:#9cbcff}
.cr b{display:block;font-size:13.5px}.cr small{color:var(--muted);font-family:var(--mono);font-size:11px}
.flow{counter-reset:s;list-style:none;padding:0;margin:0}.flow li{counter-increment:s;position:relative;padding:6px 0 6px 38px}
.flow li:before{content:counter(s);position:absolute;left:0;top:6px;width:26px;height:26px;border-radius:50%;background:rgba(59,123,255,.18);color:#9cbcff;font-weight:700;font-size:12px;display:grid;place-items:center}
@media (max-width:900px){.layout{grid-template-columns:1fr}table{display:block;overflow-x:auto}td .nm{word-break:break-word}.top .btn:not(.pri){display:none}aside{display:none}main{padding:16px}.grid2{grid-template-columns:1fr}h1{font-size:26px}}
</style>
</head>
<body>
<header class="top">
  <a href="{{ url('/') }}"><img src="{{ asset('site/assets/img/logo-white.png') }}" alt="Shipxpeed"></a>
  <nav>
    <a class="btn" href="{{ route('api.v1.postman') }}">Postman collection</a>
    <a class="btn pri" href="{{ $panelApi }}">Get your API key</a>
  </nav>
</header>
<div class="layout">
<aside>
  <h4>Start here</h4>
  <a href="#intro">Overview</a><a href="#auth">Authentication</a><a href="#flow">Quick start</a>
  <a href="#couriers-live">Live couriers</a><a href="#format">Responses & errors</a><a href="#statuses">Status codes</a>
  @foreach ($groups as $g => $list)
    <h4>{{ $g }}</h4>
    @foreach ($list as $e)<a href="#{{ $e['id'] }}"><span class="m">{{ $e['method'] }}</span>{{ $e['title'] }}</a>@endforeach
  @endforeach
  <h4>Reference</h4><a href="#webhook-events">Webhook events</a><a href="#limits">Limits</a>
</aside>
<main>
  <section id="intro">
    <span class="pill">API {{ \App\Services\ApiV1Docs::VERSION }}</span>
    <h1>Shipxpeed shipping API</h1>
    <p class="lead">Connect your store, ERP or app to Shipxpeed: get live rates from your own rate card, book B2C, reverse and B2B shipments, print labels, track, handle NDR and receive status webhooks. Every price, courier and wallet debit is exactly the same as in your Shipxpeed panel.</p>
    <div class="card"><b>Base URL</b><div class="ep"><span class="path">{{ $base }}/v1</span><button class="btn copy" data-copy="{{ $base }}/v1">Copy</button></div>
      All requests use HTTPS and JSON (B2B file uploads may use multipart/form-data).</div>
  </section>

  <section class="sec" id="auth">
    <h2>Authentication</h2>
    <p>Every request carries your secret API key. It identifies your account: rates come from your rate card, bookings debit your wallet, and you only ever see your own shipments.</p>
    <ul class="notes">
      <li>Find or regenerate it in <a href="{{ $panelApi }}">Seller panel → API</a>. Regenerating stops the old key immediately.</li>
      <li>Send it as <code>X-API-Key: sxp_…</code> (or <code>Authorization: Bearer sxp_…</code>).</li>
      <li>Keep it on your server. Never put it in a website or mobile app.</li>
    </ul>
    <div class="code"><pre>curl {{ $base }}/v1/account -H 'X-API-Key: YOUR_API_KEY'</pre></div>
  </section>

  <section class="sec" id="flow">
    <h2>Quick start</h2>
    <ol class="flow">
      <li><code>GET /v1/account</code>: check the key works and see your wallet balance.</li>
      <li><code>GET /v1/warehouses</code> (or <code>POST</code> one): note the <code>warehouse_id</code> you ship from.</li>
      <li><code>POST /v1/b2c/rates</code>: show your buyer or ops team the couriers and prices.</li>
      <li><code>POST /v1/b2c/shipments</code> with the chosen <code>courier_id</code>: you get the AWB back.</li>
      <li><code>GET /v1/shipments/{awb}/label</code>: print, hand over to the courier.</li>
      <li><code>POST /v1/webhooks</code> once: status updates arrive on your URL. Or poll <code>GET /v1/track/{awb}</code>.</li>
    </ol>
  </section>

  <section class="sec" id="couriers-live">
    <h2>Live couriers</h2>
    <p>This list is read from the Shipxpeed panel every time the page loads: when a new courier goes live it appears here and in the API automatically. Your own account may have more or fewer: <code>GET /v1/couriers</code> returns exactly yours.</p>
    @foreach (['b2c' => 'B2C (forward)', 'reverse' => 'Reverse pickups', 'b2b' => 'B2B (cargo)'] as $t => $label)
      @php $list = $couriers->where('type', $t)->values(); @endphp
      @if ($list->isNotEmpty())
        <h3>{{ $label }}</h3>
        <div class="couriers">
          @foreach ($list as $c)
            <div class="cr">@if ($c['logo'])<img src="{{ $c['logo'] }}" alt="">@else<span class="ph">{{ mb_substr($c['brand'], 0, 1) }}</span>@endif
              <div><b>{{ $c['name'] }}</b><small>{{ $c['courier_code'] }} · {{ $c['mode'] }}</small></div></div>
          @endforeach
        </div>
      @endif
    @endforeach
    @if ($couriers->isEmpty())<p class="lead">Couriers are being updated. Use <code>GET /v1/couriers</code> for your live list.</p>@endif
  </section>

  <section class="sec" id="format">
    <h2>Responses & errors</h2>
    <div class="grid2">
      <div class="code"><div class="tabs"><span class="tab on"><span class="st ok">2xx</span>Success</span></div><pre>{{ $j(['success' => true, 'message' => 'Shipment booked', 'data' => ['…' => '…']]) }}</pre></div>
      <div class="code"><div class="tabs"><span class="tab on"><span class="st bad">4xx</span>Error</span></div><pre>{{ $j(['success' => false, 'message' => 'The consignee.phone format is invalid.', 'error' => ['code' => 'validation_failed', 'fields' => ['consignee.phone' => ['The consignee.phone format is invalid.']]]]) }}</pre></div>
    </div>
    <table><tr><th>error.code</th><th>HTTP</th><th>Meaning</th></tr>
      @foreach ($docs->errorCodes() as [$code, $http, $desc])<tr><td class="nm">{{ $code }}</td><td>{{ $http }}</td><td>{{ $desc }}</td></tr>@endforeach
    </table>
  </section>

  <section class="sec" id="statuses">
    <h2>Status codes</h2>
    <p>Every shipment (B2C, reverse, B2B) carries one normalised <code>status</code>, whichever courier carries it. <code>courier_status</code> keeps the courier's own text.</p>
    <table><tr><th>status</th><th>Meaning</th></tr>@foreach ($docs->statuses() as $s => $d)<tr><td class="nm">{{ $s }}</td><td>{{ $d }}</td></tr>@endforeach</table>
  </section>

  @foreach ($endpoints as $e)
    @php $samples = $docs->samples($e, $base); $okStatus = $e['status'] ?? 200; @endphp
    <section class="sec" id="{{ $e['id'] }}">
      <span class="pill">{{ $e['group'] }}</span>
      <h2 style="margin-top:8px">{{ $e['title'] }}</h2>
      <div class="ep"><span class="meth {{ $e['method'] }}">{{ $e['method'] }}</span><span class="path"><span>{{ $base }}</span>{{ $e['path'] }}</span>
        <button class="btn copy" data-copy="{{ $base . $e['path'] }}">Copy URL</button></div>
      <p>{{ $e['summary'] }}</p>
      @if (!empty($e['notes']))<ul class="notes">@foreach ($e['notes'] as $n)<li>{{ $n }}</li>@endforeach</ul>@endif
      @if (!empty($e['fields']))
        <h3>{{ $e['method'] === 'GET' ? 'Parameters' : 'Body' }}</h3>
        <table>@foreach ($e['fields'] as [$name, $type, $req, $desc])
          <tr><td style="width:42%"><span class="nm">{{ $name }}</span>@if ($req === true)<span class="req">required</span>@elseif (is_string($req))<span class="req">{{ $req }}</span>@endif</td><td class="ty" style="width:14%">{{ $type }}</td><td>{{ $desc }}</td></tr>
        @endforeach</table>
      @endif
      <div class="grid2">
        <div class="code" data-tabs>
          <div class="tabs">@foreach ($samples as $lang => $src)<button type="button" class="tab {{ $loop->first ? 'on' : '' }}" data-tab="s{{ $loop->index }}">{{ $lang }}</button>@endforeach</div>
          @foreach ($samples as $lang => $src)<pre data-pane="s{{ $loop->index }}" @unless($loop->first) hidden @endunless><button class="btn copy" data-copy-pre>Copy</button>{{ $src }}</pre>@endforeach
        </div>
        <div class="code" data-tabs>
          <div class="tabs"><button type="button" class="tab on" data-tab="r0"><span class="st ok">{{ $okStatus }}</span>Success</button>
            @foreach ($e['errors'] as $i => [$st, $b])<button type="button" class="tab" data-tab="r{{ $i + 1 }}"><span class="st bad">{{ $st }}</span>{{ $b['error']['code'] ?? 'Error' }}</button>@endforeach</div>
          <pre data-pane="r0">{{ $j($e['response']) }}</pre>
          @foreach ($e['errors'] as $i => [$st, $b])<pre data-pane="r{{ $i + 1 }}" hidden>{{ $j($b) }}</pre>@endforeach
        </div>
      </div>
      @if (!empty($e['response_fields']))
        <h3>Response fields</h3>
        <table>@foreach ($e['response_fields'] as [$n, $t, $d])<tr><td style="width:42%" class="nm">{{ $n }}</td><td class="ty" style="width:14%">{{ $t }}</td><td>{{ $d }}</td></tr>@endforeach</table>
      @endif
    </section>
  @endforeach

  <section class="sec" id="webhook-events">
    <h2>Webhook events</h2>
    <p>When a shipment's status changes (courier scan, NDR, delivery, RTO, cancellation), Shipxpeed sends a <code>POST</code> to your URL with this JSON. Reply with any 2xx within 5 seconds. Events cover B2C, reverse and B2B, every courier.</p>
    <div class="code"><pre>{{ $j($docs->webhookExample()) }}</pre></div>
    <h3>Verify the signature</h3>
    <p>Header <code>X-Shipxpeed-Signature</code> is the hex HMAC-SHA256 of the raw request body, keyed with your API key. Compare it before trusting the call.</p>
    <div class="code"><pre>// PHP
$raw = file_get_contents('php://input');
$ok  = hash_equals(hash_hmac('sha256', $raw, 'YOUR_API_KEY'), $_SERVER['HTTP_X_SHIPXPEED_SIGNATURE'] ?? '');

// Node.js (Express, raw body)
const sig = crypto.createHmac('sha256', process.env.SHIPXPEED_KEY).update(req.rawBody).digest('hex');
const ok  = crypto.timingSafeEqual(Buffer.from(sig), Buffer.from(req.get('X-Shipxpeed-Signature') || ''));</pre></div>
  </section>

  <section class="sec" id="limits">
    <h2>Limits & good practice</h2>
    <ul class="notes">
      <li>120 requests per minute per key. Over that you get <code>429</code> with a <code>Retry-After</code> header.</li>
      <li>Always send <code>client_order_id</code>: retries then never create a second shipment.</li>
      <li>Rates change with weight, size, payment type and your rate card. Fetch them again when the parcel changes.</li>
      <li>Keep at least ₹150 in your wallet so bookings are not refused.</li>
      <li>Help: <a href="mailto:support@shipxpeed.com">support@shipxpeed.com</a></li>
    </ul>
  </section>
</main>
</div>
<script>
document.addEventListener('click', function (ev) {
  var t = ev.target.closest('[data-tab]');
  if (t) { var box = t.closest('[data-tabs]'); box.querySelectorAll('.tab').forEach(function (b) { b.classList.toggle('on', b === t); });
    box.querySelectorAll('[data-pane]').forEach(function (p) { p.hidden = p.getAttribute('data-pane') !== t.getAttribute('data-tab'); }); return; }
  var c = ev.target.closest('[data-copy],[data-copy-pre]');
  if (c) { var txt = c.hasAttribute('data-copy') ? c.getAttribute('data-copy') : c.parentNode.textContent.replace(/^Copy/, '');
    try { navigator.clipboard.writeText(txt.trim()); var o = c.textContent; c.textContent = 'Copied'; setTimeout(function () { c.textContent = o; }, 1200); } catch (e) {} }
});
</script>
</body>
</html>
