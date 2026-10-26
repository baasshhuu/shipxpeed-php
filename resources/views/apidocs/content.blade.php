{{--
  Shipxpeed seller API docs. Shared by /seller/api (inPanel=true, shows the token card) and /api-docs.
  Everything is generated from App\Services\SellerApiDocs so the page, code samples and Postman stay in sync.
  Expects: $docs (SellerApiDocs), $baseUrl, $inPanel (bool), $apiToken (string|null, panel only)
--}}
@php
    $endpoints = $docs->endpoints();
    $groups = collect($endpoints)->groupBy('group');
    $jsonFlags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    $json = fn ($v) => json_encode($v, $jsonFlags);

    $phpLit = function ($v, $ind = 0) use (&$phpLit) {
        $pad = str_repeat('    ', $ind);
        if (is_array($v)) {
            if ($v === []) return '[]';
            $isList = array_keys($v) === range(0, count($v) - 1);
            $rows = [];
            foreach ($v as $k => $x) {
                $rows[] = $pad . '    ' . ($isList ? '' : var_export((string) $k, true) . ' => ') . $phpLit($x, $ind + 1);
            }
            return "[\n" . implode(",\n", $rows) . ",\n" . $pad . ']';
        }
        if (is_bool($v)) return $v ? 'true' : 'false';
        if ($v === null) return 'null';
        if (is_int($v) || is_float($v)) return (string) $v;
        return var_export((string) $v, true);
    };

    $pyLit = function ($v, $ind = 0) use (&$pyLit, $jsonFlags) {
        $pad = str_repeat('    ', $ind);
        if (is_array($v)) {
            if ($v === []) return '[]';
            $isList = array_keys($v) === range(0, count($v) - 1);
            $rows = [];
            foreach ($v as $k => $x) {
                $rows[] = $pad . '    ' . ($isList ? '' : json_encode((string) $k, $jsonFlags) . ': ') . $pyLit($x, $ind + 1);
            }
            return ($isList ? "[\n" : "{\n") . implode(",\n", $rows) . ",\n" . $pad . ($isList ? ']' : '}');
        }
        if (is_bool($v)) return $v ? 'True' : 'False';
        if ($v === null) return 'None';
        if (is_int($v) || is_float($v)) return (string) $v;
        return json_encode((string) $v, $jsonFlags);
    };

    $samples = function ($e) use ($baseUrl, $json, $phpLit, $pyLit) {
        $url = $baseUrl . $e['path'];
        $tok = 'YOUR_API_TOKEN';
        $body = $json($e['request']);

        $curl = "curl -X {$e['method']} '{$url}' \\\n  -H 'Content-Type: application/json' \\\n  -H 'Accept: application/json' \\\n"
            . ($e['auth'] ? "  -H 'Authorization: {$tok}' \\\n" : '')
            . "  -d '" . str_replace("'", "'\\''", $body) . "'";

        $php = "<?php\n\$ch = curl_init('{$url}');\ncurl_setopt_array(\$ch, [\n    CURLOPT_POST => true,\n    CURLOPT_RETURNTRANSFER => true,\n    CURLOPT_HTTPHEADER => [\n        'Content-Type: application/json',\n        'Accept: application/json',\n"
            . ($e['auth'] ? "        'Authorization: {$tok}',\n" : '')
            . "    ],\n    CURLOPT_POSTFIELDS => json_encode(" . $phpLit($e['request'], 1) . "),\n]);\n\$response = json_decode(curl_exec(\$ch), true);\ncurl_close(\$ch);";

        $node = "const res = await fetch('{$url}', {\n  method: '{$e['method']}',\n  headers: {\n    'Content-Type': 'application/json',\n    'Accept': 'application/json',\n"
            . ($e['auth'] ? "    'Authorization': '{$tok}',\n" : '')
            . "  },\n  body: JSON.stringify(" . str_replace("\n", "\n  ", $body) . "),\n});\nconst data = await res.json();";

        $py = "import requests\n\nres = requests.post(\n    \"{$url}\",\n    headers={" . ($e['auth'] ? "\"Authorization\": \"{$tok}\", " : '') . "\"Accept\": \"application/json\"},\n    json=" . $pyLit($e['request'], 1) . ",\n)\nprint(res.json())";

        return ['cURL' => $curl, 'PHP' => $php, 'Node.js' => $node, 'Python' => $py];
    };

    $postmanUrl = route('api.docs.postman');
    $hasToken = !empty($apiToken);
    $legacyToken = $hasToken && !str_starts_with((string) $apiToken, 'sxp_');
    $maskedToken = $hasToken ? substr($apiToken, 0, 6) . str_repeat('•', 18) . substr($apiToken, -4) : '';
    $webhooksUrl = \Illuminate\Support\Facades\Route::has('seller.webhooks.index') ? route('seller.webhooks.index') : null;
@endphp

<style>
.sxapi{--sx-bg:var(--fs-bg,#0a0f1c);--sx-surface:var(--fs-surface,#0f1626);--sx-surface2:var(--fs-surface2,#152036);--sx-line:var(--fs-line,rgba(196,206,235,.11));--sx-text:var(--fs-text,#e9ecf5);--sx-muted:var(--fs-muted,#a3abc2);--sx-brand:var(--fs-brand,#3b7bff);--sx-ok:var(--fs-ok,#5fd3a1);--sx-okb:var(--fs-okb,rgba(95,211,161,.12));--sx-warn:var(--fs-warn,#f2b45a);--sx-warnb:var(--fs-warnb,rgba(242,180,90,.13));--sx-bad:var(--fs-bad,#ff7b7b);--sx-badb:var(--fs-badb,rgba(255,123,123,.12));--sx-infob:var(--fs-infob,rgba(59,123,255,.14));--sx-code:#0a1020;--sx-mono:"JetBrains Mono",ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
  color:var(--sx-text);font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;font-size:14.5px;line-height:1.6;display:grid;grid-template-columns:minmax(0,1fr);gap:28px;background:var(--sx-bg);border-radius:16px;padding:24px 16px 40px;margin:8px 0 24px}
@media (min-width:1100px){.sxapi{grid-template-columns:210px minmax(0,1fr);padding:28px 28px 48px}}
.sxapi *{box-sizing:border-box}
.sxapi a{color:#8fb3ff;text-decoration:none}.sxapi a:hover{text-decoration:underline}
.sxapi h1,.sxapi h2,.sxapi h3{color:#fff;letter-spacing:-.01em;margin:0}
.sxapi h1{font-size:clamp(24px,2.4vw,32px);line-height:1.2}
.sxapi h2{font-size:21px;line-height:1.3}
.sxapi h3{font-size:13px;text-transform:uppercase;letter-spacing:.06em;color:var(--sx-muted);font-weight:600;margin:22px 0 10px}
.sxapi p{margin:0 0 10px}
.sxapi code,.sxapi .mono{font-family:var(--sx-mono);font-size:.9em}
.sxapi :not(pre)>code{background:var(--sx-surface2);border:1px solid var(--sx-line);border-radius:6px;padding:1px 6px;color:#dbe4ff;word-break:break-word}
/* nav */
.sxapi-nav{position:relative}
.sxapi-nav-inner{display:flex;gap:6px;overflow-x:auto;padding-bottom:4px;scrollbar-width:none}
.sxapi-nav-inner::-webkit-scrollbar{display:none}
.sxapi-nav .grp{display:none}
.sxapi-nav a{display:block;white-space:nowrap;color:var(--sx-muted);padding:6px 10px;border-radius:8px;font-size:13.5px;border:1px solid var(--sx-line)}
.sxapi-nav a:hover{color:#fff;background:var(--sx-surface2);text-decoration:none}
.sxapi-nav a.on{color:#fff;background:var(--sx-infob);border-color:rgba(59,123,255,.35)}
.sxapi-nav .m{font-family:var(--sx-mono);font-size:10.5px;font-weight:700;color:var(--sx-brand);margin-right:6px}
@media (min-width:1100px){.sxapi-nav-inner{position:sticky;top:88px;display:block;overflow:visible;max-height:calc(100vh - 110px);overflow-y:auto}.sxapi-nav .grp{display:block;font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#6f7893;font-weight:600;margin:18px 10px 6px}.sxapi-nav .grp:first-child{margin-top:0}.sxapi-nav a{border:0;margin:1px 0}}
/* hero */
.sxapi-hero{padding:4px 0 6px;border-bottom:1px solid var(--sx-line);margin-bottom:8px}
.sxapi-eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--sx-brand);margin-bottom:8px}
.sxapi-eyebrow:before{content:"";width:7px;height:7px;border-radius:50%;background:var(--sx-brand);box-shadow:0 0 10px var(--sx-brand)}
.sxapi-lead{color:var(--sx-muted);font-size:15.5px;max-width:760px;margin:10px 0 18px}
.sxapi-meta{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px}
.sxapi-chip{display:inline-flex;align-items:center;gap:8px;background:var(--sx-surface);border:1px solid var(--sx-line);border-radius:10px;padding:8px 12px;font-size:13px;color:var(--sx-muted);max-width:100%}
.sxapi-chip b{color:#fff;font-weight:500;font-family:var(--sx-mono);font-size:12.5px;overflow-wrap:anywhere}
.sxapi-btn{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--sx-line);background:var(--sx-surface2);color:#fff;border-radius:10px;padding:8px 13px;font:600 13px Inter,system-ui,sans-serif;cursor:pointer;white-space:nowrap}
.sxapi-btn:hover{border-color:rgba(255,255,255,.22);text-decoration:none;color:#fff}
.sxapi-btn.pri{background:var(--sx-brand);border-color:var(--sx-brand)}
.sxapi-btn.pri:hover{filter:brightness(1.1)}
.sxapi-btn.danger{background:var(--sx-badb);border-color:rgba(255,123,123,.35);color:#ffd0d0}
.sxapi-btn.sm{padding:5px 10px;font-size:12px;border-radius:8px}
.sxapi-iconbtn{background:transparent;border:1px solid var(--sx-line);color:var(--sx-muted);border-radius:7px;padding:3px 8px;font:500 11.5px Inter,system-ui,sans-serif;cursor:pointer}
.sxapi-iconbtn:hover{color:#fff;border-color:rgba(255,255,255,.25)}
/* cards / sections */
.sxapi-main{min-width:0}
.sxapi-sec{padding:26px 0;border-bottom:1px solid var(--sx-line);scroll-margin-top:90px}
.sxapi-sec:last-child{border-bottom:0}
.sxapi-card{background:var(--sx-surface);border:1px solid var(--sx-line);border-radius:14px;padding:18px}
.sxapi-steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-top:14px}
.sxapi-step{background:var(--sx-surface);border:1px solid var(--sx-line);border-radius:12px;padding:12px 14px}
.sxapi-step .n{font:700 11px var(--sx-mono);color:var(--sx-brand)}
.sxapi-step .t{color:#fff;font-weight:600;margin:2px 0}
.sxapi-step .d{color:var(--sx-muted);font-size:12.5px;line-height:1.45}
.sxapi-ep-head{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin:10px 0 12px}
.sxapi-method{font:700 11.5px var(--sx-mono);letter-spacing:.04em;color:#fff;background:var(--sx-brand);border-radius:6px;padding:3px 8px}
.sxapi-path{font-family:var(--sx-mono);font-size:13.5px;color:#fff;background:var(--sx-surface);border:1px solid var(--sx-line);border-radius:8px;padding:5px 10px;overflow-wrap:anywhere}
.sxapi-path span{color:var(--sx-muted)}
.sxapi-auth{font-size:12px;color:var(--sx-muted);display:inline-flex;align-items:center;gap:6px}
.sxapi-auth i{width:7px;height:7px;border-radius:50%;background:var(--sx-warn);display:inline-block}
.sxapi-auth.open i{background:var(--sx-ok)}
.sxapi-notes{margin:10px 0 0;padding:0;list-style:none;display:grid;gap:6px}
.sxapi-notes li{position:relative;padding-left:18px;color:#cfd6e8;font-size:13.5px}
.sxapi-notes li:before{content:"";position:absolute;left:4px;top:9px;width:5px;height:5px;border-radius:50%;background:var(--sx-brand)}
/* field list */
.sxapi-fields{border:1px solid var(--sx-line);border-radius:12px;overflow:hidden}
.sxapi-field{display:grid;grid-template-columns:minmax(0,1fr);gap:2px 16px;padding:10px 14px;border-top:1px solid var(--sx-line)}
.sxapi-field:first-child{border-top:0}
@media (min-width:760px){.sxapi-field{grid-template-columns:minmax(180px,34%) minmax(0,1fr)}}
.sxapi-field .nm{font-family:var(--sx-mono);font-size:12.5px;color:#fff;overflow-wrap:anywhere}
.sxapi-field .ty{display:block;font-family:var(--sx-mono);font-size:11px;color:var(--sx-muted);margin-top:1px}
.sxapi-req{font:600 10px Inter,system-ui,sans-serif;text-transform:uppercase;letter-spacing:.05em;color:var(--sx-warn);margin-left:6px}
.sxapi-field .ds{color:#cfd6e8;font-size:13.5px}
/* code */
.sxapi-code{border:1px solid var(--sx-line);border-radius:12px;background:var(--sx-code);overflow:hidden;margin-top:12px}
.sxapi-code-bar{display:flex;align-items:center;gap:4px;padding:6px 8px;border-bottom:1px solid var(--sx-line);background:rgba(255,255,255,.02);overflow-x:auto}
.sxapi-tab{background:transparent;border:0;color:var(--sx-muted);font:600 12px Inter,system-ui,sans-serif;padding:5px 10px;border-radius:7px;cursor:pointer;white-space:nowrap}
.sxapi-tab:hover{color:#fff}
.sxapi-tab.on{color:#fff;background:var(--sx-surface2)}
.sxapi-tab .st{font-family:var(--sx-mono);font-size:11px;margin-right:4px}
.sxapi-tab .st.ok{color:var(--sx-ok)}.sxapi-tab .st.bad{color:var(--sx-bad)}
.sxapi-code-bar .sp{flex:1}
.sxapi pre{margin:0;padding:14px 16px;overflow:auto;max-height:460px;font:12.5px/1.6 var(--sx-mono);color:#d7def0;tab-size:2}
.sxapi pre[hidden]{display:none}
.sxapi .tk-s{color:#a5e3b4}.sxapi .tk-k{color:#8fb3ff}.sxapi .tk-n{color:#f5c27a}.sxapi .tk-b{color:#ff9db5}.sxapi .tk-c{color:#6f7893}
/* tables */
.sxapi-table{width:100%;border-collapse:collapse;font-size:13.5px}
.sxapi-table th{text-align:left;font-size:11.5px;letter-spacing:.05em;text-transform:uppercase;color:var(--sx-muted);font-weight:600;padding:8px 12px;border-bottom:1px solid var(--sx-line)}
.sxapi-table td{padding:9px 12px;border-bottom:1px solid var(--sx-line);vertical-align:top;color:#cfd6e8}
.sxapi-table tr:last-child td{border-bottom:0}
.sxapi-table td:first-child{white-space:nowrap}
.sxapi-scroll{overflow-x:auto;border:1px solid var(--sx-line);border-radius:12px}
/* credentials */
.sxapi-cred{display:grid;gap:14px}
.sxapi-token{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
.sxapi-token code{font-size:13px!important;padding:8px 12px!important;border-radius:9px!important;flex:1 1 260px;min-width:0;overflow-wrap:anywhere}
.sxapi-alert{border-radius:10px;padding:10px 12px;font-size:13.5px;border:1px solid}
.sxapi-alert.ok{background:var(--sx-okb);border-color:rgba(95,211,161,.3);color:#c9f5e0}
.sxapi-alert.warn{background:var(--sx-warnb);border-color:rgba(242,180,90,.3);color:#ffe2b8}
.sxapi-confirm{display:none;align-items:center;flex-wrap:wrap;gap:8px;background:var(--sx-badb);border:1px solid rgba(255,123,123,.3);border-radius:10px;padding:10px 12px;font-size:13px;color:#ffd6d6}
.sxapi-confirm.show{display:flex}
.sxapi-muted{color:var(--sx-muted)}
.sxapi-toast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%) translateY(20px);opacity:0;background:#1d2a44;color:#fff;border:1px solid var(--sx-line);border-radius:10px;padding:8px 14px;font:500 13px Inter,system-ui,sans-serif;transition:.2s;pointer-events:none;z-index:9999}
.sxapi-toast.show{opacity:1;transform:translateX(-50%)}
</style>

<div class="sxapi" id="sxapi">
    <nav class="sxapi-nav" aria-label="API sections">
        <div class="sxapi-nav-inner">
            <div class="grp">Overview</div>
            @if ($inPanel)<a href="#credentials">Your credentials</a>@endif
            <a href="#overview">How it works</a>
            <a href="#conventions">Requests &amp; errors</a>
            @foreach ($groups as $gname => $list)
                <div class="grp">{{ $gname }}</div>
                @foreach ($list as $e)
                    <a href="#{{ $e['id'] }}"><span class="m">{{ $e['method'] }}</span>{{ $e['title'] }}</a>
                @endforeach
            @endforeach
            <div class="grp">Reference</div>
            <a href="#courier-ids">Courier IDs</a>
            <a href="#webhooks">Webhooks</a>
            <a href="#support">Support</a>
        </div>
    </nav>

    <div class="sxapi-main">
        <header class="sxapi-hero">
            <div class="sxapi-eyebrow">Developer API</div>
            <h1>Shipxpeed Seller API</h1>
            <p class="sxapi-lead">Fetch couriers and live rates, book shipments, print labels, track parcels, cancel and handle NDRs from your own store, ERP or app. JSON over HTTPS, one token per account.</p>
            <div class="sxapi-meta">
                <span class="sxapi-chip">Base URL <b id="sxapi-base">{{ $baseUrl }}</b> <button type="button" class="sxapi-iconbtn" data-copy="{{ $baseUrl }}">Copy</button></span>
                <a class="sxapi-btn" href="{{ $postmanUrl }}" download="shipxpeed-api.postman_collection.json">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0-4-4m4 4 4-4M4 21h16"/></svg>
                    Postman collection
                </a>
                @unless ($inPanel)
                    <a class="sxapi-btn pri" href="{{ route('seller.login') }}">Log in to get your token</a>
                @endunless
            </div>
        </header>

        @if ($inPanel)
        <section class="sxapi-sec" id="credentials">
            <h2>Your credentials</h2>
            <p class="sxapi-muted" style="margin-top:6px">Send this token in the <code>Authorization</code> header. Keep it secret: anyone with it can book shipments on your wallet.</p>
            <div class="sxapi-card sxapi-cred">
                @if (session('api_token_status'))
                    <div class="sxapi-alert ok" role="status">{{ session('api_token_status') }}</div>
                @endif
                @if ($hasToken)
                    <div class="sxapi-token">
                        <code id="sxapi-token" data-full="{{ $apiToken }}" data-masked="{{ $maskedToken }}">{{ $maskedToken }}</code>
                        <button type="button" class="sxapi-btn sm" id="sxapi-token-show">Show</button>
                        <button type="button" class="sxapi-btn sm" data-copy="{{ $apiToken }}">Copy</button>
                        <button type="button" class="sxapi-btn sm danger" id="sxapi-regen-open">Regenerate</button>
                    </div>
                    @if ($legacyToken)
                        <div class="sxapi-alert warn">This token uses the old format. Regenerate it for a stronger token, then update your integrations.</div>
                    @endif
                    <form method="POST" action="{{ route('seller.api.token') }}" class="sxapi-confirm" id="sxapi-regen-confirm">
                        @csrf
                        <span style="flex:1 1 240px">The current token stops working immediately in every integration. Regenerate?</span>
                        <button type="button" class="sxapi-btn sm" id="sxapi-regen-cancel">Cancel</button>
                        <button type="submit" class="sxapi-btn sm danger">Yes, regenerate</button>
                    </form>
                @else
                    <div class="sxapi-token">
                        <span class="sxapi-muted" style="flex:1 1 240px">You don't have an API token yet.</span>
                        <form method="POST" action="{{ route('seller.api.token') }}">
                            @csrf
                            <button type="submit" class="sxapi-btn pri sm">Generate token</button>
                        </form>
                    </div>
                @endif
                <div class="sxapi-muted" style="font-size:13px">
                    You can also get the token in code with <a href="#auth">Generate token</a>.
                    @if ($webhooksUrl) Status updates: <a href="{{ $webhooksUrl }}">set your webhook URL</a>. @endif
                </div>
            </div>
        </section>
        @endif

        <section class="sxapi-sec" id="overview">
            <h2>How it works</h2>
            <div class="sxapi-steps">
                <div class="sxapi-step"><div class="n">01</div><div class="t">Get a token</div><div class="d">{{ $inPanel ? 'Copy it above' : 'From the seller panel' }} or call Generate token.</div></div>
                <div class="sxapi-step"><div class="n">02</div><div class="t">Fetch couriers</div><div class="d">Rates, EDD and a courier_id for every courier on the lane.</div></div>
                <div class="sxapi-step"><div class="n">03</div><div class="t">Create shipment</div><div class="d">Pass the courier_id to book and get the AWB in one call.</div></div>
                <div class="sxapi-step"><div class="n">04</div><div class="t">Label &amp; pickup</div><div class="d">Download the label and hand the parcel to the courier.</div></div>
                <div class="sxapi-step"><div class="n">05</div><div class="t">Track</div><div class="d">Poll Track shipment or receive webhooks. Cancel or act on NDRs.</div></div>
            </div>
        </section>

        <section class="sxapi-sec" id="conventions">
            <h2>Requests &amp; errors</h2>
            <p style="margin-top:8px">Every endpoint is a <code>POST</code> with a JSON body. Send these headers:</p>
            <div class="sxapi-code">
                <div class="sxapi-code-bar"><span class="sxapi-tab on">Headers</span><span class="sp"></span><button type="button" class="sxapi-iconbtn" data-copy-pre>Copy</button></div>
                <pre><code>Content-Type: application/json
Accept: application/json
Authorization: YOUR_API_TOKEN</code></pre>
            </div>
            <ul class="sxapi-notes" style="margin-top:14px">
                <li><code>Authorization: Bearer YOUR_API_TOKEN</code> works too.</li>
                <li>Weights are in grams, dimensions in cm, amounts in ₹. Pincodes and phone numbers are strings in Create shipment.</li>
                <li>Check <code>success</code> (or <code>status</code>) in the body as well as the HTTP code.</li>
                <li>Up to 60 requests per minute from one IP address. Generate token allows 10 per minute.</li>
            </ul>
            <h3>HTTP status codes</h3>
            <div class="sxapi-scroll">
                <table class="sxapi-table">
                    <thead><tr><th>Code</th><th>Meaning</th></tr></thead>
                    <tbody>
                        <tr><td class="mono">200</td><td>Request handled. Read <code>success</code> / <code>status</code> and <code>message</code>.</td></tr>
                        <tr><td class="mono">201</td><td>Created (Create warehouse).</td></tr>
                        <tr><td class="mono">400</td><td>The request can't be done, e.g. the shipment is already cancelled.</td></tr>
                        <tr><td class="mono">401</td><td>Token missing, wrong or regenerated, or the account is inactive.</td></tr>
                        <tr><td class="mono">404</td><td>No shipment with that AWB on your account.</td></tr>
                        <tr><td class="mono">422</td><td>Validation failed. <code>errors</code> lists each field.</td></tr>
                        <tr><td class="mono">429</td><td>Too many requests. Wait a minute and retry.</td></tr>
                        <tr><td class="mono">500</td><td>Something failed on our side. Retry, then contact support with the AWB or order ID.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        @foreach ($endpoints as $e)
            @php $code = $samples($e); @endphp
            <section class="sxapi-sec sxapi-ep" id="{{ $e['id'] }}">
                <h2>{{ $e['title'] }}</h2>
                <div class="sxapi-ep-head">
                    <span class="sxapi-method">{{ $e['method'] }}</span>
                    <span class="sxapi-path"><span>{{ $baseUrl }}</span>{{ $e['path'] }}</span>
                    <button type="button" class="sxapi-iconbtn" data-copy="{{ $baseUrl . $e['path'] }}">Copy URL</button>
                    <span class="sxapi-auth {{ $e['auth'] ? '' : 'open' }}"><i></i>{{ $e['auth'] ? 'Token required' : 'No token needed' }}</span>
                </div>
                <p>{{ $e['summary'] }}</p>
                @if (!empty($e['notes']))
                    <ul class="sxapi-notes">
                        @foreach ($e['notes'] as $n)<li>{{ $n }}</li>@endforeach
                    </ul>
                @endif

                <h3>Request body</h3>
                <div class="sxapi-fields">
                    @foreach ($e['fields'] as [$name, $type, $req, $desc])
                        <div class="sxapi-field">
                            <div><span class="nm">{{ $name }}</span>@if ($req)<span class="sxapi-req">required</span>@endif<span class="ty">{{ $type }}</span></div>
                            <div class="ds">{{ $desc }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="sxapi-code" data-tabs="lang">
                    <div class="sxapi-code-bar">
                        @foreach ($code as $lang => $src)
                            <button type="button" class="sxapi-tab {{ $loop->first ? 'on' : '' }}" data-tab="{{ $lang }}">{{ $lang }}</button>
                        @endforeach
                        <span class="sp"></span>
                        <button type="button" class="sxapi-iconbtn" data-copy-pre>Copy</button>
                    </div>
                    @foreach ($code as $lang => $src)
                        <pre data-pane="{{ $lang }}" @unless($loop->first) hidden @endunless><code>{{ $src }}</code></pre>
                    @endforeach
                </div>

                <h3>Response</h3>
                <div class="sxapi-code" data-tabs="resp">
                    <div class="sxapi-code-bar">
                        <button type="button" class="sxapi-tab on" data-tab="r0"><span class="st ok">{{ $e['id'] === 'warehouse' ? 201 : 200 }}</span>Success</button>
                        @foreach ($e['errors'] as $i => [$status, $body])
                            <button type="button" class="sxapi-tab" data-tab="r{{ $i + 1 }}"><span class="st bad">{{ $status }}</span>{{ $status === 200 ? 'Not done' : 'Error' }}</button>
                        @endforeach
                        <span class="sp"></span>
                        <button type="button" class="sxapi-iconbtn" data-copy-pre>Copy</button>
                    </div>
                    <pre data-pane="r0"><code>{{ $json($e['response']) }}</code></pre>
                    @foreach ($e['errors'] as $i => [$status, $body])
                        <pre data-pane="r{{ $i + 1 }}" hidden><code>{{ $json($body) }}</code></pre>
                    @endforeach
                </div>

                @if (!empty($e['response_fields']))
                    <h3>Response fields</h3>
                    <div class="sxapi-fields">
                        @foreach ($e['response_fields'] as [$name, $type, $desc])
                            <div class="sxapi-field">
                                <div><span class="nm">{{ $name }}</span><span class="ty">{{ $type }}</span></div>
                                <div class="ds">{{ $desc }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach

        <section class="sxapi-sec" id="courier-ids">
            <h2>Courier IDs</h2>
            <p style="margin-top:8px">Always take <code>courier_id</code> from the latest <a href="#serviceability">Fetch couriers</a> response: which couriers appear depends on the lane, the weight and your account. For reference:</p>
            <div class="sxapi-scroll">
                <table class="sxapi-table">
                    <thead><tr><th>courier_id</th><th>Books</th></tr></thead>
                    <tbody>
                        <tr><td class="mono">sxc_&lt;number&gt;</td><td>Network couriers (Xpressbees, Blue Dart, Ekart, Delhivery and more). The number identifies the courier and service.</td></tr>
                        <tr><td class="mono">sxf_&lt;number&gt;</td><td>More network couriers. Same usage as sxc_.</td></tr>
                        @foreach ($docs->courierIds() as [$cid, $label])
                            <tr><td class="mono">{{ $cid }}</td><td>{{ $label }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="sxapi-sec" id="webhooks">
            <h2>Webhooks</h2>
            <p style="margin-top:8px">Add your URL in the panel under @if ($webhooksUrl)<a href="{{ $webhooksUrl }}">Settings → Webhooks</a>@else Settings → Webhooks @endif. Shipxpeed sends a <code>POST</code> with this JSON body when a courier pushes a status update for one of your shipments. Reply with any 2xx code; each update is tried up to 3 times.</p>
            <ul class="sxapi-notes">
                <li>Not every courier pushes live scans, so call <a href="#track">Track shipment</a> as a fallback.</li>
                <li>Match updates to your orders by <code>awb</code>.</li>
            </ul>
            <div class="sxapi-code">
                <div class="sxapi-code-bar"><span class="sxapi-tab on">Payload</span><span class="sp"></span><button type="button" class="sxapi-iconbtn" data-copy-pre>Copy</button></div>
                <pre><code>{{ $json($docs->webhookExample()) }}</code></pre>
            </div>
        </section>

        <section class="sxapi-sec" id="support">
            <h2>Support</h2>
            <p style="margin-top:8px">
                Stuck on an integration? Email <a href="mailto:support@shipxpeed.com">support@shipxpeed.com</a> with the endpoint, the request body (without your token) and the response.
                @if ($inPanel && \Illuminate\Support\Facades\Route::has('seller.ticket.get'))
                    You can also <a href="{{ route('seller.ticket.get') }}">raise a support ticket</a>.
                @endif
                We reply within 2 working hours.
            </p>
        </section>
    </div>
</div>
<div class="sxapi-toast" id="sxapi-toast" role="status" aria-live="polite"></div>

<script>
(function () {
    var root = document.getElementById('sxapi');
    if (!root) return;
    var toastEl = document.getElementById('sxapi-toast'), toastT;
    function toast(msg) { toastEl.textContent = msg; toastEl.classList.add('show'); clearTimeout(toastT); toastT = setTimeout(function () { toastEl.classList.remove('show'); }, 1600); }
    function copy(text) {
        function fallback() { var t = document.createElement('textarea'); t.value = text; t.style.position = 'fixed'; t.style.opacity = '0'; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); } catch (e) {} document.body.removeChild(t); toast('Copied'); }
        if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(text).then(function () { toast('Copied'); }, fallback); else fallback();
    }

    // Syntax colouring (strings, keys, numbers, keywords, comments)
    var re = /("(?:\\.|[^"\\\n])*"|'(?:\\.|[^'\\\n])*')(\s*:)?|(\/\/[^\n]*|#[^\n{]*$)|\b(-?\d+(?:\.\d+)?)\b|\b(true|false|null|True|False|None|const|await|import|curl|new)\b/gm;
    function esc(s) { return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
    root.querySelectorAll('pre code').forEach(function (el) {
        var src = el.textContent, out = '', last = 0, m;
        re.lastIndex = 0;
        while ((m = re.exec(src))) {
            out += esc(src.slice(last, m.index));
            if (m[1]) out += '<span class="' + (m[2] ? 'tk-k' : 'tk-s') + '">' + esc(m[1]) + '</span>' + (m[2] ? esc(m[2]) : '');
            else if (m[3]) out += '<span class="tk-c">' + esc(m[3]) + '</span>';
            else if (m[4]) out += '<span class="tk-n">' + esc(m[4]) + '</span>';
            else out += '<span class="tk-b">' + esc(m[5]) + '</span>';
            last = re.lastIndex;
        }
        el.innerHTML = out + esc(src.slice(last));
    });

    // Tabs. Language choice is shared by every endpoint and remembered.
    var store = { get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } }, set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} } };
    function show(box, name) {
        box.querySelectorAll('.sxapi-tab[data-tab]').forEach(function (t) { t.classList.toggle('on', t.getAttribute('data-tab') === name); });
        box.querySelectorAll('pre[data-pane]').forEach(function (p) { p.hidden = p.getAttribute('data-pane') !== name; });
    }
    root.addEventListener('click', function (e) {
        var tab = e.target.closest('.sxapi-tab[data-tab]');
        if (tab) {
            var box = tab.closest('.sxapi-code'), name = tab.getAttribute('data-tab');
            if (box.getAttribute('data-tabs') === 'lang') {
                root.querySelectorAll('.sxapi-code[data-tabs="lang"]').forEach(function (b) { show(b, name); });
                store.set('sxapi-lang', name);
            } else show(box, name);
            return;
        }
        var c = e.target.closest('[data-copy]');
        if (c) { copy(c.getAttribute('data-copy')); return; }
        var cp = e.target.closest('[data-copy-pre]');
        if (cp) { var pre = Array.prototype.find.call(cp.closest('.sxapi-code').querySelectorAll('pre'), function (p) { return !p.hidden; }); if (pre) copy(pre.textContent); }
    });
    var saved = store.get('sxapi-lang');
    if (saved) root.querySelectorAll('.sxapi-code[data-tabs="lang"]').forEach(function (b) { if (b.querySelector('[data-tab="' + saved + '"]')) show(b, saved); });

    // Token show / regenerate confirm
    var tok = document.getElementById('sxapi-token'), showBtn = document.getElementById('sxapi-token-show');
    if (tok && showBtn) showBtn.addEventListener('click', function () {
        var on = tok.textContent === tok.getAttribute('data-masked');
        tok.textContent = on ? tok.getAttribute('data-full') : tok.getAttribute('data-masked');
        showBtn.textContent = on ? 'Hide' : 'Show';
    });
    var ro = document.getElementById('sxapi-regen-open'), rc = document.getElementById('sxapi-regen-confirm'), rx = document.getElementById('sxapi-regen-cancel');
    if (ro && rc) { ro.addEventListener('click', function () { rc.classList.add('show'); }); rx.addEventListener('click', function () { rc.classList.remove('show'); }); }

    // Highlight the section in view
    var links = {}; root.querySelectorAll('.sxapi-nav a[href^="#"]').forEach(function (a) { links[a.getAttribute('href').slice(1)] = a; });
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (!en.isIntersecting || !links[en.target.id]) return;
                Object.keys(links).forEach(function (k) { links[k].classList.toggle('on', k === en.target.id); });
                var a = links[en.target.id], nav = a.parentElement;
                if (nav.scrollWidth > nav.clientWidth) nav.scrollLeft = a.offsetLeft - 16;
            });
        }, { rootMargin: '-20% 0px -70% 0px' });
        root.querySelectorAll('.sxapi-sec[id]').forEach(function (s) { io.observe(s); });
    }
})();
</script>
