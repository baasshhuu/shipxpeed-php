#!/usr/bin/env bash
# Puts the new seller login / sign-up / OTP / forgot / reset pages live.
# Replaces only the 6 auth view files from origin/main, then checks each page returns 200
# with the new layout. Restores the backup automatically if any page fails.
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=origin/main
SITE=https://shipxpeed.tech
BK=/root/pre-auth-ui-$(date +%Y%m%d-%H%M%S).tgz
FILES="
resources/views/frontend/selleruser/auth_layout.blade.php
resources/views/frontend/selleruser/login.blade.php
resources/views/frontend/selleruser/register.blade.php
resources/views/frontend/selleruser/forgot_password.blade.php
resources/views/frontend/selleruser/reset_password.blade.php
resources/views/email/verify_otp.blade.php
"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }

EXIST=""; for f in $FILES; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK" || { echo "FAIL: backup"; exit 1; }
restore() {
    echo "!! restoring backup"; tar xzf "$BK"
    for f in $FILES; do tar tzf "$BK" | grep -qx "$f" || rm -f "$f"; done
    php artisan view:clear >/dev/null 2>&1
    echo "RESTORED - old pages are back"; exit 1
}

for f in $FILES; do git show "$REF:$f" > "$f.new" && mv "$f.new" "$f" || restore; done
echo "Copied auth pages"
php artisan view:clear >/dev/null 2>&1 || true

FAILED=0
for u in /seller-login /seller-register /seller/forgot-password /seller/verify-otp /seller/reset-password/check-token; do
    body=$(curl -s -w '\n%{http_code}' "$SITE$u"); code=${body##*$'\n'}
    if [ "$code" = "200" ] && grep -q 'class="auth"' <<<"$body"; then printf 'OK   %s\n' "$u"; else printf 'FAIL %s (HTTP %s)\n' "$u" "$code"; FAILED=1; fi
done
[ "$FAILED" = "0" ] || restore
echo "DONE - new seller auth pages are live"
