#!/usr/bin/env bash
# Moves "Fship Integration" from the admin menu into Admin > Logistics Partners. Restores on failure.
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}; cd "$APP" || exit 1
BK=/root/pre-fship-menu-$(date +%Y%m%d-%H%M%S).tgz
F="resources/views/partial/sidebar.blade.php resources/views/logistics/index.blade.php"
tar czf "$BK" $F && echo "Backup: $BK"
restore() { echo "!! restoring"; tar xzf "$BK"; sudo -u www-data php artisan view:clear >/dev/null; echo "RESTORED - nothing changed"; exit 1; }
git fetch -q origin && git show origin/main:deploy/fship-menu.php > /tmp/fship-menu.php || { echo "FAIL fetch"; exit 1; }
php /tmp/fship-menu.php "$APP" || restore
chown www-data:www-data $F
sudo -u www-data php artisan view:clear >/dev/null
sudo -u www-data php artisan view:cache >/dev/null 2>&1 || { sudo -u www-data php artisan view:cache 2>&1 | tail -5; restore; }
sudo -u www-data php artisan view:clear >/dev/null
for u in https://shipxpeed.tech/logistics https://shipxpeed.tech/fship; do
  c=$(curl -s -o /dev/null -w '%{http_code}' -k "$u"); echo "$u -> $c"; [ "$c" = 500 ] && restore
done
echo "MENU_DONE backup=$BK"
