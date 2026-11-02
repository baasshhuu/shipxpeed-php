{{-- Seller sidebar structure (runs in the browser, so it works with the server's themed sidebar markup).
     Regroups the existing links into: Overview · Orders · Delivery issues · Billing · Tools · Settings · Support,
     adds "Value added services" (one entry for RTO, early COD, protection, courier preferences, bulk actions,
     notifications …) and hides the individual entries that now live inside it. --}}
@if(Route::has('vas.index') && Auth::guard('seller')->check())
<style>[data-vas-hidden]{display:none!important}</style>
<script>
(function () {
    var VAS = @json(route('vas.index'));
    var NDR = @json(Route::has('xp.ndr') ? route('xp.ndr') : null);
    var B2B_ON = @json(Route::has('seller.b2b.rates') && class_exists(\App\Services\BigshipService::class) && app(\App\Services\BigshipService::class)->enabledForSellers());
    var SPECIAL = {'@vas': VAS, '@ndr': NDR,
        '@b2b-new': B2B_ON ? @json(Route::has('seller.b2b.order') ? route('seller.b2b.order') : null) : null,
        '@b2b': B2B_ON ? @json(Route::has('seller.b2b.orders') ? route('seller.b2b.orders') : null) : null,
        '@b2b-rates': B2B_ON ? @json(Route::has('seller.b2b.rates') ? route('seller.b2b.rates') : null) : null,
        '@b2b-track': B2B_ON ? @json(Route::has('seller.b2b.track') ? route('seller.b2b.track') : null) : null};
    var VAS_GROUP = /^\/(notifications|seller\/services|seller\/xp\/)/;
    // [heading, [[path, label, icon], ...]]   '@…' keys = built here if the sidebar has no such link
    var MENU = [
        ['Overview', [['/seller/dashboard', 'Dashboard', 'ti ti-smart-home']]],
        ['Orders', [
            ['/seller/order-add', 'Create order', 'ti ti-circle-plus'],
            ['/seller/order', 'Orders & shipments', 'ti ti-package'],
            ['/seller/reverse-order', 'Reverse orders', 'ti ti-arrow-back-up'],
            ['/seller/track-order-in', 'Track order', 'ti ti-map-pin'],
            ['/seller/channels', 'Channels', 'ti ti-plug']]],
        ['B2B Shipments', [
            ['@b2b-new', 'Create B2B shipment', 'ti ti-circle-plus'],
            ['@b2b', 'All B2B shipments', 'ti ti-truck-delivery'],
            ['@b2b-rates', 'B2B rate calculator', 'ti ti-calculator'],
            ['@b2b-track', 'Track B2B shipment', 'ti ti-map-pin']]],
        ['Delivery issues', [
            ['@ndr', 'NDR', 'ti ti-alert-triangle'],
            ['/seller/weight-discrepancy', 'Weight disputes', 'ti ti-scale']]],
        ['Billing', [
            ['/seller/recharge', 'Wallet & recharges', 'ti ti-wallet'],
            ['/seller/passbook', 'Passbook', 'ti ti-notebook'],
            ['/seller/shipping-charge', 'Shipping charges', 'ti ti-truck'],
            ['/seller/cod-remittance', 'COD remittance', 'ti ti-cash'],
            ['/seller/invoice/create', 'Invoices', 'ti ti-file-invoice']]],
        ['Tools', [
            ['/rate-cards', 'Rate calculator', 'ti ti-calculator'],
            ['/shipment-price', 'Rate card', 'ti ti-list'],
            ['@vas', 'Value added services', 'ti ti-layout-grid'],
            ['/seller/custom-label', 'Custom label', 'ti ti-tag'],
            ['/seller/shipment/report', 'Reports', 'ti ti-download']]],
        ['Settings', [
            ['/seller/warehouse/index', 'Warehouses', 'ti ti-building-warehouse'],
            ['/seller/webhooks', 'Webhooks', 'ti ti-link'],
            ['/seller/api', 'API documentation', 'ti ti-code']]],
        ['Support', [
            ['/seller/ticket', 'Support tickets', 'ti ti-headset'],
            ['/seller/help', 'Help', 'ti ti-help']]]
    ];

    function norm(u) { try { return new URL(u, location.origin).pathname.replace(/\/+$/, '') || '/'; } catch (e) { return ''; } }
    function path(a) { return norm(a.getAttribute('href') || ''); }
    var iconCache = {};
    function iconOk(cls) {
        if (cls in iconCache) return iconCache[cls];
        var i = document.createElement('i'); i.className = cls; i.style.position = 'absolute'; document.body.appendChild(i);
        var c = getComputedStyle(i, '::before').content; i.remove();
        return (iconCache[cls] = !!(c && c !== 'none' && c !== 'normal'));
    }
    function setLabel(a, text, icon) {
        var sp = a.querySelector('span'); if (sp) sp.textContent = text; else a.textContent = text;
        var i = a.querySelector('i'); if (i && icon && iconOk(icon)) i.className = icon;
        a.removeAttribute('title'); a.removeAttribute('data-vas-hidden');
    }
    var here = norm(location.pathname);
    function isActive(key, p) {
        if (key === '@vas') return VAS_GROUP.test(here) && here.indexOf('/seller/xp/ndr') !== 0;
        if (key === '@ndr') return here.indexOf('/seller/xp/ndr') === 0;
        if (key === '@b2b') return /^\/seller\/b2b-orders(\/\d+.*)?$/.test(here);
        if (key.indexOf('@b2b-') === 0) return here === p;
        return here === p || (p !== '/seller/order' && here.indexOf(p + '/') === 0);
    }

    function build(nav) {
        if (nav.getAttribute('data-sx-menu')) return true;
        var links = Array.prototype.slice.call(nav.children).filter(function (n) { return n.matches('a[href]'); });
        var heads = Array.prototype.slice.call(nav.children).filter(function (n) { return n.matches('.sx-nav-group'); });
        if (links.length < 8 || !heads.length) return false; // not the sidebar we know: leave it alone
        var byPath = {}; links.forEach(function (a) { var p = path(a); if (!byPath[p]) byPath[p] = a; });
        var used = [], frag = document.createDocumentFragment();

        MENU.forEach(function (sec) {
            var items = [];
            sec[1].forEach(function (it) {
                var key = it[0], p = key, a;
                if (key in SPECIAL) {
                    var url = SPECIAL[key]; if (!url) return;
                    p = norm(url);
                    a = byPath[p];
                    if (!a) { a = links[0].cloneNode(true); a.setAttribute('href', url); }
                } else {
                    a = byPath[p];
                }
                if (!a || used.indexOf(a) !== -1) return;
                used.push(a);
                setLabel(a, it[1], it[2]);
                a.classList.toggle('is-active', isActive(key, p));
                if (key === '@vas') a.setAttribute('data-vas-link', '1');
                items.push(a);
            });
            if (!items.length) return;
            var h = heads[0].cloneNode(false); h.textContent = sec[0]; frag.appendChild(h);
            items.forEach(function (a) { frag.appendChild(a); });
        });

        // links added later that this menu doesn't know yet stay visible under "More"
        var extra = links.filter(function (a) { return used.indexOf(a) === -1 && !VAS_GROUP.test(path(a)); });
        if (extra.length) {
            var h = heads[0].cloneNode(false); h.textContent = 'More'; frag.appendChild(h);
            extra.forEach(function (a) { frag.appendChild(a); });
        }
        var rest = Array.prototype.slice.call(nav.children).filter(function (n) { return !n.matches('a[href], .sx-nav-group'); });
        while (nav.firstChild) nav.removeChild(nav.firstChild);
        nav.appendChild(frag);
        rest.forEach(function (n) { nav.appendChild(n); });
        nav.setAttribute('data-sx-menu', '1');
        return true;
    }

    // other sidebar markup: just add one "Value added services" entry and hide the entries inside it
    function simple(side) {
        if (side.querySelector('[data-vas-link]')) return;
        var links = Array.prototype.slice.call(side.querySelectorAll('a[href]'));
        var ref = links.find(function (a) { return /^\/notifications/.test(path(a)); }) || links.find(function (a) { return /^\/seller\/xp\//.test(path(a)); });
        if (!ref) return;
        var el = ref.cloneNode(true); el.setAttribute('href', VAS); el.setAttribute('data-vas-link', '1');
        setLabel(el, 'Value added services', 'ti ti-layout-grid');
        ref.parentNode.insertBefore(el, ref);
        links.forEach(function (a) {
            if (!VAS_GROUP.test(path(a))) return;
            var l = a.closest('li');
            (l && side.contains(l) && l.querySelectorAll('a[href]').length === 1 ? l : a).setAttribute('data-vas-hidden', '1');
        });
    }

    function run() {
        var ok = 0;
        Array.prototype.slice.call(document.querySelectorAll('.sx-side')).forEach(function (side) {
            if (build(side.querySelector('.sx-nav') || side)) ok++;
        });
        if (!ok) Array.prototype.slice.call(document.querySelectorAll('#sxSide, .pc-sidebar, #mobileSidebarOffcanvas')).forEach(simple);
    }
    try { if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run(); } catch (e) {}
    // mobile menu may be filled when it opens
    document.addEventListener('show.bs.offcanvas', function () { try { run(); } catch (e) {} });
})();
</script>
@endif
{{-- Menu icons: the server's icon font is an older Tabler version, so some icon names draw nothing.
     Swap any blank menu icon for one the font has (falls back to a dot). --}}
@if(Auth::guard('seller')->check())
<script>
(function () {
    var SWAP = {
        'layout-dashboard': 'dashboard', 'webhook': 'link', 'help-circle': 'help', 'api': 'code',
        'plug-connected': 'plug', 'arrows-exchange': 'refresh', 'sparkles': 'star', 'truck-return': 'truck',
        'file-spreadsheet': 'file-text', 'receipt-2': 'receipt', 'message-2': 'message', 'world-pin': 'world'
    };
    function blank(i) { var c = getComputedStyle(i, '::before').content; return !c || c === 'none' || c === 'normal'; }
    function fix() {
        document.querySelectorAll('#sxSide a i.ti, .sx-side a i.ti, .pc-sidebar a i.ti, #mobileSidebarOffcanvas a i.ti, .vas-side a i.ti').forEach(function (i) {
            if (!blank(i)) return;
            var m = (i.className.match(/\bti-([a-z0-9-]+)/) || [])[1];
            var alt = m && SWAP[m];
            if (alt) { i.classList.remove('ti-' + m); i.classList.add('ti-' + alt); }
            if (blank(i)) { i.className = i.className.replace(/\bti-[a-z0-9-]+/g, '').trim() + ' ti-point'; }
        });
    }
    function run() { fix(); setTimeout(fix, 300); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(fix);
    document.addEventListener('show.bs.offcanvas', function () { setTimeout(fix, 50); });
})();
</script>
@endif
