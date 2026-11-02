@php
    $t = $track;
    $stageIdx = $t ? array_search($t['stage'], \App\Services\Tracking\PublicTracker::STAGES, true) : false;
    $exception = $t && $stageIdx === false && $t['stage'] !== 'booked';
    if ($t && $exception) {
        // place exceptions on the stepper where they happened
        $stageIdx = match ($t['stage']) { 'ndr' => 3, 'rto', 'rto_delivered', 'lost' => 2, default => 0 };
    }
    $tone = !$t ? '' : match ($t['stage']) {
        'delivered' => 'ok', 'ndr', 'lost' => 'warn', 'rto', 'rto_delivered', 'cancelled' => 'bad', 'ofd' => 'hot', default => 'info',
    };
    $final = $t && in_array($t['stage'], \App\Services\Tracking\PublicTracker::FINAL, true);
    $steps = ['Booked', 'Picked up', 'In transit', 'Out for delivery', 'Delivered'];
    $groups = [];
    foreach (($t['events'] ?? []) as $i => $e) {
        $day = $e['time'] ? ($e['time']->isToday() ? 'Today' : ($e['time']->isYesterday() ? 'Yesterday' : $e['time']->format('D, j M Y'))) : 'Earlier';
        $groups[$day][] = $e + ['i' => $i];
    }
    $total = count($t['events'] ?? []);
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $t ? $t['status_label'] . ' · AWB ' . $t['awb'] : 'Track your order' }} | Shipxpeed</title>
<meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#070b17; --bg2:#0d1326; --card:#111a31; --card2:#16213d; --line:#223056; --line2:#2b3b66;
  --text:#eef2ff; --muted:#93a0c4; --dim:#6b7899;
  --accent:#ffb020; --accent2:#ff8a00; --violet:#6d5dfc;
  --ok:#22c55e; --warn:#f59e0b; --bad:#ef4444; --info:#60a5fa; --hot:#ffb020;
}
*{box-sizing:border-box}
html,body{margin:0}
body{background:var(--bg);color:var(--text);font:15px/1.55 "Plus Jakarta Sans",system-ui,-apple-system,Segoe UI,Roboto,sans-serif;-webkit-font-smoothing:antialiased;min-height:100vh}
body:before{content:"";position:fixed;inset:-20% -10% auto;height:620px;background:radial-gradient(600px 300px at 20% 30%,rgba(109,93,252,.28),transparent 70%),radial-gradient(500px 260px at 85% 10%,rgba(255,176,32,.12),transparent 70%);pointer-events:none;z-index:0}
a{color:inherit}
.wrap{position:relative;z-index:1;max-width:980px;margin:0 auto;padding:0 16px}
header.top{display:flex;align-items:center;justify-content:space-between;padding:18px 0}
.logo{display:flex;align-items:center;gap:10px;text-decoration:none;font-weight:800;font-size:18px;letter-spacing:-.01em}
.logo img{height:34px;width:auto}
.top a.home{color:var(--muted);text-decoration:none;font-size:14px;font-weight:600}
.top a.home:hover{color:var(--text)}

.search{margin:22px 0 26px;text-align:center}
.search h1{font-size:clamp(26px,4.4vw,40px);line-height:1.15;margin:0 0 8px;letter-spacing:-.02em;font-weight:800}
.search p{margin:0 0 22px;color:var(--muted)}
.search form{display:flex;gap:8px;max-width:620px;margin:0 auto;background:var(--card);border:1px solid var(--line);border-radius:16px;padding:6px;box-shadow:0 20px 50px -20px rgba(0,0,0,.6)}
.search input{flex:1;min-width:0;background:transparent;border:0;color:var(--text);font:inherit;font-size:16px;padding:12px 14px;outline:none}
.search input::placeholder{color:var(--dim)}
.btn{appearance:none;border:0;cursor:pointer;font:inherit;font-weight:700;border-radius:12px;padding:12px 22px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#1b1200;white-space:nowrap;transition:transform .15s,filter .15s}
.btn:hover{filter:brightness(1.06)} .btn:active{transform:translateY(1px)}
.btn.ghost{background:var(--card2);color:var(--text);border:1px solid var(--line2)}

.alert{max-width:620px;margin:0 auto 22px;padding:12px 16px;border-radius:12px;font-size:14px;border:1px solid}
.alert.err{background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.35);color:#fecaca}
.alert.ok{background:rgba(34,197,94,.08);border-color:rgba(34,197,94,.35);color:#bbf7d0}

.card{background:linear-gradient(180deg,var(--card),#0f172c);border:1px solid var(--line);border-radius:20px;padding:22px}
.grid{display:grid;grid-template-columns:1.55fr 1fr;gap:18px;align-items:start;grid-template-areas:"status notify" "history details"}
.a-status{grid-area:status}.a-notify{grid-area:notify}.a-history{grid-area:history}.a-details{grid-area:details}
@media (max-width:820px){.grid{grid-template-columns:1fr;grid-template-areas:"status" "notify" "details" "history"}}
.grid:not(:has(.a-notify)){grid-template-areas:"status details" "history details"}
@media (max-width:820px){.grid:not(:has(.a-notify)){grid-template-areas:"status" "details" "history"}}

.status-card{position:relative;overflow:hidden}
.status-card:after{content:"";position:absolute;right:-80px;top:-80px;width:240px;height:240px;border-radius:50%;background:var(--glow,rgba(96,165,250,.14));filter:blur(10px)}
.from{color:var(--muted);font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.06em}
.from b{color:var(--text);text-transform:none;letter-spacing:0;font-size:14px}
.st{display:flex;align-items:center;gap:14px;margin:14px 0 6px}
.st .ico{width:52px;height:52px;border-radius:14px;display:grid;place-items:center;flex:none;background:var(--tbg);color:var(--tc)}
.st .ico svg{width:26px;height:26px}
.st h2{margin:0;font-size:clamp(24px,3.6vw,32px);letter-spacing:-.02em;line-height:1.1}
.note{margin:10px 0 0;font-size:16px;color:#d6dcf2}
.meta{display:flex;flex-wrap:wrap;gap:6px 16px;margin-top:12px;color:var(--muted);font-size:13.5px}
.meta span{display:inline-flex;align-items:center;gap:6px}
.edd{display:inline-flex;align-items:center;gap:8px;margin-top:16px;padding:8px 12px;border-radius:10px;background:rgba(255,176,32,.1);border:1px solid rgba(255,176,32,.3);color:#ffd68a;font-weight:600;font-size:14px}
.tone-ok{--tc:var(--ok);--tbg:rgba(34,197,94,.14);--glow:rgba(34,197,94,.16)}
.tone-warn{--tc:var(--warn);--tbg:rgba(245,158,11,.14);--glow:rgba(245,158,11,.16)}
.tone-bad{--tc:var(--bad);--tbg:rgba(239,68,68,.14);--glow:rgba(239,68,68,.16)}
.tone-hot{--tc:var(--hot);--tbg:rgba(255,176,32,.14);--glow:rgba(255,176,32,.18)}
.tone-info{--tc:var(--info);--tbg:rgba(96,165,250,.14);--glow:rgba(96,165,250,.14)}

/* stepper */
.steps{display:grid;grid-template-columns:repeat(5,1fr);margin:26px 0 4px;position:relative}
.step{text-align:center;position:relative;font-size:12.5px;color:var(--dim);font-weight:600}
.step:before{content:"";position:absolute;top:13px;left:-50%;width:100%;height:3px;background:var(--line);z-index:0}
.step:first-child:before{display:none}
.step.done:before,.step.now:before{background:linear-gradient(90deg,var(--tc),var(--tc))}
.dot{position:relative;z-index:1;width:28px;height:28px;margin:0 auto 8px;border-radius:50%;display:grid;place-items:center;background:var(--bg2);border:2px solid var(--line2)}
.step.done .dot{background:var(--tc);border-color:var(--tc);color:#08101f}
.step.now .dot{background:var(--bg2);border-color:var(--tc);box-shadow:0 0 0 6px color-mix(in srgb,var(--tc) 20%,transparent)}
.step.now .dot:after{content:"";width:10px;height:10px;border-radius:50%;background:var(--tc);animation:pulse 1.6s infinite}
.step.done,.step.now{color:var(--text)}
.step.exc .dot{border-color:var(--tc);background:var(--tc);color:#08101f}
.dot svg{width:14px;height:14px}
@keyframes pulse{50%{transform:scale(.6);opacity:.6}}
@media (max-width:520px){.step{font-size:10.5px}.dot{width:24px;height:24px}.step:before{top:11px}}

/* details */
.side{display:flex;flex-direction:column;gap:18px}
.kv{display:grid;gap:14px}
.kv div{display:flex;flex-direction:column;gap:2px}
.kv small{color:var(--dim);font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
.kv b{font-weight:600;font-size:15px;display:flex;align-items:center;gap:8px;word-break:break-all}
.courier-logo{width:28px;height:28px;border-radius:7px;object-fit:contain;background:#fff;padding:2px}
.copy{background:none;border:1px solid var(--line2);color:var(--muted);border-radius:8px;padding:2px 8px;font:inherit;font-size:12px;cursor:pointer}
.copy:hover{color:var(--text)}

/* notify */
.notify h3{margin:0 0 4px;font-size:17px;display:flex;align-items:center;gap:8px}
.notify p{margin:0 0 14px;color:var(--muted);font-size:13.5px}
.notify form{display:flex;flex-direction:column;gap:10px}
.notify input[type=email]{background:var(--bg2);border:1px solid var(--line2);border-radius:12px;color:var(--text);font:inherit;padding:12px 14px;outline:none}
.notify input[type=email]:focus{border-color:var(--accent)}
.notify .alert{margin:0 0 12px}
.hp{position:absolute;left:-9999px;opacity:0}

/* timeline */
.tl-head{display:flex;align-items:center;justify-content:space-between;margin:0 0 10px;gap:12px}
.tl-head h3{margin:0;font-size:18px}
.tl-head a{color:var(--muted);font-size:13px;text-decoration:none;font-weight:600}
.tl-head a:hover{color:var(--text)}
.day{margin:18px 0 6px;font-size:12px;font-weight:700;color:var(--dim);text-transform:uppercase;letter-spacing:.07em}
.ev{display:grid;grid-template-columns:62px 18px 1fr;gap:0 12px;position:relative}
.ev .tm{color:var(--muted);font-size:13px;text-align:right;padding-top:1px;font-variant-numeric:tabular-nums}
.ev .rail{position:relative;display:flex;justify-content:center}
.ev .rail:before{content:"";position:absolute;top:0;bottom:0;width:2px;background:var(--line)}
.ev .rail i{position:relative;margin-top:5px;width:10px;height:10px;border-radius:50%;background:var(--line2);border:2px solid var(--card)}
.ev.first .rail i{background:var(--tc);box-shadow:0 0 0 4px color-mix(in srgb,var(--tc) 22%,transparent)}
.ev .bd{padding-bottom:16px}
.ev .s{font-weight:700}
.ev.first .s{color:var(--tc)}
.ev .r{color:#c9d1ea;font-size:14px;margin-top:2px}
.ev .l{color:var(--dim);font-size:13px;margin-top:2px;display:flex;align-items:center;gap:4px}
.ev.more,.more-day{display:none} .showall .ev.more{display:grid} .showall .more-day{display:block}
.empty{color:var(--muted);padding:12px 0}
.footer{color:var(--dim);font-size:12.5px;text-align:center;padding:30px 0 40px}
.footer a{color:var(--muted)}
.welcome{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;max-width:820px;margin:8px auto 0}
.welcome div{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px;text-align:left}
.welcome b{display:block;margin-bottom:4px}
.welcome span{color:var(--muted);font-size:13.5px}
@media (max-width:720px){.welcome{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">
    <header class="top">
        <a class="logo" href="{{ url('/') }}">
            <img src="{{ asset('assets/website/img/newlogo.PNG') }}" alt="Shipxpeed" onerror="this.remove()">
        </a>
        <a class="home" href="{{ url('/') }}">shipxpeed.tech →</a>
    </header>

    <section class="search">
        @if(!$t)
            <h1>Track your order</h1>
            <p>Enter the AWB number from your shipping SMS or email, or your Order ID.</p>
        @endif
        <form action="{{ route('order.details') }}" method="GET" role="search">
            <input type="text" name="awb" value="{{ $q }}" placeholder="AWB number or Order ID" aria-label="AWB number or Order ID" required autocomplete="off" @if(!$t) autofocus @endif>
            <button class="btn" type="submit">Track</button>
        </form>
    </section>

    @if($error)
        <div class="alert err">{{ $error }}</div>
    @endif
    @if(!$t && session('notify_ok'))
        <div class="alert ok">{{ session('notify_ok') }}</div>
    @endif

    @if(!$t && !$error)
        <div class="welcome">
            <div><b>Every courier</b><span>Delhivery, Blue Dart, Xpressbees, Ekart, DTDC and more — one page.</span></div>
            <div><b>Exact courier remarks</b><span>See every scan with its time and location.</span></div>
            <div><b>Email updates</b><span>Get the latest status in your inbox every 24 hours.</span></div>
        </div>
    @endif

    @if($t)
    <div class="grid tone-{{ $tone }}">
            <section class="card status-card a-status">
                <div class="from">Order from <b>{{ $t['brand'] }}</b> · #{{ ltrim($t['order_no'], '# ') }}</div>
                <div class="st">
                    <div class="ico">
                        @switch($t['stage'])
                            @case('delivered')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>@break
                            @case('ofd')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>@break
                            @case('ndr')@case('lost')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>@break
                            @case('rto')@case('rto_delivered')@case('cancelled')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg>@break
                            @default<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 8-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8M12 13v8"/></svg>
                        @endswitch
                    </div>
                    <h2>{{ $t['status_label'] }}</h2>
                </div>
                <p class="note">{{ $t['status_note'] }}</p>
                <div class="meta">
                    @if(!empty($t['events'][0]['location']))
                        <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ $t['events'][0]['location'] }}</span>
                    @endif
                    @if($t['updated_at'])
                        <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ $t['stage'] === 'delivered' ? 'Delivered' : 'Updated' }} {{ $t['updated_at']->format('j M, g:i A') }}</span>
                    @endif
                </div>
                @if($t['edd'] && !$final)
                    <div class="edd">Expected delivery: {{ $t['edd']->format('D, j M') }}</div>
                @endif

                <div class="steps">
                    @foreach($steps as $i => $label)
                        @php
                            $cls = $i < $stageIdx || ($t['stage'] === 'delivered' && $i === 4) ? 'done' : ($i === $stageIdx ? ($exception ? 'exc' : 'now') : '');
                            if ($exception && $i === $stageIdx) { $label = $t['status_label']; }
                        @endphp
                        <div class="step {{ $cls }}">
                            <div class="dot">
                                @if($cls === 'done')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>@endif
                                @if($cls === 'exc')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"><path d="M12 7v6M12 17h.01"/></svg>@endif
                            </div>
                            {{ $label }}
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="card a-history" id="history">
                <div class="tl-head">
                    <h3>Tracking history</h3>
                    <a href="{{ route('order.details', ['awb' => $t['awb'] ?: $q, 'refresh' => 1]) }}">↻ Refresh</a>
                </div>
                @if($total)
                    <div id="tl">
                        @foreach($groups as $day => $events)
                            <div class="day {{ $events[0]['i'] >= 8 ? 'more-day' : '' }}">{{ $day }}</div>
                            @foreach($events as $e)
                                <div class="ev {{ $e['i'] === 0 ? 'first' : '' }} {{ $e['i'] >= 8 ? 'more' : '' }}">
                                    <div class="tm">{{ $e['time'] ? $e['time']->format('g:i A') : '' }}</div>
                                    <div class="rail"><i></i></div>
                                    <div class="bd">
                                        <div class="s">{{ $e['status'] }}</div>
                                        @if($e['remark'] !== '')<div class="r">{{ $e['remark'] }}</div>@endif
                                        @if($e['location'] !== '')<div class="l">{{ $e['location'] }}</div>@endif
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                    @if($total > 8)
                        <button class="btn ghost" style="width:100%;margin-top:6px" onclick="document.getElementById('tl').classList.add('showall');this.remove()">Show all {{ $total }} updates</button>
                    @endif
                @else
                    <p class="empty">{{ $t['awb'] ? 'The courier has not shared any scans yet. Updates usually appear within a few hours of pickup.' : 'Tracking starts once the order is shipped.' }}</p>
                @endif
            </section>

            @if($t['awb'] && !$final)
            <section class="card notify a-notify" id="notify">
                <h3><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><path d="m4 6 8 7 8-7"/></svg>Get updates by email</h3>
                <p>We'll email you the latest courier remarks every 24 hours until your order is delivered.</p>
                @if(session('notify_ok'))<div class="alert ok">{{ session('notify_ok') }}</div>@endif
                @if(session('notify_error'))<div class="alert err">{{ session('notify_error') }}</div>@endif
                @if(isset($errors) && $errors->has('email'))<div class="alert err">{{ $errors->first('email') }}</div>@endif
                <form method="POST" action="{{ route('order.details.notify') }}">
                    @csrf
                    <input type="hidden" name="awb" value="{{ $t['awb'] }}">
                    <input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <input type="email" name="email" placeholder="you@example.com" required autocomplete="email" value="{{ old('email') }}" aria-label="Email address">
                    <button class="btn" type="submit">Notify me</button>
                </form>
            </section>
            @elseif($final && session('notify_ok'))
                <div class="alert ok a-notify" style="margin:0">{{ session('notify_ok') }}</div>
            @endif

            <section class="card a-details">
                <div class="kv">
                    @if($t['awb'])
                    <div><small>AWB number</small><b><span id="awb">{{ $t['awb'] }}</span> <button class="copy" type="button" onclick="navigator.clipboard&&navigator.clipboard.writeText('{{ $t['awb'] }}').then(()=>{this.textContent='Copied'})">Copy</button></b></div>
                    @endif
                    <div><small>Courier</small><b>@if($t['courier_logo'])<img class="courier-logo" src="{{ asset($t['courier_logo']) }}" alt="" onerror="this.remove()">@endif{{ $t['courier'] }}</b></div>
                    @if($t['destination'])<div><small>Delivering to</small><b>{{ $t['destination'] }}</b></div>@endif
                    <div><small>Payment</small><b>{{ $t['payment'] }}</b></div>
                    @if($t['booked_at'])<div><small>Order booked</small><b>{{ $t['booked_at']->format('j M Y') }}</b></div>@endif
                </div>
                <button class="btn ghost" type="button" style="width:100%;margin-top:18px" onclick="(navigator.share?navigator.share({title:'Track my order',url:location.href.split('#')[0].replace(/[?&]refresh=1/,'')}):navigator.clipboard.writeText(location.href)).then(()=>{this.textContent='Link ready'}).catch(()=>{})">Share tracking link</button>
            </section>
    </div>
    @endif

    <div class="footer">Shipping powered by <a href="{{ url('/') }}">Shipxpeed</a> · Times shown in IST</div>
</div>
</body>
</html>
