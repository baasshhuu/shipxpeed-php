#!/usr/bin/env bash
# Orders UI fixes:
#  1) Sidebar "NDR" link pointed at Reverse orders (so NDR lit up on /seller/reverse-order) -> now opens the NDR page.
#  2) Empty "No orders found" box: giant button icons, text/buttons cut off in the wide table -> fits the screen.
# Patches server copies in place. Backs up first, restores on failure. Safe to run twice.
# Usage: cd /var/www/shipxpeed && git fetch origin && git show origin/main:deploy/orders-ui-fix.sh | bash
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
BK=/root/pre-orders-ui-$(date +%Y%m%d-%H%M%S).tgz

# files: any live (non-backup) seller view/layout containing the sidebar or the empty-state styles
FILES=$(grep -rlE "sx-nav-link|\.empty-state i \{" resources/views --include=*.blade.php 2>/dev/null | grep -v '\.bak' | sort -u)
[ -n "$FILES" ] || { echo "FAIL: nothing to patch"; exit 1; }
tar czf "$BK" $FILES && echo "Backup: $BK"
restore() { echo "!! restoring"; tar xzf "$BK"; php artisan view:clear >/dev/null 2>&1; echo "RESTORED - nothing changed"; exit 1; }

rm -f /tmp/orders-ui-changed
FILES="$FILES" php <<'PHP' || restore
<?php
$css = <<<'CSS'
    /* empty state fits the visible table area (orders-ui-fix) */
    .table-responsive { container-type: inline-size; }
    .table-responsive .empty-state { position: sticky; left: 0; width: 100cqw; max-width: 100cqw; box-sizing: border-box; background: transparent; padding: 3rem 1rem; }
    .empty-state .d-flex { flex-wrap: wrap; }
    .empty-state p { font-size: 1rem; }
    .empty-state .btn i { font-size: 1em !important; margin: 0 .45rem 0 0 !important; opacity: 1 !important; color: inherit !important; }
    .empty-state > i {
CSS;
$n = 0;
foreach (preg_split('/\s+/', trim(getenv('FILES'))) as $f) {
    $s = file_get_contents($f); $o = $s;

    // 1) empty state
    if (strpos($s, 'orders-ui-fix') === false && strpos($s, '.empty-state i {') !== false) {
        $s = preg_replace('/^[ \t]*\.empty-state i \{/m', rtrim($css), $s, 1);
    }

    // 2) sidebar NDR link that points at Reverse orders -> NDR page
    $s = preg_replace_callback(
        '#<a\s(?:(?!</a>).)*?sx-nav-link(?:(?!</a>).)*?<i class="ti ti-alert-triangle"></i>\s*<span>NDR</span>#s',
        function ($m) {
            $a = $m[0];
            if (strpos($a, 'reverse') === false) return $a;
            $a = preg_replace("#route\(\s*'seller\.reverse\.order'\s*\)#", "route('xp.ndr')", $a);
            $a = preg_replace("#url\(\s*'/?seller/reverse-order'\s*\)#", "route('xp.ndr')", $a);
            $a = str_replace(['/seller/reverse-order'], ['/seller/xp/ndr'], $a);
            $a = preg_replace("#routeIs\(([^)]*)\)#", "routeIs('xp.ndr*', 'seller.courier.NDR')", $a, 1);
            return $a;
        }, $s);

    // array-style menus: ['seller.reverse.order', 'NDR', ...] on one line
    $s = preg_replace_callback('#^.*reverse.*[\'"]NDR[\'"].*$#m', function ($m) {
        return preg_replace(["#'seller\.reverse\.order\*?'#", "#/seller/reverse-order#"], ["'xp.ndr'", "/seller/xp/ndr"], $m[0]);
    }, $s);

    if ($s !== $o) { file_put_contents($f, $s); echo "patched: $f\n"; $n++; file_put_contents('/tmp/orders-ui-changed', "$f\n", FILE_APPEND); }
}
echo "files changed: $n\n";
PHP

chown www-data:www-data $FILES 2>/dev/null
sudo -u www-data php artisan view:clear >/dev/null 2>&1
CH=$(cat /tmp/orders-ui-changed 2>/dev/null | tr '\n' ' ')
[ -n "$CH" ] || { echo "nothing new to change"; echo "ORDERS_UI_DONE backup=$BK"; exit 0; }
OUT=$(sudo -u www-data env FILES="$CH" php -r '
require "vendor/autoload.php"; $a = require "bootstrap/app.php";
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$bad = 0;
foreach (preg_split("/\s+/", trim(getenv("FILES"))) as $f) {
    $php = app("blade.compiler")->compileString(file_get_contents($f));
    $t = tempnam(sys_get_temp_dir(), "bl"); file_put_contents($t, $php);
    exec("php -l " . escapeshellarg($t) . " 2>&1", $o, $rc); unlink($t);
    if ($rc) { echo "BAD $f\n"; $bad++; }
}
echo "BLADE " . ($bad ? "fail" : "ok") . "\n";' 2>&1)
echo "$OUT" | grep -E "^(BAD|BLADE)"
echo "$OUT" | grep -q "^BLADE ok" || { echo "$OUT" | tail -5; restore; }
echo "ORDERS_UI_DONE backup=$BK"
