#!/usr/bin/env bash
# Booking fixes (re-runnable):
#  - Shipclues couriers: "pickup address could not be registered (Duplicate entry ...)" no longer stops
#    bookings - the existing warehouse is reused, or the address is registered under a unique name.
#  - Delhivery direct: "warehouse already exists" in any wording is accepted; warehouse/order errors show
#    Delhivery's real reason instead of "Unknown error".
#  - Every courier: the real failure reason is shown on the order (no more generic "please try again"),
#    with one-click fix buttons (Fix pickup address / Edit order / Recharge wallet / Try another courier).
#  - Weight auto-fetch cron crash (WeightDisputes::VOID) and the website Careers page error ($career).
# Applies only these changes on top of the live files (live edits are kept), backs up first,
# lint-checks every file and restores everything if anything fails.
# Usage: cd /var/www/shipxpeed && git fetch -q origin && git show origin/main:deploy/booking-fixes.sh | bash
set -uo pipefail
cd /var/www/shipxpeed || { echo "FAIL: /var/www/shipxpeed not found"; exit 1; }
BASE=1aba77f
REF=origin/main
FILES="
app/Services/ShipcluesService.php
app/Services/DelhiveryB2CService.php
app/Services/DelhiveryB2CServiceExpress.php
app/Services/DelhiveryZapdealService.php
app/Support/CourierError.php
app/Support/WeightAutoFetch.php
resources/views/frontend/website/pages/career.blade.php
resources/views/sellerdashboard/order.blade.php
"
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
git cat-file -e "$BASE^{commit}" 2>/dev/null || git fetch -q --depth=50 origin main 2>/dev/null
git cat-file -e "$BASE^{commit}" 2>/dev/null || { echo "FAIL: base commit $BASE not found"; exit 1; }

BK=/root/pre-booking-fixes-$(date +%Y%m%d-%H%M%S).tgz
EXIST=""; for f in $FILES; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() { echo "!! restoring backup"; tar xzf "$BK"; php artisan view:clear >/dev/null 2>&1; echo "RESTORED - nothing changed"; exit 1; }

DONE=""; SKIP=""
for f in $FILES; do
  P=$(mktemp); git diff "$BASE" "$REF" -- "$f" > "$P"
  if [ ! -s "$P" ]; then rm -f "$P"; continue; fi
  if [ ! -e "$f" ]; then
    git show "$REF:$f" > "$f" && DONE="$DONE $f"
  elif patch -p1 --forward --dry-run --silent < "$P" >/dev/null 2>&1; then
    patch -p1 --forward --silent --no-backup-if-mismatch < "$P" >/dev/null && DONE="$DONE $f"
  elif patch -p1 --reverse --dry-run --silent < "$P" >/dev/null 2>&1; then
    echo "already applied: $f"
  elif cmp -s "$f" <(git show "$BASE:$f"); then
    git show "$REF:$f" > "$f" && DONE="$DONE $f"
  else
    # live file differs too much to patch safely: fall back to the repo copy only for the two
    # small self-contained helpers; skip (and report) anything else
    case "$f" in
      app/Support/CourierError.php) git show "$REF:$f" > "$f" && DONE="$DONE $f" ;;
      *) SKIP="$SKIP $f" ;;
    esac
  fi
  rm -f "$P"
done

# cron crash fix also when WeightAutoFetch differs from the repo
if [ -f app/Support/WeightAutoFetch.php ] && ! grep -q "const VOID" app/Models/WeightDisputes.php 2>/dev/null; then
  sed -i "s/WeightDisputes::VOID/'void'/g" app/Support/WeightAutoFetch.php
fi

for f in $FILES; do
  case "$f" in *.php) [ -e "$f" ] && { case "$f" in *.blade.php) ;; *) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; restore; } ;; esac; } ;; esac
done
php artisan view:clear >/dev/null 2>&1
php artisan view:cache >/dev/null 2>&1 || { echo "FAIL: a Blade view does not compile"; restore; }
php artisan optimize:clear >/dev/null 2>&1

for svc in $(systemctl list-units --type=service --no-legend 'php*-fpm*' 2>/dev/null | awk '{print $1}'); do systemctl reload "$svc" 2>/dev/null && echo "Reloaded $svc"; done
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null

echo "Updated:${DONE:- none}"
[ -n "$SKIP" ] && echo "SKIPPED (live file too different, send this output to Claude):$SKIP"
grep -q "findWarehouseByName" app/Services/ShipcluesService.php && echo "Shipclues warehouse fix: LIVE" || echo "Shipclues warehouse fix: NOT live"
grep -q "already exist/i" app/Services/DelhiveryB2CService.php && echo "Delhivery fix: LIVE" || echo "Delhivery fix: NOT live"
grep -q "fixActions" resources/views/sellerdashboard/order.blade.php && echo "Fix buttons on orders: LIVE" || echo "Fix buttons on orders: NOT live"
echo "BOOKING FIXES DONE - retry order SPX#11 from Orders"
