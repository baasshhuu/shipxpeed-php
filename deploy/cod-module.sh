#!/usr/bin/env bash
# COD remittance module: D+7 standard (no deduction), D+4 (0.99%) / D+2 (1.29%) early COD the seller
# activates, COD generated on the delivered date, low-wallet top-up to ₹100, admin payout list with
# bank details + Excel, mark paid (all / selected) with seller email.
# Safe to run twice. Backs up first and restores automatically if any check fails.
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=origin/main
BK=/root/pre-cod-module-$(date +%Y%m%d-%H%M%S).tgz

FILES="
database/migrations/2026_11_02_000001_cod_module.php
app/Support/Cod/CodRemittance.php
app/Console/Commands/CodRun.php
app/Exports/CodPayoutExport.php
app/Http/Controllers/Cod/SellerCodController.php
app/Http/Controllers/Cod/AdminCodController.php
routes/cod.php
resources/views/cod/seller.blade.php
resources/views/cod/admin.blade.php
resources/views/email/cod_paid.blade.php
app/Providers/XpeedServiceProvider.php
app/Support/Xpeed/Kit.php
app/Support/Xpeed/Protection.php
app/Http/Controllers/Xpeed/SellerSuiteController.php
app/Http/Controllers/selleradmin/ValueServicesController.php
resources/views/sellerdashboard/vas/early-cod.blade.php
resources/views/admin/xpeed/services.blade.php
"

echo "Shipxpeed COD module installer"
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
git cat-file -e "$REF:deploy/cod-module.sh" || { echo "FAIL: latest code not found on $REF"; exit 1; }

EXIST=""; NEW=""
for f in $FILES; do if [ -e "$f" ]; then EXIST="$EXIST $f"; else NEW="$NEW $f"; fi; done
tar czf "$BK" $EXIST || { echo "FAIL: backup"; exit 1; }
echo "Backup: $BK"
restore() {
  echo "!! restoring backup"
  tar xzf "$BK"
  for f in $NEW; do rm -f "$f"; done       # only files that did not exist before
  php artisan route:clear >/dev/null 2>&1; php artisan view:clear >/dev/null 2>&1; php artisan config:clear >/dev/null 2>&1
  chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
  echo "RESTORED - site back as it was. home: $(curl -s -o /dev/null -w '%{http_code}' https://shipxpeed.tech/)"
  exit 1
}

for f in $FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
for f in $FILES; do case "$f" in *.blade.php) ;; *.php) php -l "$f" >/dev/null || { echo "syntax error in $f"; restore; } ;; esac; done
echo "Copied COD module files"

php artisan migrate --path=database/migrations/2026_11_02_000001_cod_module.php --force 2>&1 | tail -3
php artisan route:clear >/dev/null; php artisan view:clear >/dev/null; php artisan config:clear >/dev/null; php artisan cache:clear >/dev/null 2>&1

OUT=$(php artisan tinker --execute='
echo "TBL " . (Schema::hasTable("cod_remittances") && Schema::hasTable("cod_payouts") ? "ok" : "missing") . PHP_EOL;
$p = App\Support\Cod\CodRemittance::plans();
echo "PLAN d4 " . $p["d4"]["pct"] . " d2 " . $p["d2"]["pct"] . PHP_EOL;
$r = App\Support\Cod\CodRemittance::refresh();
echo "RUN created " . $r["created"] . " wallets " . $r["sellers"] . " amount " . $r["amount"] . PHP_EOL;
echo "STAT rows " . DB::table("cod_remittances")->count() . " due-now " . DB::table("cod_remittances")->where("status","pending")->where("net_payable",">",0)->where("due_on","<=",now()->toDateString())->sum("net_payable") . PHP_EOL;
$k = app(Illuminate\Contracts\Http\Kernel::class);
Auth::guard("web")->setUser(App\Models\User::first());
foreach (["/codremittance", "/codremittance?tab=upcoming", "/codremittance?tab=payouts", "/codremittance?tab=orders", "/dashboard"] as $u) {
  $res = $k->handle(Illuminate\Http\Request::create("https://shipxpeed.tech" . $u));
  $c = $res->getStatusCode(); $x = $res->exception ?? null;
  echo "CHK admin " . $u . " " . $c . ($c >= 500 && $x ? " ERR " . get_class($x) . ": " . substr($x->getMessage(), 0, 400) . " @ " . str_replace(base_path() . "/", "", $x->getFile()) . ":" . $x->getLine() : "") . PHP_EOL;
}
$s = App\Models\SellerList::where("status", 1)->first() ?? App\Models\SellerList::first();
Auth::guard("seller")->setUser($s);
foreach (["/seller/cod-remittance", "/seller/cod-remittance?tab=payouts"] as $u) {
  $res = $k->handle(Illuminate\Http\Request::create("https://shipxpeed.tech" . $u));
  $c = $res->getStatusCode(); $x = $res->exception ?? null;
  echo "CHK seller " . $u . " " . $c . ($c >= 500 && $x ? " ERR " . get_class($x) . ": " . substr($x->getMessage(), 0, 400) . " @ " . str_replace(base_path() . "/", "", $x->getFile()) . ":" . $x->getLine() : "") . PHP_EOL;
}
echo "CMD " . (array_key_exists("cod:run", Artisan::all()) ? "ok" : "missing") . PHP_EOL;
' 2>&1 | grep -E "^(TBL|PLAN|RUN|STAT|CHK|CMD)|Error|Exception")
echo "$OUT"
echo "$OUT" | grep -q "^TBL ok" || { echo "COD tables missing"; restore; }
echo "$OUT" | grep -qE "^CHK .* 5[0-9][0-9]" && { echo "a page returned a server error"; restore; }
echo "$OUT" | grep -q "^CHK admin /codremittance 200" || { echo "admin COD page check failed"; restore; }
echo "$OUT" | grep -q "^CHK seller /seller/cod-remittance 200" || { echo "seller COD page check failed"; restore; }
echo "$OUT" | grep -q "^CMD ok" || { echo "cod:run command not registered"; restore; }

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
HOMEC=$(curl -s -o /dev/null -w '%{http_code}' https://shipxpeed.tech/)
LOGIN=$(curl -s -o /dev/null -w '%{http_code}' https://shipxpeed.tech/login)
echo "Live site: home $HOMEC, login $LOGIN"
[ "$HOMEC" = "200" ] || { echo "live homepage check failed"; restore; }
case "$LOGIN" in 200|302) ;; *) echo "live login check failed"; restore ;; esac
echo "DONE. COD runs every 30 minutes with the scheduler. Backup kept at $BK"
