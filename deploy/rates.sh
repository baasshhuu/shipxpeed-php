#!/usr/bin/env bash
# Rate Manager + one price everywhere (re-runnable):
#  - Admin > Rate Manager: default / per-seller zone rates (first 500 g, additional 500 g, COD ₹, COD %),
#    Excel template with every panel courier (auto-synced) and upload.
#  - Seller rate card + rate calculator use those rates (calculator no longer uses the old XpressBees account).
#  - Delhivery + Shipclues ship-screen prices use them; the wallet debit is recomputed on the server.
# Backs up every touched file first and restores automatically if any check fails.
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-rates-$STAMP.tgz

COPY_FILES="
app/Support/RateCard.php
app/Support/ShipRate.php
app/Support/CourierAccess.php
app/Models/ZonePriceSetting.php
app/Http/Controllers/admin/RateManagerController.php
app/Exports/RateManagerTemplateExport.php
app/Imports/RateManagerSheetImport.php
resources/views/admin/ratemanager/index.blade.php
app/Http/Controllers/selleradmin/RateCardController.php
app/Http/Controllers/selleradmin/ToolsController.php
resources/views/sellerdashboard/tools/newratecalculater.blade.php
database/migrations/2026_10_27_000003_add_additional_price_to_zone_price_settings.php
"
PATCHED="routes/admin.php resources/views/partial/sidebar.blade.php app/Services/DelhiveryB2CService.php app/Services/DelhiveryB2CServiceExpress.php app/Services/ShipcluesService.php app/Http/Controllers/selleradmin/ShipmentController.php"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
[ -e app/Support/CourierAccess.php ] || { echo "FAIL: run deploy/courier-status.sh first"; exit 1; }

# Files copied whole must be unchanged on this server (or new), so no local edits are lost
for f in app/Http/Controllers/selleradmin/RateCardController.php app/Http/Controllers/selleradmin/ToolsController.php resources/views/sellerdashboard/tools/newratecalculater.blade.php app/Models/ZonePriceSetting.php; do
  # OK when the server copy equals some GitHub version of the file (no local edits to lose)
  if [ -e "$f" ]; then
    git log --format=%H "$REF" -- "$f" | while read c; do git show "$c:$f" 2>/dev/null | cmp -s - "$f" && { echo match; break; }; done | grep -q match && continue
  fi
  if [ -e "$f" ]; then
    echo "NOTE: $f has local edits on this server - stopping so nothing is lost. Send this output to Claude."; exit 1
  fi
done

EXIST=""; for f in $COPY_FILES $PATCHED; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $COPY_FILES; do tar tzf "$BK" | grep -qx "$f" || rm -f "$f"; done
  php artisan view:clear >/dev/null 2>&1; php artisan route:clear >/dev/null 2>&1
  echo "RESTORED - files back as they were"; exit 1
}

for f in $COPY_FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
echo "Copied files"

git show "$REF:deploy/rates-patch.php" > /root/rates-patch.php || restore
php /root/rates-patch.php || restore

for f in $COPY_FILES $PATCHED; do
  case "$f" in *.php) [ -e "$f" ] && { php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; restore; }; } ;; esac
done

php artisan migrate --force --path=database/migrations/2026_10_27_000003_add_additional_price_to_zone_price_settings.php || restore
php artisan view:clear >/dev/null
php artisan route:clear >/dev/null
php artisan cache:forget ratecard_keys_shipclues >/dev/null 2>&1 || true

OUT=$(php artisan tinker --execute='
$k = app(Illuminate\Contracts\Http\Kernel::class);
Auth::guard("web")->setUser(App\Models\User::first());
foreach (["/rate-manager", "/rate-manager?seller=" . (App\Models\SellerList::first()->id ?? 1), "/rate-manager/template", "/active-slebs-add"] as $u) {
  $r = $k->handle(Illuminate\Http\Request::create("https://shipxpeed.tech" . $u));
  $c = $r->getStatusCode();
  echo "CHK " . $u . " " . $c . ($c >= 500 ? " ERR " . substr(strip_tags((string) $r->getContent()), 0, 300) : "") . PHP_EOL;
}
$s = App\Models\SellerList::where("status", 1)->first() ?? App\Models\SellerList::first();
if ($s) {
  Auth::guard("seller")->setUser($s);
  $r = $k->handle(Illuminate\Http\Request::create(route("seller.ratecards", [], false) ? "https://shipxpeed.tech" . route("seller.ratecards", [], false) : "/"));
  echo "CHK seller-rate-card " . $r->getStatusCode() . PHP_EOL;
  $card = App\Support\ShipRate::sellerRateCard($s->id);
  echo "RATECARD " . collect($card["couriers"])->map(fn($c) => $c["name"] . " (" . count($c["zones"]) . " zones)")->implode(", ") . PHP_EOL;
  foreach (App\Support\ShipRate::quote($s->id, ["origin"=>"110053","destination"=>"500001","payment_type"=>"prepaid","order_amount"=>500,"weight_grams"=>500]) as $q)
    echo "QUOTE " . $q["name"] . " zone " . ($q["zone"] ?? "-") . " = Rs " . $q["total_charges"] . PHP_EOL;
}
echo "FORMULA " . App\Support\RateCard::price(["prepaid_price"=>40,"additional_price"=>35], 1200, false, 0)["courierCharge"] . PHP_EOL;
' 2>&1 | grep -E "^(CHK|RATECARD|QUOTE|FORMULA)|Error|Exception" )
echo "$OUT"
echo "$OUT" | grep -q "^CHK /rate-manager 200" || { echo "page check failed"; restore; }
echo "$OUT" | grep -qE "^CHK .* 5[0-9][0-9]" && { echo "a page returned a server error"; restore; }
echo "$OUT" | grep -q "^FORMULA 129.8" || { echo "formula check failed"; restore; }

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
echo "DONE. Backup kept at $BK"
