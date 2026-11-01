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
LAYOUT=resources/views/layouts/sellerdash.blade.php
[ -f "$LAYOUT" ] || LAYOUT=$(grep -rlE --include='*.blade.php' 'sxSide|sx-side' resources/views/layouts 2>/dev/null | head -1)
[ -f "$LAYOUT" ] || { echo "FAIL: seller layout not found"; exit 1; }

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

# check: compile every changed Blade file and lint the result (no page requests: some pages exit early)
RES=$(sudo -u www-data php -r '
require "vendor/autoload.php"; $a = require "bootstrap/app.php";
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$bad = [];
foreach (array_slice($argv, 1) as $f) {
  if (substr($f, -10) !== ".blade.php") continue;
  try { $php = app("blade.compiler")->compileString(file_get_contents($f)); } catch (Throwable $e) { $bad[] = "$f: " . $e->getMessage(); continue; }
  $t = tempnam(sys_get_temp_dir(), "vas"); file_put_contents($t, $php);
  $o = []; exec("php -l " . escapeshellarg($t) . " 2>&1", $o, $rc); unlink($t);
  if ($rc) $bad[] = "$f: " . implode(" ", $o);
}
echo "VASCHK ", $bad ? "ERR " . implode(" | ", $bad) : (($rr = app("router")->getRoutes()) && $rr->refreshNameLookups() === null && $rr->getByName("vas.show") ? "OK" : "ERR route vas.show missing"), PHP_EOL;
' $FILES "$LAYOUT" 2>&1 | grep "^VASCHK" | tail -1)
echo "check: ${RES:-no result}"
case "$RES" in
  "VASCHK OK") echo "VAS_MENU_DONE" ;;
  *) restore ;;
esac
