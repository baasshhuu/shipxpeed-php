#!/usr/bin/env bash
# Puts Cashfree PG wallet recharge live (and removes PayU) on a server whose checkout has local edits.
#  - backs up every file it touches, restores automatically on any failure
#  - new files copied from origin/main, shared files get only the wallet commit's changes (3-way patch)
#  - writes the Cashfree PG keys into .env, runs the migration, checks the keys with Cashfree
# Usage:
#   CF_APP_ID='<AppID>' CF_SECRET='<Secret Key>' bash deploy/cashfree-recharge.sh
#   (add CF_ENV=sandbox to test with sandbox keys; default production)
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-cashfree-$STAMP.tgz
CF_ENV=${CF_ENV:-production}

[ -n "${CF_APP_ID:-}" ] && [ -n "${CF_SECRET:-}" ] || { echo "FAIL: set CF_APP_ID and CF_SECRET"; exit 1; }

NEW_FILES="
app/Services/CashfreePgService.php
app/Http/Controllers/selleradmin/WalletRechargeController.php
database/migrations/2026_10_27_000004_add_gateway_columns_to_recharges.php
resources/views/recharge/cashfree_checkout.blade.php
"
SHARED="
app/Http/Controllers/selleradmin/Dashboard.php
app/Http/Controllers/selleradmin/ReversepickupController.php
app/Http/Middleware/VerifyCsrfToken.php
app/Models/Recharge.php
config/cashfree.php
routes/web.php
"
REMOVED="
app/Http/Controllers/selleradmin/PaymentController.php
resources/views/payu_form.blade.php
"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
COMMIT=$(git log "$REF" -1 --no-merges --grep='^\[wallet\] Replace PayU with Cashfree' --format=%H)
[ -n "$COMMIT" ] || { echo "FAIL: wallet commit not found on $REF"; exit 1; }

EXIST=""; for f in $NEW_FILES $SHARED $REMOVED .env; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $NEW_FILES; do grep -qxF "$f" <(tar tzf "$BK") || rm -f "$f"; done
  echo "RESTORED - nothing changed"; exit 1
}

# 1. new files
mkdir -p resources/views/recharge
for f in $NEW_FILES; do git show "$REF:$f" > "$f" || restore; done
echo "Copied Cashfree files"

# 2. shared files (skip if already done)
if grep -q 'WalletRechargeController' routes/web.php 2>/dev/null; then
  echo "Shared files already updated"
else
  git diff "$COMMIT^" "$COMMIT" -- $SHARED > /tmp/cashfree-shared.patch
  git apply --3way --whitespace=nowarn /tmp/cashfree-shared.patch 2>/tmp/cashfree-apply.log \
    || git apply --whitespace=nowarn -C1 /tmp/cashfree-shared.patch 2>>/tmp/cashfree-apply.log \
    || { cat /tmp/cashfree-apply.log; restore; }
  git reset -q -- $SHARED 2>/dev/null
  grep -l '^<<<<<<<' $SHARED 2>/dev/null && { echo "FAIL: merge conflict markers"; restore; }
  echo "Patched shared files"
fi
for f in $REMOVED; do rm -f "$f"; done
echo "PayU files removed"

# 3. lint
for f in $NEW_FILES $SHARED; do
  case "$f" in *.php) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; php -l "$f"; restore; } ;; esac
done
echo "Lint OK"

# 4. keys in .env
setenv() { if grep -q "^$1=" .env; then sed -i "s|^$1=.*|$1=$2|" .env; else echo "$1=$2" >> .env; fi; }
setenv CASHFREE_PG_APP_ID "$CF_APP_ID"
setenv CASHFREE_PG_SECRET_KEY "$CF_SECRET"
setenv CASHFREE_PG_ENV "$CF_ENV"
echo "Keys written to .env ($CF_ENV)"

chown -R www-data:www-data app config routes resources database storage bootstrap/cache 2>/dev/null
sudo -u www-data php artisan migrate --path=database/migrations/2026_10_27_000004_add_gateway_columns_to_recharges.php --force || restore
sudo -u www-data php artisan optimize:clear >/dev/null
sudo -u www-data php artisan route:list --path=recharge 2>&1 | tail -5

# 5. key check: unknown order -> 404 means keys accepted, 401 means wrong keys
BASE=https://api.cashfree.com/pg; [ "$CF_ENV" = production ] || BASE=https://sandbox.cashfree.com/pg
CODE=$(curl -s -o /dev/null -w '%{http_code}' -H "x-client-id: $CF_APP_ID" -H "x-client-secret: $CF_SECRET" \
  -H 'x-api-version: 2023-08-01' "$BASE/orders/keycheck_$STAMP")
case "$CODE" in
  404) echo "Cashfree keys: OK" ;;
  401|403) echo "Cashfree keys: REJECTED (HTTP $CODE) - check AppID/Secret and CF_ENV" ;;
  *) echo "Cashfree keys: unexpected HTTP $CODE" ;;
esac
echo "DEPLOY_DONE backup=$BK"
