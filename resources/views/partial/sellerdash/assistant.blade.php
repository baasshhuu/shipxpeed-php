{{-- Xpeed Assistant: floating AI helper for sellers --}}
<div id="xa-root" aria-live="polite">
<style>
#xa-root{--xa:#4f5bff;--xa2:#3b44e6;font-family:inherit}
#xa-fab{position:fixed;right:20px;bottom:20px;z-index:2000;width:58px;height:58px;border-radius:50%;border:0;background:linear-gradient(135deg,#5b66ff,#8a4dff);color:#fff;box-shadow:0 8px 24px rgba(79,91,255,.4);display:flex;align-items:center;justify-content:center;cursor:pointer}
#xa-fab svg{width:26px;height:26px}
#xa-panel{position:fixed;right:20px;bottom:90px;z-index:2001;width:380px;max-width:calc(100vw - 24px);height:560px;max-height:calc(100vh - 170px);background:#fff;border-radius:18px;box-shadow:0 18px 50px rgba(15,18,32,.28);display:none;flex-direction:column;overflow:hidden;border:1px solid #e6e8f0}
#xa-panel.open{display:flex}
@media (max-width:576px){#xa-panel{right:8px;left:8px;bottom:84px;width:auto;height:calc(100vh - 110px)}#xa-fab{right:14px;bottom:14px}}
.xa-head{background:linear-gradient(135deg,#1b1f3b,#2b2f63);color:#fff;padding:14px 16px;display:flex;align-items:center;gap:10px}
.xa-head b{font-size:15px}.xa-head small{display:block;color:#b8bde8;font-size:12px}
.xa-head button{margin-left:auto;background:transparent;border:0;color:#cfd3ff;font-size:13px;cursor:pointer}
.xa-dot{width:9px;height:9px;border-radius:50%;background:#39d98a;box-shadow:0 0 0 3px rgba(57,217,138,.25)}
.xa-body{flex:1;overflow:auto;padding:14px;background:#f6f7fb;display:flex;flex-direction:column;gap:10px}
.xa-msg{max-width:86%;padding:9px 12px;border-radius:14px;font-size:14px;line-height:1.45;white-space:pre-wrap;word-wrap:break-word}
.xa-bot{background:#fff;border:1px solid #e6e8f0;align-self:flex-start;border-bottom-left-radius:4px;color:#1f2333}
.xa-me{background:var(--xa);color:#fff;align-self:flex-end;border-bottom-right-radius:4px}
.xa-card{align-self:stretch;background:#fff;border:1.5px solid var(--xa);border-radius:14px;padding:12px}
.xa-card b{display:block;margin-bottom:6px}.xa-card pre{white-space:pre-wrap;font-family:inherit;font-size:13px;margin:0 0 10px;color:#374151}
.xa-card .xa-btns{display:flex;gap:8px}
.xa-card button{flex:1;border-radius:10px;padding:8px;font-weight:600;cursor:pointer;border:1px solid #d6d9e0;background:#fff}
.xa-card button.ok{background:var(--xa);border-color:var(--xa);color:#fff}
.xa-chips{display:flex;gap:6px;flex-wrap:wrap}
.xa-chips button{border:1px solid #d9dcf5;background:#fff;color:#3b44e6;border-radius:16px;padding:5px 10px;font-size:12.5px;cursor:pointer}
.xa-foot{display:flex;gap:8px;padding:10px;border-top:1px solid #e6e8f0;background:#fff}
.xa-foot textarea{flex:1;resize:none;border:1px solid #d6d9e0;border-radius:12px;padding:9px 11px;font-size:14px;height:42px;max-height:110px;outline:none}
.xa-foot textarea:focus{border-color:var(--xa)}
.xa-foot button{border:0;background:var(--xa);color:#fff;border-radius:12px;width:44px;cursor:pointer}
.xa-typing{align-self:flex-start;color:#6b7280;font-size:13px}
.xa-msg a{color:inherit;text-decoration:underline}
</style>

<button id="xa-fab" type="button" aria-label="Open Xpeed Assistant" title="Xpeed Assistant">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.8 4.2L18 9l-4.2 1.8L12 15l-1.8-4.2L6 9l4.2-1.8z"/><path d="M19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9z"/></svg>
</button>

<div id="xa-panel" role="dialog" aria-label="Xpeed Assistant">
    <div class="xa-head"><span class="xa-dot"></span><div><b>Xpeed Assistant</b><small>Orders, tracking, tickets &amp; more</small></div>
        <button type="button" id="xa-new">New chat</button><button type="button" id="xa-close" aria-label="Close">&#10005;</button></div>
    <div class="xa-body" id="xa-body">
        <div class="xa-msg xa-bot">Hi! I can track shipments, explain failed orders, check your wallet, cancel orders and raise tickets. Tap an option, or type an AWB / order number.</div>
        <div class="xa-chips" id="xa-chips">
            <button type="button">Wallet balance</button>
            <button type="button">Track my latest shipment</button>
            <button type="button">Why did my orders fail?</button>
            <button type="button">Shipment summary</button>
            <button type="button">Raise a ticket</button>
            <button type="button">Cancel an order</button>
            <button type="button">Create a new order</button>
        </div>
    </div>
    <form class="xa-foot" id="xa-form" autocomplete="off">
        <textarea id="xa-input" rows="1" placeholder="Ask anything…" maxlength="2000"></textarea>
        <button type="submit" aria-label="Send">&#10148;</button>
    </form>
</div>

<script>
(function(){
    const R = { chat: @json(route('seller.assistant.chat')), confirm: @json(route('seller.assistant.confirm')), reset: @json(route('seller.assistant.reset')) };
    let csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const $ = id => document.getElementById(id);
    const body = $('xa-body'), input = $('xa-input'), panel = $('xa-panel');
    let busy = false;

    function esc(s){ return String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
    function linkify(s){ return esc(s).replace(/(https?:\/\/[^\s)]+|\/seller\/[A-Za-z0-9\/_\-?=&.]+)/g, u => '<a href="'+u+'" target="_blank" rel="noopener">'+u+'</a>'); }
    function add(text, who){ const d = document.createElement('div'); d.className = 'xa-msg ' + (who === 'me' ? 'xa-me' : 'xa-bot'); d.innerHTML = who === 'me' ? esc(text) : linkify(text); body.appendChild(d); body.scrollTop = body.scrollHeight; return d; }
    // Get a fresh CSRF token from the current page (the token changes when the session is refreshed).
    async function refreshCsrf(){
        try {
            const h = await fetch(location.href, { credentials:'same-origin', headers:{'Accept':'text/html'} }).then(r => r.text());
            const m = h.match(/name="csrf-token" content="([^"]+)"/);
            if (m) { csrf = m[1]; const meta = document.querySelector('meta[name="csrf-token"]'); if (meta) meta.content = csrf; return true; }
        } catch(e){}
        return false;
    }
    async function post(url, data, retried){
        const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin', body: JSON.stringify(data||{}) });
        if (r.status === 419) {
            if (!retried && await refreshCsrf()) return post(url, data, true);
            return { reply: 'Your session expired. Please refresh the page and try again.' };
        }
        if (r.status === 401) return { reply: 'You have been logged out. Please log in again.' };
        if (r.status === 429) return { reply: 'Too many messages in a short time. Please wait a minute and try again.' };
        try { return await r.json(); } catch(e){ return { reply: 'Something went wrong (' + r.status + '). Please try again.' }; }
    }
    function chips(list){
        body.querySelectorAll('.xa-chips').forEach(c => c.remove());
        if (!list || !list.length) return;
        const c = document.createElement('div'); c.className = 'xa-chips';
        list.forEach(t => { const b = document.createElement('button'); b.type = 'button'; b.textContent = t; c.appendChild(b); });
        body.appendChild(c); body.scrollTop = body.scrollHeight;
    }
    function card(p){
        const c = document.createElement('div'); c.className = 'xa-card';
        c.innerHTML = '<b>' + esc(p.title) + '</b><pre>' + esc(p.summary) + '</pre><div class="xa-btns"><button type="button" class="ok">Confirm</button><button type="button">Cancel</button></div>';
        const [ok, no] = c.querySelectorAll('button');
        const go = async (decision) => { ok.disabled = no.disabled = true; const r = await post(R.confirm, { token: p.token, decision }); c.remove(); add(r.reply || 'Done.', 'bot'); chips(MENU); };
        ok.onclick = () => go('confirm'); no.onclick = () => go('cancel');
        body.appendChild(c); body.scrollTop = body.scrollHeight;
    }
    async function send(text){
        text = (text || '').trim(); if (!text || busy) return;
        busy = true; body.querySelectorAll('.xa-chips').forEach(c => c.remove()); add(text, 'me'); input.value = '';
        const t = document.createElement('div'); t.className = 'xa-typing'; t.textContent = 'Thinking…'; body.appendChild(t); body.scrollTop = body.scrollHeight;
        try { const r = await post(R.chat, { message: text }); t.remove(); add(r.reply || '…', 'bot'); if (r.pending) card(r.pending); chips(r.chips); }
        catch(e){ t.remove(); add('Network problem. Please check your internet and try again.', 'bot'); }
        busy = false; input.focus();
    }
    $('xa-fab').onclick = () => { panel.classList.toggle('open'); if (panel.classList.contains('open')) input.focus(); };
    $('xa-close').onclick = () => panel.classList.remove('open');
    const MENU = ['Wallet balance', 'Track my latest shipment', 'Why did my orders fail?', 'Shipment summary', 'Raise a ticket', 'Cancel an order', 'Create a new order'];
    $('xa-new').onclick = async () => { await post(R.reset); body.innerHTML = ''; add('New chat started. How can I help?', 'bot'); chips(MENU); };
    $('xa-form').onsubmit = e => { e.preventDefault(); send(input.value); };
    input.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(input.value); } });
    body.addEventListener('click', e => { const b = e.target.closest('.xa-chips button'); if (b) send(b.textContent); });
})();
</script>
</div>
