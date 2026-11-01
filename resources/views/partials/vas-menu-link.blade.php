{{-- Seller sidebar: one "Value added services" entry in place of Shipping notifications + the
     Control Center links (NDR, RTO risk, bulk actions, courier preferences, reports, tracking, protection).
     Done in the browser so it works with any sidebar markup. --}}
@if(Route::has('vas.index') && Auth::guard('seller')->check())
<style>[data-vas-hidden]{display:none!important}</style>
<script>
(function () {
    var GROUP = /^\/(notifications|seller\/services|seller\/xp\/)/;
    function hrefPath(a) { try { return new URL(a.getAttribute('href') || '', location.origin).pathname; } catch (e) { return ''; } }
    function hide(el) { if (el) el.setAttribute('data-vas-hidden', '1'); }
    function addAll() {
        var sides = Array.prototype.slice.call(document.querySelectorAll('#sxSide, .sx-side, .pc-sidebar, #mobileSidebarOffcanvas'))
            .filter(function (n, i, arr) { return !n.classList.contains('vas-side') && arr.findIndex(function (m) { return m.contains(n) && m !== n; }) === -1; });
        sides.forEach(add);
    }
    function add(side) {
        if (side.querySelector('[data-vas-link]')) return;
        var links = Array.prototype.slice.call(side.querySelectorAll('a[href]'));
        var ref = links.find(function (a) { return /^\/notifications/.test(hrefPath(a)); })
               || links.find(function (a) { return /^\/seller\/xp\//.test(hrefPath(a)); });
        if (!ref) return;

        var on = GROUP.test(location.pathname);
        var el = ref.cloneNode(true);
        el.setAttribute('href', @json(route('vas.index')));
        el.setAttribute('data-vas-link', '1');
        el.removeAttribute('title');
        el.classList.remove('is-active', 'active');
        if (on) { el.classList.add(ref.classList.contains('sx-nav-link') ? 'is-active' : 'active'); el.setAttribute('aria-current', 'page'); }
        var icon = el.querySelector('i'); if (icon) icon.className = 'ti ti-sparkles';
        var label = el.querySelector('span');
        if (label) label.textContent = 'Value added services'; else el.textContent = 'Value added services';

        var li = ref.closest('li');
        if (li && side.contains(li) && li.querySelectorAll('a[href]').length === 1) {
            var nli = li.cloneNode(false); nli.className = li.className.replace(/\bactive\b/g, '') + (on ? ' active' : '');
            nli.appendChild(el); li.parentNode.insertBefore(nli, li);
        } else {
            ref.parentNode.insertBefore(el, ref);
        }

        // hide the individual entries that now live inside Value added services
        links.forEach(function (a) {
            if (a === el || !GROUP.test(hrefPath(a))) return;
            var l = a.closest('li');
            hide(l && side.contains(l) && l.querySelectorAll('a[href]').length === 1 ? l : a);
        });
        // and a "Control Center" heading, if the sidebar has one
        Array.prototype.slice.call(side.querySelectorAll('li, span, div, h6, small, p')).forEach(function (n) {
            if (n.children.length <= 1 && /^\s*control center\s*$/i.test(n.textContent)) hide(n.closest('li') || n);
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', addAll); else addAll();
})();
</script>
@endif
