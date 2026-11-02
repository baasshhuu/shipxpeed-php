#!/usr/bin/env bash
# Public tracking page (/order-details) redesign for every courier + buyer "Notify me" email updates every 24 h.
#  - copies new files from origin/main, adds one require line to routes/web.php,
#    adds the hourly tracking:digest job to the scheduler, runs one migration
#  - backs up everything first and restores automatically if any step fails. Safe to run twice.
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-tracking-$STAMP.tgz
MIG=database/migrations/2026_11_02_000001_tracking_subscriptions.php
TEST_AWB=${TEST_AWB:-56874210334622}

NEW_FILES="
$MIG
app/Services/Tracking/PublicTracker.php
app/Services/Tracking/TrackingMailer.php
app/Http/Controllers/Frontend/PublicTrackController.php
app/Console/Commands/TrackingDigest.php
resources/views/tracking/public.blade.php
resources/views/emails/tracking-update.blade.php
routes/track_web.php
"
SHARED="routes/web.php app/Console/Kernel.php"

echo "Shipxpeed tracking page installer"
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
git cat-file -e "$REF:deploy/tracking-page.sh" || { echo "FAIL: latest code not found on $REF"; exit 1; }

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
// 1. routes: load track_web.php last so it replaces the old /order-details page
$r = 'routes/web.php';
$s = file_get_contents($r);
if (strpos($s, 'track_web.php') !== false) {
    echo "already there: tracking routes\n";
} else {
    file_put_contents($r, rtrim($s) . "\n\n// Public tracking page + buyer email updates (keep last: replaces the old /order-details page)\nrequire __DIR__ . '/track_web.php';\n");
    echo "patched: $r\n";
}

// 2. scheduler: hourly job that emails each subscribed buyer once every 24 hours
$k = 'app/Console/Kernel.php';
$s = file_get_contents($k);
if (strpos($s, 'tracking:digest') !== false) {
    echo "already there: tracking:digest schedule\n";
} else {
    $add = "\n        // Buyer tracking emails (opt-in on /order-details): each buyer gets one every 24 hours\n"
         . "        \$schedule->command('tracking:digest')->hourly()->withoutOverlapping(55)->runInBackground()\n"
         . "            ->appendOutputTo(storage_path('logs/tracking-digest.log'));\n";
    $n = preg_replace('/(protected function schedule\s*\(\s*Schedule \$schedule\s*\)\s*(?::\s*void)?\s*\{)/', '$1' . str_replace('$', '\\$', $add), $s, 1, $c);
    if (!$c) { fwrite(STDERR, "scheduler anchor not found in $k\n"); exit(1); }
    file_put_contents($k, $n);
    echo "patched: $k\n";
}
PHP

for f in $NEW_FILES $SHARED; do
  case "$f" in *.blade.php) ;; *.php) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; php -l "$f"; restore; } ;; esac
done
echo "Lint OK"

# Delhivery tracking token: keep it in .env (moved from the old hardcoded tracking controller if missing)
if ! grep -q '^DELHIVERY_B2C_TOKEN=.\+' .env 2>/dev/null; then
  TOK=$(grep -ohE "Token [a-f0-9]{40}" app/Http/Controllers/Frontend/TrackController.php app/Services/DelhiveryB2CService.php 2>/dev/null | head -1 | cut -d' ' -f2)
  if [ -n "$TOK" ]; then sed -i '/^DELHIVERY_B2C_TOKEN=/d' .env; echo "DELHIVERY_B2C_TOKEN=$TOK" >> .env; echo "Added DELHIVERY_B2C_TOKEN to .env"; else echo "WARN: DELHIVERY_B2C_TOKEN not set - direct Delhivery orders show saved status only"; fi
fi

chown -R www-data:www-data app config routes resources database storage bootstrap/cache 2>/dev/null
composer dump-autoload -q 2>/dev/null || true
sudo -u www-data php artisan migrate --path=$MIG --force || restore
sudo -u www-data php artisan optimize:clear >/dev/null

OUT=$(sudo -u www-data php -r 'require "vendor/autoload.php"; $a = require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap(); $r = app("router")->getRoutes(); $r->refreshNameLookups(); $x = $r->getByName("order.details"); echo "ACT " . ($x ? $x->getActionName() : "") . PHP_EOL;' 2>&1)
ACT=$(echo "$OUT" | grep '^ACT' | sed 's/^ACT //')
echo "Tracking route: ${ACT:-not found}"
case "$ACT" in *PublicTrackController*) ;; *) echo "FAIL: /order-details not pointing to the new page"; echo "$OUT" | tail -15; restore ;; esac

CODE=$(curl -s -o /tmp/track-check.html -w '%{http_code}' -k "https://shipxpeed.tech/order-details?awb=$TEST_AWB&refresh=1")
echo "Page check (AWB $TEST_AWB, expect 200): $CODE"
[ "$CODE" = "500" ] && { tail -30 storage/logs/laravel.log; restore; }
grep -q 'Tracking history' /tmp/track-check.html && echo "Page shows tracking: yes" || echo "Page shows tracking: no (check AWB)"
grep -o '<h2>[^<]*</h2>' /tmp/track-check.html | head -1

sudo -u www-data php artisan schedule:list 2>/dev/null | grep -q 'tracking:digest' && echo "Scheduler: tracking:digest hourly" || echo "WARN: tracking:digest not in schedule:list"
crontab -l -u www-data 2>/dev/null | grep -q 'schedule:run' || crontab -l 2>/dev/null | grep -q 'schedule:run' && echo "Cron: schedule:run present" || echo "WARN: no schedule:run cron found - emails will not go out every 24h"
echo "TRACKING_DONE backup=$BK"
