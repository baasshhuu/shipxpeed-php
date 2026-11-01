{{-- Adds "Value added services" to the seller sidebar, right after "Shipping notifications".
     Done in the browser so it works with any sidebar markup. --}}
@if(Route::has('vas.index') && Auth::guard('seller')->check())
<script>
(function () {
    function add() {
        if (document.querySelector('[data-vas-link]')) return;
        var links = Array.prototype.slice.call(document.querySelectorAll('aside a, nav a, .pc-sidebar a'));
        var ref = links.find(function (a) { return /\/notifications(\?|$|#)/.test(a.getAttribute('href') || '') || /shipping notification/i.test(a.textContent); });
        if (!ref) return;
        var el = ref.cloneNode(true);
        el.setAttribute('href', @json(route('vas.index')));
        el.setAttribute('data-vas-link', '1');
        el.removeAttribute('title');
        var on = /\/seller\/services/.test(location.pathname);
        el.classList.toggle('is-active', on); el.classList.toggle('active', on);
        var icon = el.querySelector('i'); if (icon) icon.className = 'ti ti-sparkles';
        var label = el.querySelector('span') || el;
        if (label === el) { el.textContent = 'Value added services'; } else { label.textContent = 'Value added services'; }
        var holder = ref.closest('li') && ref.closest('li').parentElement === ref.parentElement.parentElement ? ref.closest('li') : ref;
        if (holder !== ref) { var li = holder.cloneNode(false); li.className = holder.className.replace(/\bactive\b/g, ''); li.appendChild(el); holder.after(li); }
        else { ref.after(el); }
        if (on) { ref.classList.remove('is-active'); ref.classList.remove('active'); }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', add); else add();
})();
</script>
@endif
