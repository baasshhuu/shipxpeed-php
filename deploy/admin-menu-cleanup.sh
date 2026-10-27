#!/usr/bin/env bash
# Removes unused admin menu items (menu only; pages stay reachable, nothing is deleted). Re-runnable.
set -uo pipefail
cd /var/www/shipxpeed || exit 1
F="resources/views/partial/sidebar.blade.php resources/views/layouts/app.blade.php"
BK=/root/pre-menu-cleanup-$(date +%Y%m%d-%H%M%S).tgz
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
tar czf "$BK" $F && echo "Backup: $BK"
restore() { echo "!! restoring backup"; tar xzf "$BK"; php artisan view:clear >/dev/null 2>&1; echo "RESTORED - menu unchanged"; exit 1; }

git show origin/main:deploy/admin-menu-cleanup.php > /root/admin-menu-cleanup.php || restore
php /root/admin-menu-cleanup.php || restore
for x in $F; do php -l "$x" >/dev/null 2>&1 || true; done
php artisan view:clear >/dev/null

OUT=$(php artisan tinker --execute='
$k = app(Illuminate\Contracts\Http\Kernel::class);
Auth::guard("web")->setUser(App\Models\User::first());
foreach (["/dashboard", "/seller-list", "/rate-manager", "/active-slebs-add"] as $u) {
  $r = $k->handle(Illuminate\Http\Request::create("https://shipxpeed.tech" . $u));
  $c = $r->getStatusCode();
  echo "CHK " . $u . " " . $c . ($c >= 500 ? " ERR " . substr(strip_tags((string) $r->getContent()), 0, 200) : "") . PHP_EOL;
}' 2>&1 | grep -E "^CHK|Error|Exception")
echo "$OUT"
echo "$OUT" | grep -q "^CHK /dashboard 200" || { echo "dashboard check failed"; restore; }
echo "$OUT" | grep -qE "^CHK .* 5[0-9][0-9]" && { echo "a page returned a server error"; restore; }
echo "DONE. Backup kept at $BK"
