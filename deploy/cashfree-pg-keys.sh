#!/usr/bin/env bash
# Sets the Cashfree Payment Gateway (wallet recharge) keys in .env, LIVE by default.
# Checks the keys with Cashfree first and changes nothing if Cashfree rejects them.
# Usage (from /var/www/shipxpeed):
#   CF_APP_ID='your App ID' CF_SECRET='your Secret Key' bash <(git show origin/main:deploy/cashfree-pg-keys.sh)
#   add CF_ENV=sandbox for Test keys
set -uo pipefail
cd /var/www/shipxpeed || { echo "FAIL: /var/www/shipxpeed not found"; exit 1; }
CF_ENV=${CF_ENV:-production}
[ -n "${CF_APP_ID:-}" ] && [ -n "${CF_SECRET:-}" ] || { echo "FAIL: set CF_APP_ID and CF_SECRET"; exit 1; }
case "$CF_APP_ID$CF_SECRET" in *" "*|*"<"*|*paste*|*your*) echo "FAIL: replace the placeholders with the real keys from Cashfree"; exit 1 ;; esac
case "$CF_ENV" in production) HOST=https://api.cashfree.com ;; sandbox) HOST=https://sandbox.cashfree.com ;; *) echo "FAIL: CF_ENV must be production or sandbox"; exit 1 ;; esac

# Ask Cashfree about an order id that does not exist: 404 = keys accepted, 401 = keys rejected
CODE=$(curl -s -o /tmp/cf-check.json -w '%{http_code}' "$HOST/pg/orders/sx_keycheck_$(date +%s)" \
  -H "x-client-id: $CF_APP_ID" -H "x-client-secret: $CF_SECRET" -H "x-api-version: 2023-08-01")
case "$CODE" in
  404|200) echo "Cashfree accepted the $CF_ENV keys" ;;
  401|403) echo "FAIL: Cashfree rejected these keys for $CF_ENV ($(head -c 200 /tmp/cf-check.json)). Check you copied the ${CF_ENV} App ID + Secret Key, and that the server IP is whitelisted if Cashfree asks for it."; rm -f /tmp/cf-check.json; exit 1 ;;
  *) echo "FAIL: unexpected reply from Cashfree ($CODE): $(head -c 200 /tmp/cf-check.json)"; rm -f /tmp/cf-check.json; exit 1 ;;
esac
rm -f /tmp/cf-check.json

cp -p .env "/root/env-before-cashfree-keys-$(date +%Y%m%d-%H%M%S)" && echo "Backup of .env saved in /root"
setenv() { if grep -q "^$1=" .env; then sed -i "s|^$1=.*|$1=$2|" .env; else printf '\n%s=%s' "$1" "$2" >> .env; fi; }
setenv CASHFREE_PG_APP_ID "$CF_APP_ID"
setenv CASHFREE_PG_SECRET_KEY "$CF_SECRET"
setenv CASHFREE_PG_ENV "$CF_ENV"

php artisan config:clear >/dev/null 2>&1; php artisan optimize:clear >/dev/null 2>&1
for svc in $(systemctl list-units --type=service --no-legend 'php*-fpm*' 2>/dev/null | awk '{print $1}'); do systemctl reload "$svc" 2>/dev/null; done
echo "CASHFREE KEYS OK ($CF_ENV) - try Recharge again"
