#!/usr/bin/env bash
# Cashfree wallet recharge: asks for the mobile number in the Recharge window when the seller has none
# (Cashfree needs it), saves it to the profile, and shows recharge errors inside the window.
# Patches 2 files in place. Backs up first, checks syntax, restores on failure. Re-runnable.
# Usage: cd /var/www/shipxpeed && git fetch -q origin && git show origin/main:deploy/recharge-phone-fix.sh | bash
set -uo pipefail
cd /var/www/shipxpeed || { echo "FAIL: /var/www/shipxpeed not found"; exit 1; }
C=app/Http/Controllers/selleradmin/WalletRechargeController.php
V=resources/views/partial/sellerdash/common/footer.blade.php
BK=/root/pre-recharge-phone-$(date +%Y%m%d-%H%M%S).tgz
TMP=$(mktemp -d)

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
git show origin/main:deploy/recharge-phone/patch.py > "$TMP/patch.py" || { echo "FAIL: patch.py not on origin/main"; exit 1; }
[ -f "$C" ] || { echo "FAIL: Cashfree recharge controller missing on server"; exit 1; }

tar czf "$BK" "$C" "$V" && echo "Backup: $BK"
restore() { tar xzf "$BK" -C /var/www/shipxpeed; echo "RESTORED original files"; exit 1; }

python3 "$TMP/patch.py" /var/www/shipxpeed || restore
php -l "$C" >/dev/null || { echo "FAIL: PHP syntax"; restore; }
grep -q "sx-recharge-phone-v1" "$C" && grep -q "sx-recharge-phone-v1" "$V" || { echo "FAIL: patch not applied"; restore; }
grep -q "^CASHFREE_PG_APP_ID=." .env && grep -q "^CASHFREE_PG_SECRET_KEY=." .env && echo "Cashfree keys: present in .env" || echo "WARNING: Cashfree PG keys missing in .env"

echo "Cashfree mode: $(grep "^CASHFREE_PG_ENV=" .env | cut -d= -f2 | tr -d "\"'")"
php artisan view:clear >/dev/null 2>&1; php artisan optimize:clear >/dev/null 2>&1
for svc in $(systemctl list-units --type=service --no-legend 'php*-fpm*' 2>/dev/null | awk '{print $1}'); do systemctl reload "$svc" 2>/dev/null && echo "Reloaded $svc"; done
chown -R www-data:www-data app resources storage bootstrap/cache 2>/dev/null
rm -rf "$TMP"
echo "RECHARGE FIX OK - open Recharge on the dashboard"
