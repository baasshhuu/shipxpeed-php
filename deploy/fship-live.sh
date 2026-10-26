#!/usr/bin/env bash
# Puts the Fship integration live on the Shipxpeed server.
# The server's files carry local edits, so nothing is patched line-by-line:
#  1. backs up every file it touches (restored automatically on any failure)
#  2. copies the new Fship files from origin/main
#  3. inserts the Fship hooks at stable landmarks (deploy/fship-patch.php)
#  4. lints, migrates, clears caches, boots the app and checks key pages
# Usage:  FSHIP_KEY=<client key> bash fship-live.sh        (FSHIP_KEY optional)
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
REF=${REF:-origin/main}
SITE=${SITE:-https://shipxpeed.tech}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
STAMP=$(date +%Y%m%d-%H%M%S)
BK=${BK_DIR:-/root}/pre-fship-$STAMP.tgz
TMP=${TMPDIR:-/tmp}

WEB="sudo -u www-data"
{ id www-data >/dev/null 2>&1 && command -v sudo >/dev/null 2>&1; } || WEB=""
[ "${AS_ROOT:-0}" = 1 ] && WEB=""
art() { $WEB php artisan "$@"; }

NEW_FILES="
app/Services/FshipService.php
app/Console/Commands/TrackFshipOrders.php
app/Http/Controllers/admin/FshipController.php
config/fship.php
database/migrations/2026_10_25_000001_fship_integration.php
resources/views/admin/fship/index.blade.php
"
EDIT_FILES="
config/couriers.php
app/Providers/CourierServiceProvider.php
routes/admin.php
app/Http/Controllers/selleradmin/ShipmentController.php
app/Http/Controllers/selleradmin/CourierController.php
app/Console/Kernel.php
app/Http/Controllers/Frontend/TrackController.php
app/Http/Controllers/Shipxpeedapi/NdrController.php
app/Http/Controllers/Shipxpeedapi/CancelshipmentController.php
app/Http/Controllers/Shipxpeedapi/ServiceabilityController.php
app/Http/Controllers/admin/ZonePriceSettingController.php
resources/views/partial/sidebar.blade.php
"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
git show "$REF:deploy/fship-patch.php" > "$TMP/fship-patch.php" 2>/dev/null \
  || { echo "FAIL: deploy/fship-patch.php is not on $REF yet (merge the Fship deploy pull request first)"; exit 1; }

EXIST=""; for f in $NEW_FILES $EDIT_FILES .env; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST || { echo "FAIL: backup"; exit 1; }
echo "Backup: $BK"
restore() {
  echo "!! restoring backup"
  tar xzf "$BK"
  # process substitution, not a pipe: with pipefail a pipe into grep -q can report a miss and delete a file that existed
  for f in $NEW_FILES; do grep -qxF "$f" <(tar tzf "$BK") || rm -f -- "./${f:?}"; done
  art optimize:clear >/dev/null 2>&1
  echo "RESTORED - nothing changed"; exit 1
}

# 1. new Fship files
mkdir -p resources/views/admin/fship app/Console/Commands
for f in $NEW_FILES; do git show "$REF:$f" > "$f" || restore; done
echo "Copied Fship files"

# 2. hooks into existing files
php "$TMP/fship-patch.php" "$APP" || restore

# 3. syntax check of every PHP file touched
for f in $NEW_FILES $EDIT_FILES; do
  case "$f" in
    *.blade.php) ;;
    *.php) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; php -l "$f" 2>&1 | tail -2; restore; } ;;
  esac
done
echo "Lint OK"

# 4. client key in .env (optional)
if [ -n "${FSHIP_KEY:-}" ]; then
  grep -q "^FSHIP_ENV=" .env 2>/dev/null || echo 'FSHIP_ENV=production' >> .env
  if grep -q "^FSHIP_API_KEY=" .env 2>/dev/null; then sed -i "s|^FSHIP_API_KEY=.*|FSHIP_API_KEY=$FSHIP_KEY|" .env; else echo "FSHIP_API_KEY=$FSHIP_KEY" >> .env; fi
  echo "Key saved in .env"
fi

# 5. autoload, database, caches (no config:cache: parts of the panel read env() at runtime)
command -v composer >/dev/null 2>&1 && COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload -q --no-interaction >/dev/null 2>&1
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null
art migrate --path=database/migrations/2026_10_25_000001_fship_integration.php --force || { echo "MIGRATION FAILED"; restore; }
art optimize:clear >/dev/null 2>&1
art view:cache >/dev/null 2>&1 || { echo "VIEW COMPILE FAILED"; art view:cache 2>&1 | tail -5; restore; }

# 6. the app boots and resolves the Fship route + courier service
CHK=$(art tinker --execute="echo 'FSHIPCHK '.route('admin.fship.index').' '.get_class(app('courier.fship')).PHP_EOL;" 2>&1 | grep 'FSHIPCHK' | tail -1)
grep -q '/fship .*FshipService' <<<"$CHK" || {
  echo "Boot check failed: ${CHK:-no output}"
  art tinker --execute="echo route('admin.fship.index');" 2>&1 | tail -4
  restore
}
echo "App boots: Fship page + courier service OK"

# 7. key pages still load
BAD=0
for u in /login /admin/login /seller-login /fship; do
  c=$(curl -s -o /dev/null -w '%{http_code}' "$SITE$u")
  printf '  %-14s %s\n' "$u" "$c"
  [ "$c" -ge 500 ] 2>/dev/null && BAD=1
done
[ "$BAD" = 1 ] && { echo "A page returned a server error"; restore; }

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null
art fship:track 2>&1 | tail -1
art tinker --execute="\$r=app('courier.fship')->request('getallcourier',null,'GET'); echo 'Fship API: '.(\$r['ok'] ? 'connected - '.count(\$r['data']).' couriers' : 'HTTP '.\$r['http'].' - '.\$r['error']).PHP_EOL;" 2>&1 | grep 'Fship API' | tail -1
echo "DEPLOY_DONE backup=$BK"
