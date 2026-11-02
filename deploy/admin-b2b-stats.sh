#!/usr/bin/env bash
# Admin dashboard: Shipments overview (total orders, total tonnage, most used courier,
# top B2B account), B2C vs B2B cards, and the "Accounts using B2B the most" table.
# Safe to run twice. Backs up first and restores automatically if any check fails.
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=origin/main
BK=/root/pre-admin-b2b-stats-$(date +%Y%m%d-%H%M%S).tgz

FILES="
app/Support/AdminShipmentStats.php
app/Http/Controllers/admin/HomeController.php
resources/views/home.blade.php
"

echo "Shipxpeed admin dashboard stats installer"
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
git cat-file -e "$REF:deploy/admin-b2b-stats.sh" || { echo "FAIL: latest code not found on $REF"; exit 1; }

EXIST=""; for f in $FILES; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $FILES; do tar tzf "$BK" | grep -qx "$f" || rm -f "$f"; done
  php artisan view:clear >/dev/null 2>&1
  echo "RESTORED - dashboard unchanged"; exit 1
}

for f in $FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
php -l app/Support/AdminShipmentStats.php >/dev/null || restore
php -l app/Http/Controllers/admin/HomeController.php >/dev/null || restore
php artisan view:clear >/dev/null

OUT=$(php artisan tinker --execute='
$s = App\Support\AdminShipmentStats::build();
echo "STAT orders " . $s["total_orders"] . " (B2C " . $s["b2c"]["orders"] . ", B2B " . $s["b2b"]["orders"] . ")" . PHP_EOL;
echo "STAT weight " . App\Support\AdminShipmentStats::tonnage($s["total_kg"]) . PHP_EOL;
echo "STAT courier " . ($s["top_couriers"][0]["name"] ?? "-") . PHP_EOL;
echo "STAT b2b-top " . ($s["top_b2b_accounts"][0]["name"] ?? "-") . PHP_EOL;
Auth::guard("web")->setUser(App\Models\User::first());
$r = app(Illuminate\Contracts\Http\Kernel::class)->handle(Illuminate\Http\Request::create("https://shipxpeed.tech/dashboard"));
$c = (string) $r->getContent();
echo "CHK dashboard " . $r->getStatusCode() . ($r->getStatusCode() >= 500 ? " ERR " . substr(strip_tags($c), 0, 300) : "") . PHP_EOL;
echo "CHK section " . (str_contains($c, "Accounts using B2B the most") ? "yes" : "no") . PHP_EOL;
' 2>&1 | grep -E "^(CHK|STAT)|Error|Exception")
echo "$OUT"
echo "$OUT" | grep -q "^CHK dashboard 200" || { echo "dashboard check failed"; restore; }
echo "$OUT" | grep -q "^CHK section yes" || { echo "new section not on dashboard"; restore; }

echo "DONE. Backup kept at $BK"
