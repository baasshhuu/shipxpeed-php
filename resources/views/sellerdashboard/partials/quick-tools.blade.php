{{-- Dashboard: live rate check + "How to create an order" video. Placed after the KPI tiles by the script below. --}}
@php
    $qtSeller = Auth::guard('seller')->user();
    $qtPickup = '';
    try {
        $qtWh = $qtSeller ? \App\Models\Warehouse::where('seller_id', $qtSeller->id)->orderByDesc('is_default')->first() : null;
        $qtPickup = $qtWh ? preg_replace('/\D/', '', (string) $qtWh->pincode) : '';
    } catch (\Throwable $e) {
    }
@endphp
<section class="sxd-grid qt-row" id="qtRow">
    <div class="sxd-card sxd-c8 qt-card">
        <div class="sxd-card-h"><div><h3>Calculate your shipping rates</h3><p>Live prices for your account, incl. 18% GST</p></div></div>

        <form id="qtForm" class="qt-form" autocomplete="off" novalidate>
            <div class="qt-route">
                <div class="qt-field">
                    <label for="qtPick"><span class="qt-dot qt-dot--from"></span>Pickup pincode</label>
                    <input class="form-control" id="qtPick" inputmode="numeric" maxlength="6" placeholder="Enter pincode" value="{{ $qtPickup }}">
                </div>
                <div class="qt-link" aria-hidden="true"></div>
                <div class="qt-field">
                    <label for="qtDel"><span class="qt-dot qt-dot--to"><i class="ti ti-map-pin"></i></span>Delivery pincode</label>
                    <input class="form-control" id="qtDel" inputmode="numeric" maxlength="6" placeholder="Enter pincode">
                </div>
            </div>
            <div class="qt-opts">
                <div class="qt-field qt-field--sm">
                    <label for="qtWt">Weight (g)</label>
                    <input class="form-control" id="qtWt" inputmode="numeric" value="500">
                </div>
                <div class="qt-field qt-field--sm">
                    <label>Payment</label>
                    <div class="qt-seg" role="radiogroup">
                        <button type="button" class="on" data-pay="prepaid" role="radio" aria-checked="true">Prepaid</button>
                        <button type="button" data-pay="cod" role="radio" aria-checked="false">COD</button>
                    </div>
                </div>
                <div class="qt-field qt-field--sm qt-amt" hidden>
                    <label for="qtAmt">Order value (₹)</label>
                    <input class="form-control" id="qtAmt" inputmode="decimal" placeholder="e.g. 999">
                </div>
                <button class="qt-btn" type="submit" id="qtGo"><span>Calculate rates</span></button>
            </div>
        </form>

        <div class="qt-out" id="qtOut" aria-live="polite"></div>
    </div>

    <div class="sxd-card sxd-c4 qt-card">
        <div class="sxd-card-h"><div><h3>Quick help</h3><p>Learn the panel in a minute</p></div></div>
        <button type="button" class="qt-video" id="qtVideoBtn" aria-label="Play: How to create an order">
            <img src="{{ asset('videos/how-to-create-order.jpg') }}" alt="" loading="lazy">
            <span class="qt-play"><i class="ti ti-player-play"></i></span>
            <span class="qt-vlabel"><b>How to create an order</b><small>1 min · step by step</small></span>
        </button>
        <a class="qt-help" href="{{ url('/seller/ticket') }}">
            <span><b>Still stuck?</b><small>Raise a ticket - we reply within 2 working hours.</small></span>
            <i class="ti ti-arrow-right"></i>
        </a>
    </div>
</section>

<div class="qt-modal" id="qtModal" hidden>
    <div class="qt-modal-box" role="dialog" aria-modal="true" aria-label="How to create an order">
        <button type="button" class="qt-x" id="qtClose" aria-label="Close"><i class="ti ti-x"></i></button>
        <video id="qtVideo" controls playsinline preload="none" poster="{{ asset('videos/how-to-create-order.jpg') }}">
            <source src="{{ asset('videos/how-to-create-order.mp4') }}" type="video/mp4">
        </video>
    </div>
</div>

<style>
.qt-row{margin-bottom:16px}
.qt-card .sxd-card-h{margin-bottom:14px}
.qt-form{display:flex;flex-direction:column;gap:12px}
.qt-route{display:grid;grid-template-columns:1fr 56px 1fr;align-items:end}
.qt-field{display:flex;flex-direction:column;gap:6px;min-width:0}
.qt-field label{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:500;color:var(--sx-fg-2,#a3abc2);margin:0}
.qt-field .form-control{height:42px;font-size:14px}
.qt-link{height:42px;position:relative}
.qt-link::before{content:"";position:absolute;left:6px;right:6px;top:50%;border-top:2px dotted var(--sx-line-2,rgba(196,206,235,.25))}
.qt-dot{width:18px;height:18px;border-radius:50%;display:inline-grid;place-items:center;flex:none}
.qt-dot--from{background:var(--sx-blue-bg,rgba(59,123,255,.14));box-shadow:inset 0 0 0 5px rgba(59,123,255,.18)}
.qt-dot--from::after{content:"";width:6px;height:6px;border-radius:50%;background:var(--sx-blue,#3b7bff)}
.qt-dot--to{background:var(--sx-ok-bg,rgba(95,211,161,.12));color:var(--sx-ok,#5fd3a1);font-size:12px}
.qt-opts{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end}
.qt-field--sm{flex:0 0 130px}
.qt-amt{flex-basis:150px}
.qt-seg{display:flex;height:42px;padding:3px;border-radius:10px;background:var(--sx-surface,#0f1626);border:1px solid var(--sx-line-2,rgba(196,206,235,.16))}
.qt-seg button{flex:1;border:0;border-radius:7px;background:transparent;color:var(--sx-fg-2,#a3abc2);font-size:13px;font-weight:500;padding:0 10px}
.qt-seg button.on{background:var(--sx-raised,#152036);color:var(--sx-fg,#e9ecf5);box-shadow:0 0 0 1px var(--sx-line-2,rgba(196,206,235,.16))}
.qt-btn{flex:1 1 160px;height:42px;border-radius:10px;border:1px solid var(--sx-blue,#3b7bff);background:var(--sx-blue,#3b7bff);color:#fff;font-size:14px;font-weight:600;transition:filter .15s}
.qt-btn:hover{filter:brightness(1.08)}
.qt-btn[disabled]{opacity:.7;cursor:progress}
.qt-out:empty{display:none}
.qt-out{margin-top:14px;border-top:1px solid var(--sx-line,rgba(196,206,235,.09));padding-top:12px}
.qt-meta{display:flex;justify-content:space-between;align-items:center;gap:8px;font-size:12.5px;color:var(--sx-fg-3,#6d7690);margin-bottom:8px}
.qt-meta b{color:var(--sx-blue-2,#6b9cff);font-weight:600}
.qt-list{display:flex;flex-direction:column;gap:6px;max-height:312px;overflow:auto}
.qt-rate{display:grid;grid-template-columns:36px 1fr auto;gap:12px;align-items:center;padding:9px 12px;border-radius:10px;background:var(--sx-raised,#152036);border:1px solid var(--sx-line,rgba(196,206,235,.09))}
.qt-rate img{width:36px;height:36px;border-radius:8px;object-fit:contain;background:#fff;padding:3px}
.qt-rate .n{font-size:13.5px;font-weight:600;color:var(--sx-fg,#e9ecf5);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.qt-rate .s{font-size:11.5px;color:var(--sx-fg-3,#6d7690)}
.qt-rate .p{font-size:15px;font-weight:700;color:var(--sx-fg,#e9ecf5);text-align:right;white-space:nowrap}
.qt-rate .p small{display:block;font-size:11px;font-weight:500;color:var(--sx-fg-3,#6d7690)}
.qt-rate.best{border-color:rgba(95,211,161,.4)}
.qt-tag{display:inline-block;margin-left:6px;padding:1px 6px;border-radius:5px;font-size:10px;font-weight:600;background:var(--sx-ok-bg,rgba(95,211,161,.12));color:var(--sx-ok,#5fd3a1);vertical-align:1px}
.qt-msg{font-size:13px;color:var(--sx-fg-2,#a3abc2);padding:4px 0}
.qt-msg.err{color:var(--sx-bad,#ff7b7b)}
.qt-actions{display:flex;justify-content:flex-end;margin-top:10px}
.qt-actions a{font-size:13px;font-weight:600;color:var(--sx-blue-2,#6b9cff);text-decoration:none}
.qt-skel{height:54px;border-radius:10px;background:linear-gradient(90deg,var(--sx-raised,#152036) 0%,var(--sx-hover,#1a2742) 50%,var(--sx-raised,#152036) 100%);background-size:200% 100%;animation:qtSk 1.1s infinite linear}
@keyframes qtSk{to{background-position:-200% 0}}
.qt-video{position:relative;display:block;width:100%;max-width:560px;padding:0;border:0;border-radius:12px;overflow:hidden;aspect-ratio:16/9;background:#0a0f1c;cursor:pointer}
.qt-video img{width:100%;height:100%;object-fit:cover;opacity:.75;transition:opacity .2s,transform .3s}
.qt-video:hover img{opacity:.9;transform:scale(1.02)}
.qt-video::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(10,15,28,0) 35%,rgba(10,15,28,.9) 100%)}
.qt-play{position:absolute;left:50%;top:42%;transform:translate(-50%,-50%);width:56px;height:56px;border-radius:50%;display:grid;place-items:center;background:rgba(255,255,255,.95);color:#0a0f1c;font-size:22px;z-index:1;box-shadow:0 8px 24px rgba(0,0,0,.35)}
.qt-vlabel{position:absolute;left:14px;right:14px;bottom:12px;z-index:1;text-align:left;display:flex;flex-direction:column}
.qt-vlabel b{font-size:14.5px;color:#fff}
.qt-vlabel small{font-size:12px;color:rgba(233,236,245,.75)}
.qt-help{margin-top:12px;display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;background:var(--sx-raised,#152036);border:1px solid var(--sx-line,rgba(196,206,235,.09));text-decoration:none}
.qt-help b{display:block;font-size:13.5px;color:var(--sx-fg,#e9ecf5)}
.qt-help small{display:block;font-size:12px;color:var(--sx-fg-3,#6d7690)}
.qt-help i{color:var(--sx-blue-2,#6b9cff)}
.qt-modal{position:fixed;inset:0;z-index:20000;display:grid;place-items:center;padding:16px;background:rgba(5,8,16,.78);backdrop-filter:blur(3px)}
.qt-modal[hidden]{display:none}
.qt-modal-box{position:relative;width:min(960px,100%)}
.qt-modal-box video{width:100%;border-radius:12px;background:#000;display:block}
.qt-x{position:absolute;top:-44px;right:0;width:36px;height:36px;border-radius:50%;border:0;background:rgba(255,255,255,.12);color:#fff;font-size:18px}
@media (max-width:767.98px){
  .qt-route{grid-template-columns:1fr}
  .qt-link{display:none}
  .qt-field--sm,.qt-amt{flex:1 1 45%}
  .qt-btn{flex-basis:100%}
}
</style>

<script>
(function () {
    var row = document.getElementById('qtRow');
    if (!row) return;

    // Place right after the KPI tiles; fall back to where the include sits.
    var kpis = document.querySelector('.sxd-kpis');
    if (kpis && kpis.parentNode) kpis.parentNode.insertBefore(row, kpis.nextSibling);
    // The older "Quick rate check" form only linked to the calculator - this card replaces it.
    var old = document.getElementById('sxdRate');
    if (old) {
        var head = old.previousElementSibling;
        if (head && /Quick rate check/i.test(head.textContent)) head.remove();
        old.remove();
    }

    var $ = function (id) { return document.getElementById(id); };
    var form = $('qtForm'), out = $('qtOut'), go = $('qtGo');
    var pay = 'prepaid';
    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
    var inr = function (n) { return '₹' + Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
    var digits = function (el) { el.addEventListener('input', function () { el.value = el.value.replace(/\D/g, '').slice(0, 6); }); };
    digits($('qtPick')); digits($('qtDel'));

    row.querySelectorAll('.qt-seg button').forEach(function (b) {
        b.addEventListener('click', function () {
            pay = b.dataset.pay;
            row.querySelectorAll('.qt-seg button').forEach(function (x) {
                x.classList.toggle('on', x === b); x.setAttribute('aria-checked', x === b ? 'true' : 'false');
            });
            row.querySelector('.qt-amt').hidden = pay !== 'cod';
        });
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var o = $('qtPick').value, d = $('qtDel').value;
        if (o.length !== 6 || d.length !== 6) {
            out.innerHTML = '<div class="qt-msg err">Enter both 6-digit pincodes.</div>';
            (o.length !== 6 ? $('qtPick') : $('qtDel')).focus();
            return;
        }
        var amt = $('qtAmt').value.trim();
        if (pay === 'cod' && !(Number(amt) > 0)) {
            out.innerHTML = '<div class="qt-msg err">Enter the order value for COD.</div>';
            $('qtAmt').focus();
            return;
        }
        go.disabled = true; go.firstElementChild.textContent = 'Checking couriers…';
        out.innerHTML = '<div class="qt-list"><div class="qt-skel"></div><div class="qt-skel"></div><div class="qt-skel"></div></div>';

        fetch(@json(url('/seller/quick-rate')), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: JSON.stringify({ origin: o, destination: d, weight: Number($('qtWt').value) || 500, payment_type: pay, order_amount: Number(amt) || 0 })
        }).then(function (r) {
            return r.json().catch(function () { return { ok: false }; }).then(function (j) { j._status = r.status; return j; });
        }).then(function (j) {
            if (j._status === 419) { out.innerHTML = '<div class="qt-msg err">Session expired - refresh the page and try again.</div>'; return; }
            if (j._status === 422) {
                var first = j.errors ? Object.values(j.errors)[0][0] : 'Check the details and try again.';
                out.innerHTML = '<div class="qt-msg err">' + esc(first) + '</div>'; return;
            }
            if (!j.ok) { out.innerHTML = '<div class="qt-msg err">' + esc(j.message || 'Could not fetch rates right now.') + '</div>'; return; }
            if (!j.rates.length) { out.innerHTML = '<div class="qt-msg">' + esc(j.message) + '</div>'; return; }
            var html = '<div class="qt-meta"><span>' + j.rates.length + ' courier' + (j.rates.length > 1 ? 's' : '') + ' · ' + o + ' → ' + d +
                '</span>' + (j.zone ? '<span>Zone <b>' + esc(j.zone) + '</b></span>' : '') + '</div><div class="qt-list">';
            j.rates.forEach(function (r, i) {
                html += '<div class="qt-rate' + (i === 0 ? ' best' : '') + '"><img src="' + esc(r.logo) + '" alt="" loading="lazy">' +
                    '<div style="min-width:0"><div class="n">' + esc(r.name) + (i === 0 ? '<span class="qt-tag">Cheapest</span>' : '') + '</div>' +
                    '<div class="s">Chargeable ' + esc(r.weight) + ' g' + (r.zone ? ' · Zone ' + esc(r.zone) : '') + '</div></div>' +
                    '<div class="p">' + inr(r.total) + (r.cod > 0 ? '<small>incl. COD ' + inr(r.cod) + '</small>' : '<small>incl. GST</small>') + '</div></div>';
            });
            html += '</div><div class="qt-actions"><a href="' + @json(url('/seller/order-add')) + '">Create order <i class="ti ti-arrow-right"></i></a></div>';
            out.innerHTML = html;
        }).catch(function () {
            out.innerHTML = '<div class="qt-msg err">Network error - please try again.</div>';
        }).finally(function () {
            go.disabled = false; go.firstElementChild.textContent = 'Calculate rates';
        });
    });

    // Video
    var modal = $('qtModal'), video = $('qtVideo');
    document.body.appendChild(modal);
    var close = function () { video.pause(); modal.hidden = true; };
    $('qtVideoBtn').addEventListener('click', function () { modal.hidden = false; video.play().catch(function () {}); });
    $('qtClose').addEventListener('click', close);
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) close(); });
})();
</script>
