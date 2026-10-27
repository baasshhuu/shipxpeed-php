{{-- Dark / light theme switch. Dark is the default; the choice is remembered per browser. --}}
<script>
(function () {
    if (window.__sxThemeInit) return;
    window.__sxThemeInit = true;
    var t = 'dark';
    try { if (localStorage.getItem('sx-theme') === 'light') t = 'light'; } catch (e) {}
    document.documentElement.setAttribute('data-theme', t);
})();
</script>
<link id="sxLightCss" rel="stylesheet" href="{{ asset('css/shipxpeed-light.css') }}?v=1">
<script>
(function () {
    if (window.__sxThemeBtn) return;
    window.__sxThemeBtn = true;
    function setTheme(t) {
        document.documentElement.setAttribute('data-theme', t);
        try { localStorage.setItem('sx-theme', t); } catch (e) {}
        var b = document.getElementById('sxTheme');
        if (b) {
            var label = t === 'light' ? 'Switch to dark mode' : 'Switch to light mode';
            b.setAttribute('aria-label', label);
            b.setAttribute('title', label);
        }
    }
    function addButton() {
        if (document.getElementById('sxTheme')) return;
        var bar = document.querySelector('.sx-top-r');
        if (!bar) return;
        var b = document.createElement('button');
        b.type = 'button';
        b.id = 'sxTheme';
        b.className = 'sx-icon-btn';
        b.innerHTML = '<i class="ti ti-sun"></i><i class="ti ti-moon"></i>';
        b.addEventListener('click', function () {
            setTheme(document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light');
        });
        var bell = bar.querySelector('.ti-bell');
        var anchor = bell ? bell.closest('a,button') : null;
        bar.insertBefore(b, anchor && anchor.parentNode === bar ? anchor : (document.getElementById('sxProfile') || null));
        setTheme(document.documentElement.getAttribute('data-theme') || 'dark');
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', addButton);
    else addButton();
})();
</script>
