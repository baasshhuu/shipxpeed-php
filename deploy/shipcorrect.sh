#!/usr/bin/env bash
# ShipCorrect aggregator integration (re-runnable):
#  - live rates, one-step booking + AWB, tracking (cron every 30 min), cancel with wallet refund
#  - 11 couriers as ShipCorrect_<Courier> keys, OFF until approved on Courier Status
#  - seller ship flow, bulk ship, seller API (sxr_ ids), cancel API, tracking pages, rate manager
#  - Admin > Logistics > ShipCorrect Settings (key, connection, rate test, tracking test, API log)
# Applies only these changes on top of the live files (live edits are kept), backs up first,
# lint-checks every file and restores everything if anything fails.
# Usage: cd /var/www/shipxpeed && git fetch -q origin && git show origin/main:deploy/shipcorrect.sh | bash -s -- <SHIPCORRECT_API_KEY>
set -uo pipefail
cd /var/www/shipxpeed || { echo "FAIL: /var/www/shipxpeed not found"; exit 1; }
KEY="${1:-}"
BASE=0842cc7
REF=b868257
FILES="
app/Console/Commands/CourierHealth.php
app/Console/Commands/TrackShipCorrectOrders.php
app/Console/Kernel.php
app/Http/Controllers/Frontend/TrackController.php
app/Http/Controllers/Shipxpeedapi/CancelshipmentController.php
app/Http/Controllers/Shipxpeedapi/ServiceabilityController.php
app/Http/Controllers/admin/ActiveslebsController.php
app/Http/Controllers/admin/RateManagerController.php
app/Http/Controllers/admin/ShipCorrectController.php
app/Http/Controllers/admin/ZonePriceSettingController.php
app/Http/Controllers/selleradmin/ShipmentController.php
app/Http/Middleware/SellerApiToken.php
app/Providers/CourierServiceProvider.php
app/Services/ShipCorrectService.php
app/Services/Tracking/PublicTracker.php
app/Support/CourierAccess.php
app/Support/CourierBrand.php
app/Support/RateCard.php
app/Support/ShipRate.php
app/Support/WeightDisputeService.php
app/Support/Xpeed/Tracking.php
config/couriers.php
config/shipcorrect.php
database/migrations/2026_11_03_000001_shipcorrect_integration.php
resources/views/admin/Activeslebs/index.blade.php
resources/views/admin/shipcorrect/index.blade.php
resources/views/logistics/index.blade.php
resources/views/partial/sidebar.blade.php
routes/admin.php
"
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
for c in $BASE $REF; do
  git cat-file -e "$c^{commit}" 2>/dev/null || git fetch -q --depth=50 origin main 2>/dev/null
  git cat-file -e "$c^{commit}" 2>/dev/null || { echo "FAIL: commit $c not found"; exit 1; }
done

BK=/root/pre-shipcorrect-$(date +%Y%m%d-%H%M%S).tgz
EXIST=""; for f in $FILES .env; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
NEWFILES=""
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $NEWFILES; do rm -f "$f"; done
  php artisan optimize:clear >/dev/null 2>&1; echo "RESTORED - nothing changed"; exit 1
}

DONE=""; SKIP=""
for f in $FILES; do
  P=$(mktemp); git diff "$BASE" "$REF" -- "$f" > "$P"
  if [ ! -s "$P" ]; then rm -f "$P"; continue; fi
  if [ ! -e "$f" ]; then
    mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" && DONE="$DONE $f" && NEWFILES="$NEWFILES $f"
  elif grep -i "shipcorrect" "$f" >/dev/null 2>&1; then
    echo "already applied: $f"
  elif patch -p1 --forward --dry-run --silent < "$P" >/dev/null 2>&1; then
    patch -p1 --forward --silent --no-backup-if-mismatch < "$P" >/dev/null && DONE="$DONE $f"
  elif cmp -s "$f" <(git show "$BASE:$f"); then
    git show "$REF:$f" > "$f" && DONE="$DONE $f"
  else
    SKIP="$SKIP $f"
  fi
  rm -f "$P"
done

for f in $FILES; do
  case "$f" in *.blade.php) ;; *.php) [ -e "$f" ] && { php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; restore; }; } ;; esac
done

# API key into .env (only when given and not already set)
if [ -n "$KEY" ]; then
  if grep '^SHIPCORRECT_API_KEY=' .env >/dev/null 2>&1; then
    sed -i "s|^SHIPCORRECT_API_KEY=.*|SHIPCORRECT_API_KEY=$KEY|" .env
  else
    printf '\nSHIPCORRECT_API_KEY=%s\n' "$KEY" >> .env
  fi
  echo "SHIPCORRECT_API_KEY saved in .env"
fi

php artisan migrate --force --path=database/migrations/2026_11_03_000001_shipcorrect_integration.php 2>&1 | tail -2
php artisan tinker --execute='DB::table("logistic_providers")->where("code","shipcorrect")->update(["status"=>1]);' >/dev/null 2>&1
php artisan optimize:clear >/dev/null 2>&1
VC=$(php artisan view:cache 2>&1) || { echo "FAIL: a Blade view does not compile:"; echo "$VC" | tail -5; restore; }
php artisan config:cache >/dev/null 2>&1 || php artisan config:clear >/dev/null 2>&1
ROUTES=$(php artisan tinker --execute='echo app("router")->has("admin.shipcorrect.index") ? "ROUTE_OK" : "ROUTE_MISSING";' 2>&1 || true)
case "$ROUTES" in *ROUTE_OK*) echo "Admin routes: OK" ;; *) echo "FAIL: admin routes not registered"; echo "$ROUTES" | tail -5; restore ;; esac

for svc in $(systemctl list-units --type=service --no-legend 'php*-fpm*' 2>/dev/null | awk '{print $1}'); do systemctl reload "$svc" 2>/dev/null && echo "Reloaded $svc"; done
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null

echo "Updated:${DONE:- none}"
[ -n "$SKIP" ] && echo "SKIPPED (live file too different, send this output to Claude):$SKIP"

echo "--- live self-test ---"
php artisan tinker --execute='
$s = app("courier.shipcorrect");
echo "configured: " . ($s->isConfigured() ? "yes" : "NO - add the key") . PHP_EOL;
$r = $s->rateCalculator(["pickup_pincode"=>"110053","delivery_pincode"=>"500001","actual_weight"=>0.5,"length"=>10,"breadth"=>10,"height"=>10,"payment_type"=>"prepaid","shipment_value"=>500]);
echo "rate API: " . ($r["ok"] ? "OK, " . count($r["data"]["rates"] ?? []) . " couriers" : "FAIL " . $r["error"]) . PHP_EOL;
echo "panel row: " . (DB::table("logistic_providers")->where("code","shipcorrect")->exists() ? "yes" : "NO") . PHP_EOL;
' 2>&1 | tail -4
php artisan shipcorrect:track 2>&1 | tail -1
echo "SHIPCORRECT DONE - approve couriers in Admin > Courier Status (they start OFF)"
