#!/usr/bin/env bash
# Seller › Notify page: WhatsApp Communication + Email & SMS Communication (replaces old /notifications).
#  - copies new files from origin/main, adds one require line to routes/web.php,
#    hooks buyer emails into the Order observer, runs one migration
#  - backs up everything first and restores automatically if any step fails. Safe to run twice.
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-notify-$STAMP.tgz
MIG=database/migrations/2026_10_27_000006_buyer_notify.php

NEW_FILES="
config/notify.php
$MIG
app/Services/Notify/NotifySettings.php
app/Services/Notify/BuyerNotifier.php
app/Http/Controllers/selleradmin/NotifyController.php
resources/views/sellerdashboard/notify/index.blade.php
resources/views/emails/buyer-status.blade.php
routes/notify_web.php
"
SHARED="routes/web.php app/Observers/OrderObserver.php app/Services/WhatsApp/OrderStatusNotifier.php resources/views/sellerdashboard/whatsapp-alerts/index.blade.php"

echo "Shipxpeed Notify page installer"
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
git cat-file -e "$REF:deploy/notify-page.sh" || { echo "FAIL: latest code not found on $REF"; exit 1; }
[ -f app/Observers/OrderObserver.php ] || { echo "FAIL: WhatsApp alerts (OrderObserver) not installed on this server"; exit 1; }

EXIST=""; for f in $NEW_FILES $SHARED; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $NEW_FILES; do tar tzf "$BK" | grep -qxF "$f" || rm -f "$f"; done
  php artisan optimize:clear >/dev/null 2>&1
  echo "RESTORED - nothing changed"; exit 1
}

for f in $NEW_FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
echo "Copied new files"

php <<'PHP' || restore
<?php
// 1. routes: load notify_web.php last so it replaces the old /notifications page
$r = 'routes/web.php';
$s = file_get_contents($r);
if (strpos($s, "notify_web.php") !== false) {
    echo "already there: notify routes\n";
} else {
    file_put_contents($r, rtrim($s) . "\n\n// Seller › Notify (keep last: replaces the old /notifications page)\nrequire __DIR__ . '/notify_web.php';\n");
    echo "patched: $r\n";
}

// 2. observer: send buyer emails next to the WhatsApp alerts
$o = 'app/Observers/OrderObserver.php';
$s = file_get_contents($o);
if (strpos($s, 'BuyerNotifier') !== false) {
    echo "already there: observer hook\n";
} else {
    $hook = function ($call) {
        return "            if (class_exists(\\App\\Services\\Notify\\BuyerNotifier::class)) {\n"
             . "                app(\\App\\Services\\Notify\\BuyerNotifier::class)->$call;\n"
             . "            }\n";
    };
    $a1 = "app(OrderStatusNotifier::class)->notify(\$order, 'booked');\n";
    $a2 = "app(OrderStatusNotifier::class)->handleOrderChange(\$order);\n";
    if (substr_count($s, $a1) !== 1 || substr_count($s, $a2) !== 1) {
        fwrite(STDERR, "observer anchors not found\n"); exit(1);
    }
    $s = str_replace($a1, $a1 . $hook("notify(\$order, 'booked')"), $s);
    $s = str_replace($a2, $a2 . $hook('handleOrderChange($order)'), $s);
    file_put_contents($o, $s);
    echo "patched: $o\n";
}

// 3. WhatsApp brand: default to the seller's own name, not the warehouse name/address
$w = 'app/Services/WhatsApp/OrderStatusNotifier.php';
$s = file_get_contents($w);
$old = "return \$pickup['warehouse_name'] ?? \$pickup['name'] ?? (\$seller->name ?? 'our store');";
if (strpos($s, "default: the seller's own name") !== false) {
    echo "already there: brand default\n";
} elseif (substr_count($s, $old) === 1) {
    $s = str_replace($old, "// default: the seller's own name; warehouse name only if the account has none\n        return filled(\$seller->name ?? null) ? trim(\$seller->name) : (\$pickup['warehouse_name'] ?? \$pickup['name'] ?? 'our store');", $s);
    file_put_contents($w, $s);
    echo "patched: $w\n";
} else {
    echo "skipped: brand default (server copy differs)\n";
}
$v = 'resources/views/sellerdashboard/whatsapp-alerts/index.blade.php';
if (is_file($v)) {
    $s = file_get_contents($v);
    $n = str_replace('Leave blank to use your pickup location name.', 'Leave blank to use your account name.', $s);
    if ($n !== $s) { file_put_contents($v, $n); echo "patched: $v\n"; }
}
PHP

for f in $NEW_FILES $SHARED; do
  case "$f" in *.blade.php) ;; *.php) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; php -l "$f"; restore; } ;; esac
done
echo "Lint OK"

chown -R www-data:www-data app config routes resources database storage bootstrap/cache 2>/dev/null
composer dump-autoload -q 2>/dev/null || true
sudo -u www-data php artisan migrate --path=$MIG --force || restore
sudo -u www-data php artisan optimize:clear >/dev/null

OUT=$(sudo -u www-data php -r 'require "vendor/autoload.php"; $a = require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap(); $r = app("router")->getRoutes(); $r->refreshNameLookups(); $x = $r->getByName("notifications.index"); echo "ACT " . ($x ? $x->getActionName() : "") . PHP_EOL;' 2>&1)
ACT=$(echo "$OUT" | grep '^ACT' | sed 's/^ACT //')
echo "Notify route: ${ACT:-not found}"
case "$ACT" in *NotifyController*) ;; *) echo "FAIL: /notifications not pointing to the new page"; echo "$OUT" | tail -15; restore ;; esac
CODE=$(curl -s -o /dev/null -w '%{http_code}' -k https://shipxpeed.tech/notifications)
echo "Page check (logged out, expect 302): $CODE"
[ "$CODE" = "500" ] && restore
echo "NOTIFY_DONE backup=$BK"
