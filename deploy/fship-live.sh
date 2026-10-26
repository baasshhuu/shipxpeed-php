#!/usr/bin/env bash
# Puts the Fship integration live on a server whose checkout has local edits.
#  - backs up every file it touches, restores automatically on any failure
#  - new Fship files are copied from origin/main
#  - shared files get only the Fship changes, applied as a 3-way patch on top of local edits
#  - runs the Fship migration, optionally stores the client key in .env
# Usage:  FSHIP_KEY=<client key> bash deploy/fship-live.sh      (FSHIP_KEY optional)
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
SHARED="
app/Console/Kernel.php
app/Http/Controllers/Frontend/TrackController.php
app/Http/Controllers/Shipxpeedapi/CancelshipmentController.php
app/Http/Controllers/Shipxpeedapi/NdrController.php
app/Http/Controllers/Shipxpeedapi/ServiceabilityController.php
app/Http/Controllers/admin/ZonePriceSettingController.php
app/Http/Controllers/selleradmin/CourierController.php
app/Http/Controllers/selleradmin/ShipmentController.php
app/Providers/CourierServiceProvider.php
config/couriers.php
routes/admin.php
"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
# Only the Fship commits (not unrelated work that landed on main later)
COMMITS=$(git log "$REF" --reverse --no-merges -i --grep='fship' --format=%H)
[ -n "$COMMITS" ] || { echo "FAIL: Fship commits not found on $REF"; exit 1; }
echo "Fship commits: $(echo $COMMITS | wc -w)"

SIDEBAR=resources/views/partial/sidebar.blade.php
EXIST=""; for f in $NEW_FILES $SHARED $SIDEBAR .env; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $NEW_FILES; do grep -qxF "$f" <(tar tzf "$BK") || rm -f "$f"; done
  echo "RESTORED - nothing changed"; exit 1
}

# 1. new files
mkdir -p resources/views/admin/fship
for f in $NEW_FILES; do git show "$REF:$f" > "$f" || restore; done
echo "Copied Fship files"

# 2. shared files: skip ones that already contain Fship, patch the rest 3-way
TODO=""
for f in $SHARED; do
  if grep -qi 'fship' "$f" 2>/dev/null; then echo "  already has Fship: $f"; else TODO="$TODO $f"; fi
done
if [ -n "$TODO" ]; then
  : > /tmp/fship-apply.log
  for c in $COMMITS; do
    git diff "$c^" "$c" -- $TODO > /tmp/fship-shared.patch
    [ -s /tmp/fship-shared.patch ] || continue
    git apply --3way --whitespace=nowarn /tmp/fship-shared.patch 2>>/tmp/fship-apply.log \
      || git apply --whitespace=nowarn -C1 /tmp/fship-shared.patch 2>>/tmp/fship-apply.log \
      || { cat /tmp/fship-apply.log; restore; }
  done
  git reset -q -- $TODO 2>/dev/null   # keep changes in the working tree only
  grep -l '^<<<<<<<' $TODO 2>/dev/null && { echo "FAIL: merge conflict markers"; restore; }
  echo "Patched shared files"
fi

# 2b. sidebar link - best effort, never blocks the deploy (works with old and new admin sidebar;
#     deploy/admin-ui.sh copies the sidebar from origin/main, which already has this link)
if [ -f "$SIDEBAR" ] && ! grep -q "admin.fship.index" "$SIDEBAR"; then
  php -r '
    $f = $argv[1]; $s = file_get_contents($f);
    $li = "        <li class=\"menu @routeis(\x27admin.fship.index\x27) active @endrouteis\">\n"
        . "            <a href=\"{{ route(\x27admin.fship.index\x27) }}\" class=\"dropdown-toggle\">\n"
        . "                <div class=\"\">\n                    <i class=\"fa-solid fa-plug\"></i>\n"
        . "                    <span>Fship Integration</span>\n                </div>\n            </a>\n        </li>\n";
    foreach (["@routeis(\x27pricesetting\x27)", "@routeis(\x27zone.price.index\x27)"] as $a) {
        $p = strpos($s, $a);
        if ($p === false) continue;
        $ls = strrpos(substr($s, 0, $p), "\n");
        $ls = $ls === false ? 0 : $ls + 1;
        file_put_contents($f, substr($s, 0, $ls) . $li . substr($s, $ls));
        echo "Sidebar link added\n"; exit(0);
    }
    fwrite(STDERR, "Sidebar anchor not found - link skipped (page still opens at /fship)\n");
  ' "$SIDEBAR"
fi

# 3. lint everything touched
for f in $NEW_FILES $SHARED; do
  case "$f" in *.php) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; php -l "$f"; restore; } ;; esac
done
echo "Lint OK"

# 4. client key in .env (optional)
if [ -n "${FSHIP_KEY:-}" ]; then
  grep -q '^FSHIP_ENV=' .env || echo 'FSHIP_ENV=production' >> .env
  if grep -q '^FSHIP_API_KEY=' .env; then sed -i "s|^FSHIP_API_KEY=.*|FSHIP_API_KEY=$FSHIP_KEY|" .env; else echo "FSHIP_API_KEY=$FSHIP_KEY" >> .env; fi
  echo "Key written to .env"
fi

chown -R www-data:www-data app config routes resources database storage bootstrap/cache 2>/dev/null
sudo -u www-data php artisan migrate --path=database/migrations/2026_10_25_000001_fship_integration.php --force || restore
sudo -u www-data php artisan optimize:clear >/dev/null   # (no config:cache: parts of the panel read env() at runtime)
sudo -u www-data php artisan route:list --name=fship 2>&1 | tail -6
sudo -u www-data php artisan fship:track && echo "fship:track command OK"
sudo -u www-data php artisan tinker --execute="\$r=app('courier.fship')->request('getallcourier',null,'GET'); echo 'Fship API: HTTP '.\$r['http'].' '.(\$r['ok']?'OK - '.count(\$r['data']).' couriers':\$r['error']).PHP_EOL;" 2>&1 | tail -2
echo "Admin page: $(curl -s -o /dev/null -w '%{http_code}' -k https://shipxpeed.tech/fship) (302 = login redirect, fine)"
echo "DEPLOY_DONE backup=$BK"
