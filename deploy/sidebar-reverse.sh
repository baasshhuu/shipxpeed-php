#!/usr/bin/env bash
# Sidebar: the old "NDR" entry was really the Reverse orders link. orders-ui-fix.sh repointed it to NDR, which removed
# Reverse orders from the menu. This turns it back into "Reverse orders" (own icon, own highlight); NDR keeps its own link.
# Uses the backup made by orders-ui-fix.sh to find the original markup. Backs up first, restores on failure. Safe to rerun.
# Usage: cd /var/www/shipxpeed && git fetch origin && git show origin/main:deploy/sidebar-reverse.sh | bash
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
OLD=$(ls -1t /root/pre-orders-ui-*.tgz 2>/dev/null | tail -1)
[ -n "$OLD" ] || { echo "FAIL: orders-ui-fix backup not found in /root"; exit 1; }
echo "Original markup from: $OLD"
rm -rf /tmp/sbrev && mkdir -p /tmp/sbrev && tar xzf "$OLD" -C /tmp/sbrev || { echo "FAIL: cannot read backup"; exit 1; }
FILES=$(cd /tmp/sbrev && grep -rl "sx-nav-link" . --include=*.blade.php | sed 's#^\./##')
[ -n "$FILES" ] || { echo "FAIL: no sidebar file in backup"; exit 1; }
BK=/root/pre-sidebar-reverse-$(date +%Y%m%d-%H%M%S).tgz
tar czf "$BK" $FILES && echo "Backup: $BK"
restore() { echo "!! restoring"; tar xzf "$BK"; php artisan view:clear >/dev/null 2>&1; echo "RESTORED - nothing changed"; exit 1; }

FILES="$FILES" php <<'PHP' || restore
<?php
$re = '#<a\s(?:(?!</a>).)*?sx-nav-link(?:(?!</a>).)*?<i class="ti ti-alert-triangle"></i>\s*<span>NDR</span>#s';
// exactly what orders-ui-fix.sh did to the link
$fix = function ($a) {
    $a = preg_replace("#route\(\s*'seller\.reverse\.order'\s*\)#", "route('xp.ndr')", $a);
    $a = preg_replace("#url\(\s*'/?seller/reverse-order'\s*\)#", "route('xp.ndr')", $a);
    $a = str_replace(['/seller/reverse-order'], ['/seller/xp/ndr'], $a);
    return preg_replace("#routeIs\(([^)]*)\)#", "routeIs('xp.ndr*', 'seller.courier.NDR')", $a, 1);
};
$n = 0;
foreach (preg_split('/\s+/', trim(getenv('FILES'))) as $f) {
    $orig = file_get_contents('/tmp/sbrev/' . $f); $live = file_get_contents($f);
    if (!preg_match_all($re, $orig, $m)) continue;
    foreach ($m[0] as $a) {
        if (strpos($a, 'reverse') === false) continue;
        $want = str_replace(['ti ti-alert-triangle', '<span>NDR</span>'], ['ti ti-arrow-back-up', '<span>Reverse orders</span>'], $a);
        if (strpos($live, $want) !== false) { echo "already there: $f\n"; continue; }
        foreach ([$fix($a), $a] as $cur) {
            if (substr_count($live, $cur) === 1) { $live = str_replace($cur, $want, $live); $n++; echo "fixed: $f\n"; continue 2; }
        }
        fwrite(STDERR, "link not found in live $f\n"); exit(1);
    }
    file_put_contents($f, $live);
}
echo "links fixed: $n\n";
PHP

chown www-data:www-data $FILES 2>/dev/null
for f in $FILES; do
  sudo -u www-data php -r 'require "vendor/autoload.php"; $a = require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $t = tempnam(sys_get_temp_dir(), "bl"); file_put_contents($t, app("blade.compiler")->compileString(file_get_contents($argv[1]))); exec("php -l ".escapeshellarg($t)." 2>&1", $o, $rc); unlink($t); exit($rc ? 1 : 0);' "$f" || { echo "Blade check failed: $f"; restore; }
done
sudo -u www-data php artisan view:clear >/dev/null 2>&1
echo "SIDEBAR_REVERSE_DONE backup=$BK"
