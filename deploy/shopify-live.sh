#!/usr/bin/env bash
# Puts the Shopify channel live on a server whose checkout has local edits.
# Copies only Shopify files from origin/main and patches the 4 shared files in place.
# Takes a backup first and restores it automatically if any PHP file fails to lint.
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-shopify-$STAMP.tgz

NEW_FILES="
app/Services/Shopify/ShopifyClient.php
app/Services/Shopify/ShopifyGraphqlException.php
app/Services/Shopify/ShopifyOrderSync.php
app/Services/Shopify/ShopifyStatusSync.php
app/Console/Commands/ShopifySync.php
config/shopify.php
database/migrations/2026_10_21_000001_shopify_channel_sync.php
app/Http/Controllers/Shopifyconnect/ShopifyController.php
app/Models/ShopifyConnection.php
resources/views/shopify/index.blade.php
resources/views/shopify/shopify-integration.blade.php
"
PATCHED="app/Console/Kernel.php routes/web.php app/Http/Middleware/VerifyCsrfToken.php app/Http/Controllers/selleradmin/ShipmentController.php"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }

# backup (only existing files)
EXIST=""; for f in $NEW_FILES $PATCHED; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() { echo "!! restoring backup"; tar xzf "$BK"; for f in $NEW_FILES; do git cat-file -e HEAD:"$f" 2>/dev/null || { tar tzf "$BK" | grep -qx "$f" || rm -f "$f"; }; done; echo "RESTORED - nothing changed"; exit 1; }

mkdir -p app/Services/Shopify
for f in $NEW_FILES; do git show "$REF:$f" > "$f" || restore; done
echo "Copied Shopify files"

# Patch shared files with PHP (exact, idempotent)
git show "$REF:routes/web.php" > /tmp/origin-web.php
php <<'PHP' || restore
<?php
function must($c, $m) { if (!$c) { fwrite(STDERR, "FAIL: $m\n"); exit(1); } }

// 1. Kernel: add the shopify:sync schedule
$k = 'app/Console/Kernel.php'; $s = file_get_contents($k);
if (!str_contains($s, "command('shopify:sync')")) {
    $block = "\n        // Shopify: pull new orders + push AWB / shipment status back\n        \$schedule\n        ->command('shopify:sync')\n        ->everyFiveMinutes()\n        ->withoutOverlapping(20)\n        ->runInBackground()\n        ->appendOutputTo(storage_path('logs/shopify-sync.log'));\n";
    $pos = strpos($s, 'protected function schedule(');
    must($pos !== false, 'Kernel schedule() not found');
    // insert before the closing brace of schedule()
    $depth = 0; $i = strpos($s, '{', $pos);
    for ($j = $i; $j < strlen($s); $j++) { if ($s[$j] === '{') $depth++; elseif ($s[$j] === '}') { $depth--; if ($depth === 0) break; } }
    must($depth === 0, 'Kernel brace match');
    $s = substr($s, 0, $j) . $block . "    " . substr($s, $j);
    file_put_contents($k, $s); echo "Kernel patched\n";
} else echo "Kernel already has shopify:sync\n";

// 2. routes/web.php: swap the old Shopify channel block for the new one
$w = 'routes/web.php'; $s = file_get_contents($w); $o = file_get_contents('/tmp/origin-web.php');
$startNew = strpos($o, '// Channel Management Routes'); $endNew = strpos($o, '// Alternative simpler routes for integration pages');
must($startNew !== false && $endNew !== false, 'new route block missing in origin');
$newBlock = substr($o, $startNew, $endNew - $startNew);
if (!str_contains($s, "auth/shopify/callback")) {
    $a = strpos($s, '// Channel Management Routes'); $b = strpos($s, '// Alternative simpler routes for integration pages');
    if ($a !== false && $b !== false && $b > $a) {
        $s = substr($s, 0, $a) . $newBlock . substr($s, $b);
    } else {
        // markers missing: drop old one-line shopify channel routes, append new block
        $s = preg_replace("~^Route::(get|post)\('/(seller/channels|webhooks/shopify|seller/shopify)[^\n]*\n~m", '', $s);
        $s = rtrim($s) . "\n\n" . $newBlock;
    }
    file_put_contents($w, $s); echo "routes/web.php patched\n";
} else echo "routes already have callback\n";

// 3. CSRF exemption for webhooks
$v = 'app/Http/Middleware/VerifyCsrfToken.php'; $s = file_get_contents($v);
if (!str_contains($s, 'webhooks/shopify/*')) {
    $s = preg_replace('~protected \$except = \[~', "protected \$except = [\n        'webhooks/shopify/*',", $s, 1, $n);
    must($n === 1, 'CSRF except not found');
    file_put_contents($v, $s); echo "CSRF patched\n";
}

// 4. ShipmentController: dd() inside createShipmentshopify kills the cron run
$c = 'app/Http/Controllers/selleradmin/ShipmentController.php'; $s = file_get_contents($c);
$a = strpos($s, 'function createShipmentshopify');
if ($a !== false) {
    $b = strpos($s, 'Failed to create Shopify order', $a);
    if ($b !== false) {
        $seg = substr($s, $a, $b - $a);
        $seg2 = preg_replace('~^\s*dd\(\$e->(errors|getMessage)\(\)\);\s*\n~m', '', $seg);
        if ($seg2 !== $seg) { file_put_contents($c, substr($s, 0, $a) . $seg2 . substr($s, $b)); echo "ShipmentController dd() removed\n"; }
    }
}
PHP

for f in $NEW_FILES $PATCHED; do php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; php -l "$f"; restore; }; done
echo "Lint OK"

chown -R www-data:www-data app config routes resources database storage bootstrap/cache 2>/dev/null
sudo -u www-data php artisan migrate --path=database/migrations/2026_10_21_000001_shopify_channel_sync.php --force || restore
sudo -u www-data php artisan optimize:clear >/dev/null
sudo -u www-data php artisan route:list --path=shopify 2>&1 | tail -15
sudo -u www-data php artisan shopify:sync && echo "shopify:sync command OK"
echo "Callback check: $(curl -s -o /dev/null -w '%{http_code}' -k https://shipxpeed.tech/auth/shopify/callback)"
echo "DEPLOY_DONE backup=$BK"
