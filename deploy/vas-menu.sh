#!/usr/bin/env bash
# Seller sidebar: one "Value added services" button in place of Shipping notifications,
# RTO risk check, Courier preferences, Protection & early COD and the other Control Center links.
# Copies the sidebar partial + VAS left menu from origin/main and re-adds the hook to the live
# seller layout. Backs up first, restores on failure. Safe to run twice.
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
BK=/root/pre-vas-menu-$(date +%Y%m%d-%H%M%S).tgz
FILES="resources/views/partials/vas-menu-link.blade.php resources/views/sellerdashboard/vas/_nav.blade.php resources/views/sellerdashboard/vas/secure.blade.php resources/views/sellerdashboard/xpeed/protection.blade.php app/Http/Controllers/selleradmin/ValueServicesController.php"
LAYOUT=$(grep -rlE 'sxSide|sx-side' resources/views/layouts 2>/dev/null | head -1)
[ -n "$LAYOUT" ] || LAYOUT=resources/views/layouts/sellerdash.blade.php

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
[ -f routes/vas_web.php ] || { echo "FAIL: Value added services not installed - run deploy/value-services.sh first"; exit 1; }
EXIST=""; for f in $FILES $LAYOUT; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() { echo "!! restoring"; tar xzf "$BK"; php artisan view:clear >/dev/null 2>&1; echo "RESTORED"; exit 1; }

for f in $FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
if grep -q "vas-menu-link" "$LAYOUT"; then
  echo "hook already in $LAYOUT"
else
  grep -qi "</body>" "$LAYOUT" || { echo "no </body> in $LAYOUT"; restore; }
  sed -i "0,/<\/body>/I s#</body>#@includeIf('partials.vas-menu-link')\n</body>#I" "$LAYOUT" || restore
  echo "hook added to $LAYOUT"
fi
php -l app/Http/Controllers/selleradmin/ValueServicesController.php >/dev/null || restore
chown www-data:www-data $FILES "$LAYOUT" 2>/dev/null
sudo -u www-data php artisan view:clear >/dev/null || restore

CODE=$(sudo -u www-data php -r '
require "vendor/autoload.php"; $a = require "bootstrap/app.php";
$k = $a->make(Illuminate\Contracts\Http\Kernel::class); $k->bootstrap();
$s = App\Models\SellerList::where("status", 1)->first() ?: App\Models\SellerList::first();
Auth::guard("seller")->setUser($s);
$bad = [];
foreach (["/seller/services/rto-score", "/seller/services/early-cod", "/seller/services/secure", "/seller/services/brand-boost", "/seller/xp/ndr", "/seller/xp/couriers", "/seller/xp/bulk", "/notifications", "/seller/xp/reports", "/seller/xp/protection?view=claims", "/seller/weight-discrepancy"] as $u) {
  $c = $k->handle(Illuminate\Http\Request::create($u))->getStatusCode(); if ($c >= 500) $bad[] = "$u=$c";
}
$r = $k->handle(Illuminate\Http\Request::create("/seller/services/rto-score"));
echo $bad ? "ERR " . implode(",", $bad) : $r->getStatusCode() . (strpos((string) $r->getContent(), "data-vas-link") !== false ? " hook" : " nohook");
' 2>&1 | tail -1)
echo "check: $CODE"
case "$CODE" in "200 hook") echo "VAS_MENU_DONE" ;; *) restore ;; esac
