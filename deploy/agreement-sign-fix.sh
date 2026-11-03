#!/usr/bin/env bash
# Fixes "The client name field is required" on the Aadhaar agreement Sign button.
# Cause: a later update copied an old Dashboard.php over the live one, bringing back the old
# acceptAgreement() that wants a typed name + address. This swaps ONLY that function back to the
# Aadhaar (DigiLocker) version. Backs up first, checks PHP syntax, restores on any failure. Re-runnable.
# Usage: cd /var/www/shipxpeed && git fetch -q origin && git show origin/main:deploy/agreement-sign-fix.sh | bash
set -uo pipefail
cd /var/www/shipxpeed || { echo "FAIL: /var/www/shipxpeed not found"; exit 1; }
F=app/Http/Controllers/selleradmin/Dashboard.php
BK=/root/pre-agreement-fix-$(date +%Y%m%d-%H%M%S).php
TMP=$(mktemp -d)

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
git show origin/main:deploy/agreement-sign/replace.py > "$TMP/replace.py" || { echo "FAIL: replace.py not on origin/main"; exit 1; }
git show origin/main:deploy/agreement-sign/acceptAgreement.php > "$TMP/fn.php" || { echo "FAIL: function not on origin/main"; exit 1; }
[ -f app/Http/Controllers/selleradmin/CashfreeController.php ] || { echo "FAIL: CashfreeController missing (DigiLocker part not installed)"; exit 1; }

cp -p "$F" "$BK" && echo "Backup: $BK"
restore() { cp -p "$BK" "$F"; echo "RESTORED original $F"; exit 1; }

python3 "$TMP/replace.py" "$F" "$TMP/fn.php" || restore
php -l "$F" >/dev/null || { echo "FAIL: PHP syntax"; restore; }
grep -q "seller.agreement.accept" routes/web.php || { echo "FAIL: agreement route missing"; restore; }

grep -q "aadhaar-sign-v2" "$F" || { echo "FAIL: new code not in $F"; restore; }

php artisan optimize:clear >/dev/null 2>&1
# reload PHP so the cached old code is dropped immediately
for svc in $(systemctl list-units --type=service --no-legend 'php*-fpm*' 2>/dev/null | awk '{print $1}'); do systemctl reload "$svc" 2>/dev/null && echo "Reloaded $svc"; done
systemctl reload apache2 2>/dev/null; systemctl reload nginx 2>/dev/null
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null
mkdir -p storage/app/public/agreements && chown www-data:www-data storage/app/public/agreements 2>/dev/null
rm -rf "$TMP"
echo "Live function: $(grep -c 'aadhaar-sign-v2' "$F") new copy in Dashboard.php"
echo "AGREEMENT FIX OK - reload the dashboard and click Sign Agreement"
