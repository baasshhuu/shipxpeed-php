#!/usr/bin/env bash
# Puts the Fship integration live on a server whose files have local edits.
# Unlike fship-live.sh it does not apply git patches: each shared file gets the
# Fship hooks inserted by anchor (deploy/fship-anchor.php), so diverged copies are fine.
#  - backs up every file it touches; restores automatically on any failure
#  - files that already contain Fship are left alone
# Usage:  bash deploy/fship-live2.sh
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-fship-$STAMP.tgz

NEW_FILES="
app/Services/FshipService.php
app/Console/Commands/TrackFshipOrders.php
app/Http/Controllers/admin/FshipController.php
config/fship.php
database/migrations/2026_10_25_000001_fship_integration.php
resources/views/admin/fship/index.blade.php
"
# Needed by FshipService; copied only when the server does not have it yet
OPTIONAL_NEW="app/Support/CourierAccess.php"
# Required hooks first; a missing anchor in these stops the deploy
CRITICAL="
config/couriers.php
app/Providers/CourierServiceProvider.php
routes/admin.php
app/Http/Controllers/Shipxpeedapi/ServiceabilityController.php
app/Http/Controllers/selleradmin/ShipmentController.php
"
EXTRA="
app/Http/Controllers/selleradmin/CourierController.php
app/Console/Kernel.php
app/Http/Controllers/Frontend/TrackController.php
app/Http/Controllers/Shipxpeedapi/CancelshipmentController.php
app/Http/Controllers/Shipxpeedapi/NdrController.php
app/Http/Controllers/admin/ZonePriceSettingController.php
"
SIDEBAR=resources/views/partial/sidebar.blade.php

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
PATCHER=/tmp/fship-anchor.php
git show "$REF:deploy/fship-anchor.php" > "$PATCHER" || { echo "FAIL: patcher not on $REF"; exit 1; }

EXIST=""; for f in $NEW_FILES $OPTIONAL_NEW $CRITICAL $EXTRA $SIDEBAR .env; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $NEW_FILES $OPTIONAL_NEW; do tar tzf "$BK" | grep -qxF "$f" || rm -f "$f"; done
  echo "RESTORED - nothing changed"; exit 1
}

# 1. new files
mkdir -p resources/views/admin/fship app/Support
for f in $NEW_FILES; do git show "$REF:$f" > "$f" || restore; done
for f in $OPTIONAL_NEW; do [ -e "$f" ] || { git show "$REF:$f" > "$f" || restore; echo "Added $f"; }; done
echo "Copied Fship files"

# 2. hooks in shared files
for f in $CRITICAL $EXTRA; do
  if [ ! -f "$f" ]; then
    case " $CRITICAL " in *" $f "*) echo "FAIL: $f missing on server"; restore;; esac
    echo "WARN $f: not on server, skipped"; continue
  fi
  if grep -qi 'fship' "$f"; then echo "OK   $f: already has Fship"; continue; fi
  if ! php "$PATCHER" "$f"; then
    case " $CRITICAL " in
      *" $f "*) echo "---- context for $f:"; grep -n "case '\|assignOrder(\|function \|Route::\|\]; *$\|bind(" "$f" | head -40; restore;;
      *) echo "WARN $f: skipped";;
    esac
  fi
  php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; php -l "$f"; restore; }
done
for f in $NEW_FILES $OPTIONAL_NEW; do case "$f" in *.php) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; restore; };; esac; done
echo "Lint OK"

# 2b. (no sidebar link: Fship opens from Admin > Logistics Partners, see deploy/fship-menu.sh)

# 3. migrate + boot checks
chown -R www-data:www-data app config routes resources database storage bootstrap/cache 2>/dev/null
sudo -u www-data php artisan migrate --path=database/migrations/2026_10_25_000001_fship_integration.php --force || restore
sudo -u www-data php artisan optimize:clear >/dev/null || restore
# Boot check without route:list (crashes on an unrelated legacy route) or tinker (needs a writable HOME)
cat > /tmp/fship-check.php <<'PHPEOF'
<?php
chdir($argv[1]);
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo Illuminate\Support\Facades\Route::has('admin.fship.index') ? "FSHIP_ROUTES_OK\n" : "FSHIP_ROUTES_MISSING\n";
app('courier.fship');
echo "FSHIP_ADAPTER_OK\n";
PHPEOF
R=$(sudo -u www-data env HOME=/tmp php /tmp/fship-check.php "$APP" 2>&1 | tail -5)
echo "$R"; echo "$R" | grep -q FSHIP_ADAPTER_OK && echo "$R" | grep -q FSHIP_ROUTES_OK || restore
for u in https://shipxpeed.tech/fship https://shipxpeed.tech/login; do
  c=$(curl -s -o /dev/null -w '%{http_code}' -k "$u"); echo "$u -> $c"
  [ "$c" = "500" ] && { echo "FAIL: panel returns 500"; tail -20 storage/logs/laravel.log; restore; }
done
echo "DEPLOY_DONE backup=$BK"
