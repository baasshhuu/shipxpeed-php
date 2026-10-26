#!/usr/bin/env bash
# Puts the seller courier-branding fix live: real courier names + logos, no aggregator names.
#  - backs up every file it touches, restores automatically on any failure
#  - new files are copied from origin/main
#  - shared files get only this change, applied as a 3-way patch on top of local edits
# Usage:  bash deploy/courier-branding.sh
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-courier-branding-$STAMP.tgz

NEW_FILES="
app/Support/CourierBrand.php
public/brands/courier-default.svg
public/brands/couriers/.gitkeep
"
SHARED="
app/Services/ShipcluesService.php
app/Http/Controllers/selleradmin/CourierController.php
app/Http/Controllers/selleradmin/ShipmentController.php
app/Http/Controllers/Shipxpeedapi/ServiceabilityController.php
resources/views/sellerdashboard/courier/index.blade.php
resources/views/sellerdashboard/courier/bulk_index.blade.php
resources/views/sellerdashboard/reverse/index.blade.php
resources/views/sellerdashboard/passbook/shippingcharge.blade.php
"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
COMMITS=$(git log "$REF" --reverse --no-merges --grep='Seller courier branding' --format=%H)
[ -n "$COMMITS" ] || { echo "FAIL: courier-branding commit not found on $REF"; exit 1; }

EXIST=""; for f in $NEW_FILES $SHARED; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $NEW_FILES; do tar tzf "$BK" | grep -qx "$f" || rm -f "$f"; done
  echo "RESTORED - nothing changed"; exit 1
}

# 1. new files
mkdir -p app/Support public/brands/couriers
for f in $NEW_FILES; do git show "$REF:$f" > "$f" || restore; done
echo "Copied new files"

# 2. shared files: skip ones already patched, patch the rest 3-way
TODO=""
for f in $SHARED; do
  if grep -q 'CourierBrand' "$f" 2>/dev/null; then echo "  already patched: $f"; else TODO="$TODO $f"; fi
done
if [ -n "$TODO" ]; then
  : > /tmp/cb-apply.log
  for c in $COMMITS; do
    git diff "$c^" "$c" -- $TODO > /tmp/cb-shared.patch
    [ -s /tmp/cb-shared.patch ] || continue
    git apply --3way --whitespace=nowarn /tmp/cb-shared.patch 2>>/tmp/cb-apply.log \
      || git apply --whitespace=nowarn -C1 /tmp/cb-shared.patch 2>>/tmp/cb-apply.log \
      || { cat /tmp/cb-apply.log; restore; }
  done
  git reset -q -- $TODO 2>/dev/null
  grep -l '^<<<<<<<' $TODO 2>/dev/null && { echo "FAIL: merge conflict markers"; restore; }
  echo "Patched shared files"
fi

# 3. lint
for f in $NEW_FILES $SHARED; do
  case "$f" in *.blade.php) ;; *.php) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; php -l "$f"; restore; } ;; esac
done
echo "Lint OK"

chown -R www-data:www-data app resources public/brands storage bootstrap/cache 2>/dev/null
sudo -u www-data composer dump-autoload -q 2>/dev/null || composer dump-autoload -q 2>/dev/null
sudo -u www-data php artisan optimize:clear >/dev/null
sudo -u www-data php artisan view:cache >/dev/null 2>&1 || { echo "VIEW COMPILE FAIL"; restore; }
sudo -u www-data php artisan tinker --execute="echo App\Support\CourierBrand::cleanName('Shipclues').' / '.App\Support\CourierBrand::sellerLogo('Ekart Prime').PHP_EOL;" 2>&1 | tail -1
echo "DEPLOY_DONE backup=$BK"
