@extends('layouts.sellerdash')
@section('content')
@php
    // Seller > API: this account's own API key, who it belongs to, usage, and a quick start for API v1.
    $s = \Illuminate\Support\Facades\Auth::guard('seller')->user();
    $key = (string) ($s->api_token ?? '');
    $legacy = $key !== '' && class_exists(\App\Http\Middleware\ApiV1Auth::class) && \App\Http\Middleware\ApiV1Auth::isLegacyKey($key);
    $base = rtrim(url('/api'), '/') . '/v1';
    $docs = \Illuminate\Support\Facades\Route::has('api.v1.docs') ? route('api.v1.docs') : url('/developers');
    $postman = \Illuminate\Support\Facades\Route::has('api.v1.postman') ? route('api.v1.postman') : url('/developers/postman.json');
    $regen = \Illuminate\Support\Facades\Route::has('seller.api.token') ? route('seller.api.token') : null;

    $usage = ['day' => 0, 'week' => 0, 'errors' => 0, 'last' => null, 'recent' => collect()];
    try {
        if ($s && \Illuminate\Support\Facades\Schema::hasTable('api_v1_logs')) {
            $q = fn () => \Illuminate\Support\Facades\DB::table('api_v1_logs')->where('seller_id', $s->id);
            $usage['day'] = $q()->where('created_at', '>=', now()->subDay())->count();
            $usage['week'] = $q()->where('created_at', '>=', now()->subDays(7))->count();
            $usage['errors'] = $q()->where('created_at', '>=', now()->subDays(7))->where('status', '>=', 400)->count();
            $usage['last'] = $q()->max('created_at');
            $usage['recent'] = $q()->orderByDesc('id')->limit(10)->get(['method', 'path', 'status', 'duration_ms', 'ip', 'created_at']);
        }
    } catch (\Throwable $e) {
    }
    $hook = null;
    try {
        if ($s && \Illuminate\Support\Facades\Schema::hasTable('webhooks')) {
            $hook = \App\Models\Webhook::where('seller_id', $s->id)->where('status', 1)->orderByDesc('id')->value('webhook_url');
        }
    } catch (\Throwable $e) {
    }
    $masked = $key !== '' ? substr($key, 0, 8) . str_repeat('•', max(0, strlen($key) - 12)) . substr($key, -4) : '';
@endphp
<style>
.sxk{--sxk-line:rgba(127,140,170,.22);--sxk-soft:rgba(127,140,170,.08);--sxk-mut:#7d879c;--sxk-pri:#3b7bff;--sxk-ok:#1f9d5c;--sxk-warn:#c47a10;--sxk-bad:#d64545}
.sxk *{box-sizing:border-box}.sxk{max-width:1100px}
.sxk h1{font-size:24px;font-weight:700;margin:0 0 4px}.sxk h2{font-size:16px;font-weight:700;margin:0 0 4px}
.sxk .mut{color:var(--sxk-mut)}.sxk .lead{color:var(--sxk-mut);margin:0 0 18px;max-width:760px}
.sxk .card2{border:1px solid var(--sxk-line);border-radius:14px;padding:18px 20px;margin:0 0 16px;background:var(--sxk-soft)}
.sxk .row2{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
.sxk .keybox{flex:1 1 360px;min-width:0;font:600 14px/1.4 "JetBrains Mono",ui-monospace,Menlo,monospace;padding:11px 14px;border-radius:10px;border:1px solid var(--sxk-line);background:rgba(0,0,0,.18);word-break:break-all}
.sxk .btn2{display:inline-flex;align-items:center;gap:6px;font:600 13px/1 inherit;padding:10px 14px;border-radius:9px;border:1px solid var(--sxk-line);background:transparent;color:inherit;cursor:pointer;text-decoration:none;white-space:nowrap}
.sxk .btn2:hover{border-color:var(--sxk-pri)}.sxk .btn2.pri{background:var(--sxk-pri);border-color:var(--sxk-pri);color:#fff}
.sxk .btn2.bad{color:var(--sxk-bad);border-color:rgba(214,69,69,.45)}
.sxk .stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.sxk .stat{border:1px solid var(--sxk-line);border-radius:12px;padding:12px 14px}.sxk .stat b{display:block;font-size:20px;margin-top:2px}
.sxk .note{border-radius:10px;padding:10px 14px;margin:12px 0 0;font-size:13.5px}
.sxk .note.ok{background:rgba(31,157,92,.12);color:var(--sxk-ok)}.sxk .note.warn{background:rgba(196,122,16,.13);color:var(--sxk-warn)}
.sxk pre{margin:10px 0 0;padding:12px 14px;border-radius:10px;background:rgba(0,0,0,.22);border:1px solid var(--sxk-line);overflow:auto;font:12.5px/1.55 "JetBrains Mono",ui-monospace,Menlo,monospace;white-space:pre}
.sxk table{width:100%;border-collapse:collapse;font-size:13px}.sxk th,.sxk td{text-align:left;padding:8px 6px;border-bottom:1px solid var(--sxk-line)}
.sxk th{color:var(--sxk-mut);font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.04em}
.sxk code{font:12.5px "JetBrains Mono",ui-monospace,Menlo,monospace}.sxk .st{font-weight:700}.sxk .st.ok{color:var(--sxk-ok)}.sxk .st.bad{color:var(--sxk-bad)}
.sxk ol{margin:8px 0 0;padding-left:20px}.sxk ol li{margin:4px 0}
.sxk dialog{border:1px solid var(--sxk-line);border-radius:14px;padding:20px;max-width:420px;color:inherit;background:#141b2d}
.sxk dialog::backdrop{background:rgba(0,0,0,.55)}
[data-pc-theme=light] .sxk .keybox,[data-pc-theme=light] .sxk pre{background:#f4f6fb}
[data-pc-theme=light] .sxk dialog{background:#fff}
@media (max-width:760px){.sxk .stats{grid-template-columns:repeat(2,minmax(0,1fr))}.sxk .tbl{overflow-x:auto}}
</style>
<div class="pc-container">
<div class="pc-content">
<div class="sxk">
    <h1>API</h1>
    <p class="lead">Connect your store, ERP or app to Shipxpeed. Your API key is tied to this account: rates come from your rate card, bookings use your wallet, and you only see your own shipments.</p>

    {{-- ===================== key ===================== --}}
    <div class="card2" id="credentials">
        <h2>Your API key</h2>
        <p class="mut" style="margin:0 0 12px">Belongs to <b>{{ $s->name }}</b> · client ID <b>{{ $s->id }}</b>. Keep it on your server; never put it in a website or mobile app.</p>

        @if ($key !== '' && !$legacy)
            <div class="row2">
                <div class="keybox" id="sxkKey" data-full="{{ $key }}" data-masked="{{ $masked }}">{{ $masked }}</div>
                <button type="button" class="btn2" id="sxkShow">Show</button>
                <button type="button" class="btn2 pri" id="sxkCopy">Copy key</button>
                @if ($regen)<button type="button" class="btn2 bad" id="sxkRegen">Regenerate</button>@endif
            </div>
        @elseif ($legacy)
            <div class="note warn" style="margin-top:0">Your current key is an old-style key and does not work with API v1. Generate a new key below; then update it in every integration that uses this account.</div>
            @if ($regen)
                <form method="POST" action="{{ $regen }}" style="margin-top:12px">@csrf<button class="btn2 pri" type="submit">Generate new key</button></form>
            @endif
        @else
            <p style="margin:0 0 10px">No key yet.</p>
            @if ($regen)
                <form method="POST" action="{{ $regen }}">@csrf<button class="btn2 pri" type="submit">Generate my API key</button></form>
            @endif
        @endif

        @if (session('api_token_status'))
            <div class="note ok">{{ session('api_token_status') }}</div>
        @endif
    </div>

    {{-- ===================== usage ===================== --}}
    <div class="card2">
        <h2>Usage</h2>
        <div class="stats" style="margin-top:10px">
            <div class="stat"><span class="mut">Calls, last 24 h</span><b>{{ number_format($usage['day']) }}</b></div>
            <div class="stat"><span class="mut">Calls, last 7 days</span><b>{{ number_format($usage['week']) }}</b></div>
            <div class="stat"><span class="mut">Errors, last 7 days</span><b>{{ number_format($usage['errors']) }}</b></div>
            <div class="stat"><span class="mut">Last call</span><b style="font-size:15px">{{ $usage['last'] ? \Illuminate\Support\Carbon::parse($usage['last'])->diffForHumans() : 'Never' }}</b></div>
        </div>
        @if ($usage['recent']->isNotEmpty())
            <div class="tbl" style="margin-top:14px">
                <table>
                    <tr><th>Time</th><th>Call</th><th>Result</th><th>Took</th><th>From IP</th></tr>
                    @foreach ($usage['recent'] as $r)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('d M, H:i:s') }}</td>
                            <td><code>{{ $r->method }} /{{ preg_replace('#^api/#', '', $r->path) }}</code></td>
                            <td><span class="st {{ (int) $r->status < 400 ? 'ok' : 'bad' }}">{{ $r->status }}</span></td>
                            <td>{{ $r->duration_ms }} ms</td>
                            <td>{{ $r->ip }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
        <p class="mut" style="margin:12px 0 0">Status updates: {!! $hook ? 'sent to <code>' . e($hook) . '</code>' : 'no webhook URL yet. Set one with <code>POST /v1/webhooks</code>.' !!}</p>
    </div>

    {{-- ===================== quick start ===================== --}}
    <div class="card2">
        <h2>Quick start</h2>
        <div class="row2" style="margin-top:6px">
            <span class="mut">Base URL</span><code>{{ $base }}</code>
            <a class="btn2 pri" href="{{ $docs }}" target="_blank" rel="noopener">Full documentation</a>
            <a class="btn2" href="{{ $postman }}">Postman collection</a>
        </div>
        <ol>
            <li>Check your key: <code>GET /v1/account</code> answers with this account's name and wallet balance.</li>
            <li>Get prices: <code>POST /v1/b2c/rates</code> lists every courier live on your account with your price.</li>
            <li>Book: <code>POST /v1/b2c/shipments</code> with the chosen <code>courier_id</code> returns the AWB.</li>
            <li>Print: <code>GET /v1/shipments/{awb}/label</code>. Track: <code>GET /v1/track/{awb}</code>.</li>
        </ol>
        <pre>curl {{ $base }}/account -H 'X-API-Key: YOUR_API_KEY'</pre>
    </div>

    @if ($regen && $key !== '' && !$legacy)
        <dialog id="sxkDlg">
            <h2>Regenerate API key?</h2>
            <p class="mut">The current key stops working immediately. Every store, app or ERP using it must be updated with the new key.</p>
            <form method="POST" action="{{ $regen }}" class="row2" style="justify-content:flex-end;margin-top:14px">
                @csrf
                <button type="button" class="btn2" id="sxkCancel">Cancel</button>
                <button type="submit" class="btn2 bad">Regenerate</button>
            </form>
        </dialog>
    @endif
</div>
</div>
</div>
<script>
(function () {
    var box = document.getElementById('sxkKey');
    var show = document.getElementById('sxkShow'), copy = document.getElementById('sxkCopy');
    if (show && box) show.addEventListener('click', function () {
        var shown = box.textContent === box.dataset.full;
        box.textContent = shown ? box.dataset.masked : box.dataset.full;
        show.textContent = shown ? 'Show' : 'Hide';
    });
    if (copy && box) copy.addEventListener('click', function () {
        var done = function () { copy.textContent = 'Copied'; setTimeout(function () { copy.textContent = 'Copy key'; }, 1500); };
        if (navigator.clipboard) { navigator.clipboard.writeText(box.dataset.full).then(done, function () {}); }
        else { var t = document.createElement('textarea'); t.value = box.dataset.full; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); done(); } catch (e) {} t.remove(); }
    });
    var dlg = document.getElementById('sxkDlg'), regen = document.getElementById('sxkRegen'), cancel = document.getElementById('sxkCancel');
    if (regen && dlg) regen.addEventListener('click', function () { dlg.showModal ? dlg.showModal() : dlg.setAttribute('open', ''); });
    if (cancel && dlg) cancel.addEventListener('click', function () { dlg.close ? dlg.close() : dlg.removeAttribute('open'); });
})();
</script>
@endsection
