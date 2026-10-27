@extends('layouts.sellerdash')

@section('content')
@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $logLabels = [
        'sent' => ['Sent', 'ok'], 'sending' => ['Sending', 'muted'], 'failed' => ['Failed', 'bad'],
        'skipped_no_email' => ['No buyer email', 'warn'], 'skipped_low_balance' => ['Low wallet', 'warn'],
    ];
@endphp
<style>
.nf{--nf-wa:#25d366;--nf-wa-d:#16a34a;--nf-bg:var(--sx-surface,#0f1626);--nf-raised:var(--sx-raised,#152036);--nf-line:var(--sx-line,rgba(196,206,235,.09));--nf-line2:var(--sx-line-2,rgba(196,206,235,.16));--nf-fg:var(--sx-fg,#e9ecf5);--nf-fg2:var(--sx-fg-2,#a3abc2);--nf-fg3:var(--sx-fg-3,#6d7690);--nf-blue:var(--sx-blue,#3b7bff);--nf-blue-bg:var(--sx-blue-bg,rgba(59,123,255,.14));color:var(--nf-fg)}
.nf *{box-sizing:border-box}
.nf-crumb{font-size:13px;color:var(--nf-fg3);margin:0 0 14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.nf-crumb a{color:var(--nf-blue);text-decoration:none}
.nf-wrap{display:grid;grid-template-columns:230px 1fr;gap:18px;align-items:start}
@media (max-width:991px){.nf-wrap{grid-template-columns:1fr}}
.nf-side{background:var(--nf-bg);border:1px solid var(--nf-line);border-radius:12px;padding:14px 8px}
.nf-side h6{font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--nf-fg3);margin:4px 12px 10px;font-weight:700}
.nf-side a{display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:8px;font-size:14px;color:var(--nf-fg2);text-decoration:none}
.nf-side a:hover{background:var(--nf-raised);color:var(--nf-fg)}
.nf-side a.on{background:var(--nf-blue-bg);color:var(--nf-blue);font-weight:600}
.nf-main{background:var(--nf-bg);border:1px solid var(--nf-line);border-radius:12px;min-width:0}
.nf-tabs{display:flex;gap:6px;border-bottom:1px solid var(--nf-line);padding:0 18px;overflow-x:auto}
.nf-tabs button{background:none;border:0;border-bottom:3px solid transparent;padding:16px 14px 13px;font-size:15px;font-weight:500;color:var(--nf-fg2);white-space:nowrap}
.nf-tabs button.on{color:var(--nf-blue);border-bottom-color:var(--nf-blue);font-weight:600}
.nf-pane{padding:20px}
.nf-pane[hidden]{display:none}
/* WhatsApp hero */
.nf-hero{display:grid;grid-template-columns:1.15fr .85fr;gap:24px;align-items:center;padding:30px;border-radius:14px;background:linear-gradient(115deg,rgba(124,58,237,.10),rgba(59,123,255,.06) 45%,rgba(37,211,102,.12));border:1px solid var(--nf-line)}
@media (max-width:991px){.nf-hero{grid-template-columns:1fr;padding:22px}}
.nf-hero h1{font-size:34px;line-height:1.15;font-weight:800;margin:0 0 12px;color:var(--nf-fg)}
.nf-hero h1 span{background:linear-gradient(90deg,#6d6bff,#22c55e);-webkit-background-clip:text;background-clip:text;color:transparent}
@media (max-width:575px){.nf-hero h1{font-size:27px}}
.nf-hero p.lead{font-size:15px;color:var(--nf-fg2);margin:0 0 18px;max-width:520px;line-height:1.55}
.nf-dash{border:1.5px dashed rgba(109,107,255,.55);border-radius:10px;padding:13px 16px;font-size:14.5px;line-height:1.55;max-width:520px;margin-bottom:22px;color:var(--nf-fg)}
.nf-dash b{color:#22c55e}
.nf-price{display:flex;align-items:center;gap:14px;max-width:470px;padding:14px 18px;border-radius:10px;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.25);margin-bottom:8px}
.nf-price .ic{width:42px;height:42px;border-radius:10px;background:rgba(34,197,94,.2);display:grid;place-items:center;font-size:22px;color:#22c55e;flex:none}
.nf-price .amt{font-size:28px;font-weight:800;color:#22c55e;line-height:1}
.nf-price .per{font-size:15px;color:var(--nf-fg)}
.nf-note{font-size:12px;color:var(--nf-fg3);margin:0 0 22px}
.nf-btns{display:flex;gap:14px;flex-wrap:wrap;align-items:center}
.nf-btn{height:48px;min-width:170px;padding:0 22px;border-radius:9px;font-size:16px;font-weight:600;display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1.5px solid transparent;cursor:pointer;text-decoration:none;transition:filter .15s,background .15s}
.nf-btn:disabled{opacity:.55;cursor:not-allowed}
.nf-btn-ghost{background:transparent;border-color:var(--nf-blue);color:var(--nf-blue)}
.nf-btn-ghost:hover{background:var(--nf-blue-bg)}
.nf-btn-wa{background:var(--nf-wa-d);color:#fff}
.nf-btn-wa:hover{filter:brightness(1.08);color:#fff}
.nf-btn-sm{height:34px;min-width:0;padding:0 14px;font-size:13px}
.nf-off{background:none;border:0;color:var(--nf-fg3);font-size:13px;text-decoration:underline;padding:0}
/* phone mockup */
.nf-phone{position:relative;width:250px;margin:0 auto;border-radius:34px;background:#111;padding:10px;box-shadow:0 18px 50px rgba(0,0,0,.35)}
.nf-screen{border-radius:26px;overflow:hidden;background:#e9e3da}
.nf-wabar{background:#075e54;color:#fff;padding:22px 12px 10px;display:flex;align-items:center;gap:8px;font-size:13px}
.nf-wabar .av{width:28px;height:28px;border-radius:50%;background:#25d366;display:grid;place-items:center;font-weight:700;font-size:12px}
.nf-wabar small{display:block;font-size:10px;opacity:.8}
.nf-chat{padding:12px 10px 18px;min-height:300px}
.nf-bub{background:#fff;color:#111;border-radius:0 10px 10px 10px;padding:9px 10px 6px;font-size:12px;line-height:1.45;box-shadow:0 1px 1px rgba(0,0,0,.12);max-width:92%}
.nf-bub .t{font-size:9.5px;color:#999;text-align:right;margin-top:3px}
.nf-bub .cta{display:block;border-top:1px solid #eee;margin-top:7px;padding-top:7px;text-align:center;color:#0a7cff;font-weight:600}
.nf-toast-card{position:absolute;left:-46px;bottom:70px;background:#fff;color:#111;border-radius:12px;padding:10px 12px;display:flex;gap:9px;align-items:center;box-shadow:0 10px 30px rgba(0,0,0,.25);font-size:12px;max-width:230px}
.nf-toast-card i{font-size:22px;color:#25d366}
.nf-toast-card b{display:block;font-size:12.5px}
.nf-toast-card span{color:#16a34a;font-weight:600}
@media (max-width:575px){.nf-toast-card{left:-6px}}
/* cards */
.nf-card{border:1px solid var(--nf-line);border-radius:12px;padding:18px;margin-top:18px;background:var(--nf-raised)}
.nf-card h3{font-size:16px;font-weight:700;margin:0 0 4px;color:var(--nf-fg)}
.nf-card p.sub{font-size:13px;color:var(--nf-fg2);margin:0 0 14px;line-height:1.5}
.nf-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
@media (max-width:575px){.nf-stats{grid-template-columns:1fr}}
.nf-stat{padding:12px 14px;border-radius:10px;background:var(--nf-bg);border:1px solid var(--nf-line)}
.nf-stat b{display:block;font-size:20px;color:var(--nf-fg)}
.nf-stat span{font-size:12px;color:var(--nf-fg3)}
.nf-tbl{width:100%;border-collapse:separate;border-spacing:0;font-size:14px;border:1px solid var(--nf-line2);border-radius:10px;overflow:hidden}
.nf-tbl th,.nf-tbl td{padding:12px 16px;border-bottom:1px solid var(--nf-line);color:var(--nf-fg);vertical-align:middle}
.nf-tbl th{text-align:left}
.nf-tbl thead th{font-weight:600;background:var(--nf-bg)}
.nf-tbl tbody tr:nth-child(even) td{background:rgba(127,140,170,.04)}
.nf-tbl tr:last-child td{border-bottom:0}
.nf-tbl td.c,.nf-tbl th.c{text-align:center;width:90px}
.nf-tbl .muted{color:var(--nf-fg3)}
.nf-chk{width:20px;height:20px;accent-color:#6d6bff;cursor:pointer;vertical-align:middle}
.nf-eye{background:none;border:0;color:#6d6bff;font-size:20px;padding:2px 6px;cursor:pointer;line-height:1}
/* privacy toggles */
.nf-priv{display:flex;justify-content:space-between;align-items:center;gap:20px;padding:20px 22px;border-radius:12px;background:var(--nf-raised);border:1px solid var(--nf-line);margin-bottom:16px}
.nf-priv h4{font-size:16px;font-weight:700;margin:0 0 6px;color:var(--nf-fg)}
.nf-priv p{font-size:13.5px;color:var(--nf-fg2);margin:0;line-height:1.55;max-width:820px}
.nf-sw{position:relative;width:50px;height:28px;flex:none}
.nf-sw input{opacity:0;width:0;height:0;position:absolute}
.nf-sw span{position:absolute;inset:0;border-radius:999px;background:var(--nf-line2);border:1px solid var(--nf-line2);cursor:pointer;transition:background .2s}
.nf-sw span::after{content:"";position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.3);transition:transform .2s}
.nf-sw input:checked+span{background:#6d6bff;border-color:#6d6bff}
.nf-sw input:checked+span::after{transform:translateX(22px)}
.nf-sw input:focus-visible+span{outline:2px solid var(--nf-blue);outline-offset:2px}
.nf-cols{display:grid;grid-template-columns:1fr 1fr;gap:26px;margin-top:10px}
@media (max-width:1199px){.nf-cols{grid-template-columns:1fr}}
.nf-cols h3{font-size:20px;font-weight:700;margin:6px 0 6px;color:var(--nf-fg);display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.nf-cols p.sub{font-size:13.5px;color:var(--nf-fg2);margin:0 0 14px;line-height:1.55;min-height:42px}
.pill{display:inline-block;padding:3px 10px;border-radius:999px;font-size:11.5px;font-weight:600;white-space:nowrap}
.pill.ok{background:var(--sx-ok-bg,rgba(95,211,161,.12));color:var(--sx-ok,#5fd3a1)}
.pill.warn{background:var(--sx-warn-bg,rgba(245,158,11,.14));color:var(--sx-warn,#f5b44a)}
.pill.bad{background:var(--sx-bad-bg,rgba(239,68,68,.12));color:var(--sx-bad,#f87171)}
.pill.muted{background:rgba(127,140,170,.14);color:var(--nf-fg2)}
.pill.blue{background:var(--nf-blue-bg);color:var(--nf-blue)}
.nf-banner{display:flex;gap:10px;align-items:flex-start;padding:11px 14px;border-radius:10px;font-size:13px;line-height:1.5;margin-bottom:14px;background:var(--sx-info-bg,rgba(6,182,212,.12));color:var(--nf-fg)}
.nf-banner i{font-size:18px;color:var(--sx-info,#22d3ee);flex:none}
.nf-banner.warn{background:var(--sx-warn-bg,rgba(245,158,11,.14))}
.nf-banner.warn i{color:var(--sx-warn,#f5b44a)}
.nf-saved{position:fixed;right:22px;bottom:22px;z-index:2000;padding:11px 16px;border-radius:10px;background:#111827;color:#fff;font-size:13.5px;box-shadow:0 10px 30px rgba(0,0,0,.3);display:flex;gap:8px;align-items:center;transform:translateY(20px);opacity:0;transition:.2s;pointer-events:none}
.nf-saved.show{transform:none;opacity:1}
.nf-saved.err{background:#7f1d1d}
/* modal */
.nf-modal{position:fixed;inset:0;z-index:1990;background:rgba(5,10,20,.65);display:flex;align-items:center;justify-content:center;padding:16px}
.nf-modal[hidden]{display:none}
.nf-dlg{width:100%;max-width:640px;max-height:92vh;display:flex;flex-direction:column;background:var(--nf-bg);border:1px solid var(--nf-line2);border-radius:14px;overflow:hidden}
.nf-dlg-h{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--nf-line)}
.nf-dlg-h h5{margin:0;font-size:15.5px;color:var(--nf-fg)}
.nf-dlg-h small{display:block;font-size:12px;color:var(--nf-fg3);font-weight:400;margin-top:2px}
.nf-x{background:none;border:0;font-size:22px;color:var(--nf-fg2);line-height:1;cursor:pointer}
.nf-dlg-b{padding:0;overflow:auto;flex:1;min-height:200px;background:#f2f4f8}
.nf-dlg-b iframe{width:100%;height:62vh;border:0;display:block}
.nf-dlg-f{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:12px 18px;border-top:1px solid var(--nf-line);flex-wrap:wrap}
.nf-dlg-f span{font-size:12.5px;color:var(--nf-fg3)}
.nf-smsbox{padding:28px 18px;background:#e9e3da;min-height:220px}
.nf-smsbox .nf-bub{max-width:100%;font-size:14px;border-radius:12px;background:#fff}
.nf-skel{padding:40px;text-align:center;color:#6b7280;font-size:13px}
</style>

<div class="pc-container nf">
<div class="pc-content">

    <div class="nf-crumb"><a href="{{ url('/seller/dashboard') }}">Dashboard</a><i class="ti ti-chevron-right"></i><span>Tools</span><i class="ti ti-chevron-right"></i><span style="color:var(--nf-fg)">Notify</span></div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="nf-wrap">
        <aside class="nf-side">
            <h6>Buyer communication</h6>
            <a href="{{ route('notifications.index') }}" class="on"><i class="ti ti-bell-ringing"></i> Notify</a>
            @if(Route::has('seller.whatsapp.alerts'))
            <a href="{{ route('seller.whatsapp.alerts') }}"><i class="ti ti-brand-whatsapp"></i> WhatsApp history</a>
            @endif
            <a href="{{ route('notifications.index', ['tab' => 'email']) }}#nfLog" data-go="email"><i class="ti ti-mail"></i> Email activity</a>
        </aside>

        <section class="nf-main">
            <div class="nf-tabs" role="tablist">
                <button type="button" role="tab" data-tab="whatsapp" class="{{ $tab === 'whatsapp' ? 'on' : '' }}" aria-selected="{{ $tab === 'whatsapp' ? 'true' : 'false' }}">WhatsApp Communication</button>
                <button type="button" role="tab" data-tab="email" class="{{ $tab === 'email' ? 'on' : '' }}" aria-selected="{{ $tab === 'email' ? 'true' : 'false' }}">Email &amp; SMS Communication</button>
            </div>

            {{-- ============ WhatsApp ============ --}}
            <div class="nf-pane" id="pane-whatsapp" @if($tab !== 'whatsapp') hidden @endif>
                @if(!$waReady)
                    <div class="nf-banner warn"><i class="ti ti-alert-triangle"></i><div>WhatsApp alerts are being set up on your account. Please check back shortly.</div></div>
                @elseif(!$waPlatform)
                    <div class="nf-banner"><i class="ti ti-info-circle"></i><div>Shipxpeed is finishing the WhatsApp setup. You can activate now; messages start automatically once it's live, and you're only charged for messages actually sent.</div></div>
                @endif

                <div class="nf-hero">
                    <div>
                        <h1>Whatsapp <span>Communication</span></h1>
                        <p class="lead">A seamless tool that sends real-time order tracking updates to your buyers on WhatsApp, under your brand name.</p>
                        <div class="nf-dash">WhatsApp messages get read within minutes, so your buyers stay <b>updated every step of the way</b> and fewer deliveries fail.</div>

                        <div class="nf-price">
                            <div class="ic"><i class="ti ti-currency-rupee"></i></div>
                            <div><span class="amt">₹{{ number_format($waRate, 2) }}*</span> <span class="per">per message</span></div>
                        </div>
                        <p class="nf-note">*Charged from your wallet only when a message is delivered to WhatsApp. Free alerts are marked below.</p>

                        <form method="POST" action="{{ route('seller.whatsapp.alerts.update') }}" class="nf-btns" id="nfWaForm">
                            @csrf
                            <input type="hidden" name="brand_name" value="{{ $brandName }}">
                            <button type="button" class="nf-btn nf-btn-ghost" id="nfDemoBtn">Try Demo</button>
                            @if($waActive)
                                <button type="button" class="nf-btn nf-btn-wa" style="cursor:default"><i class="ti ti-circle-check"></i> Activated</button>
                                <button name="active" value="0" class="nf-off" @disabled(!$waReady) onclick="return confirm('Turn off WhatsApp alerts? Buyers will stop getting updates.')">Turn off</button>
                            @else
                                <button name="active" value="1" class="nf-btn nf-btn-wa" @disabled(!$waReady)><i class="ti ti-bolt"></i> Activate</button>
                            @endif
                        </form>
                    </div>

                    <div style="position:relative;padding:10px 0 10px 40px">
                        <div class="nf-phone" aria-hidden="true">
                            <div class="nf-screen">
                                <div class="nf-wabar"><i class="ti ti-arrow-left"></i><div class="av">{{ strtoupper(mb_substr($demoBrand, 0, 1)) }}</div><div>{{ \Illuminate\Support\Str::limit($demoBrand, 18) }}<small>online</small></div></div>
                                <div class="nf-chat">
                                    <div class="nf-bub">
                                        Hey Ramesh 👋<br>
                                        📦 Your <b>{{ \Illuminate\Support\Str::limit($demoBrand, 20) }}</b> order is out for delivery and will reach you today.<br><br>
                                        Order ID: <b>SPX1001</b><br>Courier: Delhivery
                                        <div class="t">10:42 am</div>
                                        <span class="cta"><i class="ti ti-external-link"></i> Track Now</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="nf-toast-card"><i class="ti ti-brand-whatsapp"></i><div><b>Whatsapp Notification</b><span>On the way</span> | Track your order now</div></div>
                    </div>
                </div>

                <div class="nf-card">
                    <div class="nf-stats">
                        <div class="nf-stat"><b>{{ $waActive ? 'Active' : 'Off' }}</b><span>Service status</span></div>
                        <div class="nf-stat"><b>{{ number_format($waMonth['sent']) }}</b><span>Messages this month</span></div>
                        <div class="nf-stat"><b>₹{{ number_format($waMonth['charged'], 2) }}</b><span>Charged this month</span></div>
                    </div>
                </div>

                <div class="nf-card">
                    <h3>What your buyers receive</h3>
                    <p class="sub">Each status goes out once per shipment (delivery-failed once per day). Messages stop automatically if your wallet can't cover a paid alert.</p>
                    <div class="table-responsive">
                    <table class="nf-tbl">
                        <thead><tr><th>Shipment status</th><th style="text-align:right">Your rate</th></tr></thead>
                        <tbody>
                        @forelse($waEvents as $e)
                            <tr><td>{{ $e['label'] }}</td><td style="text-align:right">@if($e['seller_rate'] > 0) ₹{{ $fmt($e['seller_rate']) }} @else <span class="pill ok">Free</span> @endif</td></tr>
                        @empty
                            <tr><td colspan="2" class="muted">Alerts will be listed here once Shipxpeed finishes the WhatsApp setup.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                    <form method="POST" action="{{ route('seller.whatsapp.alerts.update') }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="active" value="{{ $waActive ? 1 : 0 }}">
                        <label class="small mb-1 d-block" for="nfBrand" style="color:var(--nf-fg2)">Brand name shown to buyers</label>
                        <div class="d-flex gap-2" style="max-width:520px">
                            <input type="text" id="nfBrand" name="brand_name" maxlength="40" class="form-control form-control-sm" value="{{ $brandName }}" placeholder="e.g. {{ $seller->name }}">
                            <button class="nf-btn nf-btn-ghost nf-btn-sm" @disabled(!$waReady)>Save</button>
                        </div>
                        <div class="small mt-1" style="color:var(--nf-fg3)">Leave blank to use your pickup location name. Also used in buyer emails.</div>
                    </form>
                </div>
            </div>

            {{-- ============ Email & SMS ============ --}}
            <div class="nf-pane" id="pane-email" @if($tab !== 'email') hidden @endif>
                @if(!$ready)
                    <div class="nf-banner warn"><i class="ti ti-alert-triangle"></i><div>Email &amp; SMS settings are being set up on your account. Please check back shortly.</div></div>
                @endif

                <div class="nf-priv">
                    <div>
                        <h4>Hide Product Name &amp; Image</h4>
                        <p>Turn the toggle on if you don't want the product name or image to appear in the email or SMS. Recommended for sellers of health, wellness, personal care and similar products.</p>
                    </div>
                    <label class="nf-sw" title="Hide product name & image"><input type="checkbox" data-pref="hide_product" @checked($settings['hide_product']) @disabled(!$ready)><span></span></label>
                </div>
                <div class="nf-priv">
                    <div>
                        <h4>Hide Product Price For Prepaid Orders</h4>
                        <p>Turn the toggle on if you don't want the product price to appear in the email or SMS. Recommended for sellers whose prepaid orders are mostly gift orders.</p>
                    </div>
                    <label class="nf-sw" title="Hide price on prepaid orders"><input type="checkbox" data-pref="hide_price_prepaid" @checked($settings['hide_price_prepaid']) @disabled(!$ready)><span></span></label>
                </div>

                <div class="nf-cols">
                    @foreach(['email' => 'Email', 'sms' => 'SMS'] as $ch => $title)
                    <div>
                        <h3>{{ $title }}
                            @if($ch === 'email')
                                <span class="pill blue">{{ $emailRate > 0 ? '₹' . number_format($emailRate, 2) . ' per email' : 'Free' }}</span>
                            @else
                                <span class="pill {{ $smsLive ? 'ok' : 'warn' }}">{{ $smsLive ? 'Live' : 'Starting soon' }}</span>
                            @endif
                        </h3>
                        <p class="sub">Choose when to send out {{ $ch === 'email' ? 'emails' : 'SMS' }}. Only one {{ $ch === 'email' ? 'email' : 'SMS' }} will be sent out for every delivery status to avoid spamming.</p>
                        @if($ch === 'sms' && !$smsLive)
                            <div class="nf-banner"><i class="ti ti-info-circle"></i><div>SMS goes live once Shipxpeed's SMS gateway (DLT-approved templates) is connected. Your choices are saved now and start working automatically, nothing is charged until then.</div></div>
                        @endif
                        <div class="table-responsive">
                        <table class="nf-tbl" data-ch="{{ $ch }}">
                            <thead><tr>
                                <th>Order Stage</th>
                                <th class="c"><input type="checkbox" class="nf-chk nf-all" aria-label="Select all {{ $title }} stages" @disabled(!$ready)></th>
                                <th class="c">Preview</th>
                            </tr></thead>
                            <tbody>
                            @foreach($stages as $key => [$label])
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="c"><input type="checkbox" class="nf-chk nf-stage" value="{{ $key }}" aria-label="{{ $title }}: {{ $label }}" @checked(in_array($key, $settings[$ch], true)) @disabled(!$ready)></td>
                                    <td class="c"><button type="button" class="nf-eye" data-preview="{{ $ch }}" data-event="{{ $key }}" data-label="{{ $label }}" aria-label="Preview {{ $title }} for {{ $label }}"><i class="ti ti-eye"></i></button></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="nf-card" id="nfLog">
                    <h3>Email activity</h3>
                    <p class="sub">Last 50 buyer emails. Emails go only to buyers whose order has an email address.</p>
                    @if($emailLog->isEmpty())
                        <p class="small mb-0" style="color:var(--nf-fg3)">No emails yet. Tick the stages above and they'll appear here as your shipments move.</p>
                    @else
                    <div class="table-responsive">
                    <table class="nf-tbl">
                        <thead><tr><th>When</th><th>AWB</th><th>Stage</th><th>Buyer</th><th>Result</th><th style="text-align:right">Charge</th></tr></thead>
                        <tbody>
                        @foreach($emailLog as $m)
                            @php [$ml, $mc] = $logLabels[$m->status] ?? [ucfirst(str_replace(['skipped_', '_'], ['', ' '], $m->status)), 'muted']; @endphp
                            <tr>
                                <td style="white-space:nowrap">{{ \Illuminate\Support\Carbon::parse($m->created_at)->format('d M, h:i A') }}</td>
                                <td style="font-family:ui-monospace,Menlo,monospace;font-size:12.5px">{{ $m->awb_number ?: '—' }}</td>
                                <td>{{ $stages[$m->event][0] ?? $m->event }} <span class="muted small">· {{ strtoupper($m->channel) }}</span></td>
                                <td class="muted">{{ $m->recipient ? \Illuminate\Support\Str::mask($m->recipient, '•', 2, max(0, strpos($m->recipient, '@') - 2)) : '—' }}</td>
                                <td><span class="pill {{ $mc }}" @if($m->reason) title="{{ $m->reason }}" @endif>{{ $ml }}</span></td>
                                <td style="text-align:right">{{ $m->charge > 0 ? '₹' . number_format($m->charge, 2) : '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    </div>
                    @endif
                </div>
            </div>
        </section>
    </div>

    {{-- Preview modal --}}
    <div class="nf-modal" id="nfModal" hidden role="dialog" aria-modal="true" aria-labelledby="nfModalTitle">
        <div class="nf-dlg">
            <div class="nf-dlg-h"><h5 id="nfModalTitle">Preview<small id="nfModalSub"></small></h5><button type="button" class="nf-x" id="nfClose" aria-label="Close">&times;</button></div>
            <div class="nf-dlg-b" id="nfModalBody"></div>
            <div class="nf-dlg-f"><span id="nfModalFoot">Shown with your latest shipment. Buyers see their own order details.</span><button type="button" class="nf-btn nf-btn-ghost nf-btn-sm" id="nfTest" hidden><i class="ti ti-send"></i> Send test email to me</button></div>
        </div>
    </div>

    <div class="nf-saved" id="nfSaved" role="status" aria-live="polite"><i class="ti ti-check"></i><span>Saved</span></div>
</div>
</div>

<script>
(function () {
    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

    // tabs
    function show(tab) {
        $$('.nf-tabs button').forEach(function (b) { var on = b.dataset.tab === tab; b.classList.toggle('on', on); b.setAttribute('aria-selected', on); });
        $('#pane-whatsapp').hidden = tab !== 'whatsapp';
        $('#pane-email').hidden = tab !== 'email';
        try { history.replaceState(null, '', tab === 'email' ? '?tab=email' : location.pathname); } catch (e) {}
    }
    $$('.nf-tabs button').forEach(function (b) { b.addEventListener('click', function () { show(b.dataset.tab); }); });
    $$('[data-go]').forEach(function (a) { a.addEventListener('click', function (e) { e.preventDefault(); show(a.dataset.go); var t = document.getElementById('nfLog'); t && t.scrollIntoView({ behavior: 'smooth' }); }); });

    // toast
    var toast = $('#nfSaved'), tt;
    function say(msg, err) {
        toast.lastElementChild.textContent = msg; toast.classList.toggle('err', !!err);
        toast.firstElementChild.className = err ? 'ti ti-alert-circle' : 'ti ti-check';
        toast.classList.add('show'); clearTimeout(tt); tt = setTimeout(function () { toast.classList.remove('show'); }, 2600);
    }

    // settings (auto-save)
    function collect() {
        var data = { hide_product: $('[data-pref="hide_product"]').checked ? 1 : 0, hide_price_prepaid: $('[data-pref="hide_price_prepaid"]').checked ? 1 : 0, email: [], sms: [] };
        $$('.nf-tbl[data-ch]').forEach(function (t) { data[t.dataset.ch] = $$('.nf-stage:checked', t).map(function (c) { return c.value; }); });
        return data;
    }
    function syncAll() {
        $$('.nf-tbl[data-ch]').forEach(function (t) {
            var boxes = $$('.nf-stage', t), on = boxes.filter(function (c) { return c.checked; }).length, all = $('.nf-all', t);
            all.checked = on === boxes.length; all.indeterminate = on > 0 && on < boxes.length;
        });
    }
    var saving, pending = false;
    function save() {
        if (saving) { pending = true; return; }
        saving = fetch(@json(route('notifications.settings')), {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(collect())
        }).then(function (r) {
            if (r.status === 419) throw new Error('Session expired - refresh the page.');
            return r.json().then(function (j) { if (!r.ok || !j.ok) throw new Error(j.message || 'Could not save'); return j; });
        }).then(function () { say('Saved'); })
          .catch(function (e) { say(e.message || 'Could not save', true); })
          .finally(function () { saving = null; if (pending) { pending = false; save(); } });
    }
    $$('[data-pref]').forEach(function (c) { c.addEventListener('change', save); });
    $$('.nf-stage').forEach(function (c) { c.addEventListener('change', function () { syncAll(); save(); }); });
    $$('.nf-all').forEach(function (a) { a.addEventListener('change', function () { $$('.nf-stage', a.closest('table')).forEach(function (c) { c.checked = a.checked; }); syncAll(); save(); }); });
    syncAll();

    // modal
    var modal = $('#nfModal'), body = $('#nfModalBody'), testBtn = $('#nfTest'), current = null;
    var footDefault = $('#nfModalFoot').textContent;
    function open(title, sub) { $('#nfModalTitle').firstChild.textContent = title; $('#nfModalSub').textContent = sub || ''; $('#nfModalFoot').textContent = footDefault; modal.hidden = false; }
    function close() { modal.hidden = true; body.innerHTML = ''; }
    $('#nfClose').addEventListener('click', close);
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) close(); });
    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

    $$('[data-preview]').forEach(function (b) {
        b.addEventListener('click', function () {
            var ch = b.dataset.preview, ev = b.dataset.event;
            current = ev;
            open((ch === 'email' ? 'Email' : 'SMS') + ' preview · ' + b.dataset.label, '');
            testBtn.hidden = ch !== 'email';
            body.innerHTML = '<div class="nf-skel">Loading preview…</div>';
            var p = collect();
            var url = @json(url('/notifications/preview')) + '/' + ch + '/' + ev + '?hide_product=' + p.hide_product + '&hide_price_prepaid=' + p.hide_price_prepaid;
            fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    if (!j.ok) throw 0;
                    if (ch === 'email') {
                        $('#nfModalSub').textContent = 'Subject: ' + j.subject;
                        var f = document.createElement('iframe'); f.setAttribute('sandbox', ''); f.title = 'Email preview'; f.srcdoc = j.html;
                        body.innerHTML = ''; body.appendChild(f);
                    } else {
                        body.innerHTML = '<div class="nf-smsbox"><div class="nf-bub">' + esc(j.text) + '<div class="t">' + j.text.length + ' characters</div></div></div>';
                    }
                })
                .catch(function () { body.innerHTML = '<div class="nf-skel">Preview could not be loaded. Please try again.</div>'; });
        });
    });

    testBtn.addEventListener('click', function () {
        testBtn.disabled = true;
        fetch(@json(route('notifications.test')), {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ event: current })
        }).then(function (r) { return r.json().then(function (j) { j._s = r.status; return j; }); })
          .then(function (j) { say(j._s === 429 ? 'Too many test emails - wait a minute.' : (j.message || (j.ok ? 'Sent' : 'Could not send')), !j.ok); })
          .catch(function () { say('Could not send', true); })
          .finally(function () { testBtn.disabled = false; });
    });

    // WhatsApp demo
    $('#nfDemoBtn').addEventListener('click', function () {
        open('WhatsApp demo', 'What your buyer sees at each step');
        testBtn.hidden = true;
        var brand = @json(\Illuminate\Support\Str::limit($demoBrand, 24));
        var steps = [
            ['Shipped', '📦 Your <b>' + esc(brand) + '</b> order <b>SPX1001</b> is shipped with Delhivery. AWB 1234567890123.'],
            ['Out for delivery', '🛵 Your order <b>SPX1001</b> is out for delivery and will reach you today. Please keep your phone reachable.'],
            ['Delivery failed', '⚠️ We couldn\'t deliver order <b>SPX1001</b> today. Tap below to choose: re-attempt, new address, new phone or return.'],
            ['Delivered', '✅ Your order <b>SPX1001</b> has been delivered. Thank you for shopping with <b>' + esc(brand) + '</b>!']
        ];
        body.innerHTML = '<div class="nf-smsbox" style="display:grid;gap:12px">' + steps.map(function (s) {
            return '<div><div style="font-size:11px;color:#555;margin:0 0 4px 4px;font-weight:600">' + s[0] + '</div><div class="nf-bub">Hey Ramesh 👋<br>' + s[1] + '<div class="t">10:42 am</div><span class="cta">Track Now</span></div></div>';
        }).join('') + '</div>';
        $('#nfModalFoot').textContent = 'Sample messages. Real alerts use your buyer\'s name, order, courier and tracking link.';
    });
})();
</script>
@endsection
