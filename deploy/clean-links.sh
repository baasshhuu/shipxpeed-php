#!/usr/bin/env bash
# Keeps site settings / permissions out of page links (shipment report, COD pages, pagination).
# Safe to run twice. Backs up first and restores automatically if any check fails.
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=origin/main
BK=/root/pre-clean-links-$(date +%Y%m%d-%H%M%S).tgz
FILES="
app/Support/CleanQuery.php
app/Support/ShipmentReport.php
app/Http/Controllers/admin/HomeController.php
app/Http/Controllers/Cod/AdminCodController.php
app/Http/Controllers/Cod/SellerCodController.php
resources/views/shipment_report.blade.php
resources/views/cod/seller.blade.php
"
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
EXIST=""; NEW=""
for f in $FILES; do if [ -e "$f" ]; then EXIST="$EXIST $f"; else NEW="$NEW $f"; fi; done
tar czf "$BK" $EXIST || { echo "FAIL: backup"; exit 1; }
echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"; for f in $NEW; do rm -f "$f"; done
  php artisan view:clear >/dev/null 2>&1; chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
  echo "RESTORED - site back as it was. home: $(curl -s -o /dev/null -w '%{http_code}' https://shipxpeed.tech/)"; exit 1
}
for f in $FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
for f in $FILES; do case "$f" in *.blade.php) ;; *.php) php -l "$f" >/dev/null || { echo "syntax error in $f"; restore; } ;; esac; done
php artisan view:clear >/dev/null
OUT=$(php artisan tinker --execute='
$k = app(Illuminate\Contracts\Http\Kernel::class);
Auth::guard("web")->setUser(App\Models\User::first());
foreach (["/shipment-report", "/codremittance", "/codremittance?tab=orders", "/codremittance?tab=payouts", "/dashboard"] as $u) {
  $res = $k->handle(Illuminate\Http\Request::create("https://shipxpeed.tech" . $u)); $c = $res->getStatusCode(); $x = $res->exception ?? null;
  $leak = str_contains((string) $res->getContent(), "site_settings") ? " LEAK" : "";
  echo "CHK admin " . $u . " " . $c . $leak . ($c >= 500 && $x ? " ERR " . get_class($x) . ": " . substr($x->getMessage(), 0, 300) : "") . PHP_EOL;
}
$s = App\Models\SellerList::where("status", 1)->first() ?? App\Models\SellerList::first();
Auth::guard("seller")->setUser($s);
$res = $k->handle(Illuminate\Http\Request::create("https://shipxpeed.tech/seller/cod-remittance")); $c = $res->getStatusCode(); $x = $res->exception ?? null;
$leak = str_contains((string) $res->getContent(), "site_settings") ? " LEAK" : "";
echo "CHK seller /seller/cod-remittance " . $c . $leak . ($c >= 500 && $x ? " ERR " . get_class($x) . ": " . substr($x->getMessage(), 0, 300) : "") . PHP_EOL;
' 2>&1 | grep -E "^CHK|Error|Exception")
echo "$OUT"
echo "$OUT" | grep -qE "^CHK .* 5[0-9][0-9]" && { echo "a page returned a server error"; restore; }
echo "$OUT" | grep -q "^CHK admin /shipment-report 200" || { echo "shipment report check failed"; restore; }
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
HOMEC=$(curl -s -o /dev/null -w '%{http_code}' https://shipxpeed.tech/)
echo "Live homepage: $HOMEC"
[ "$HOMEC" = "200" ] || { echo "live homepage check failed"; restore; }
echo "DONE. Backup kept at $BK"
