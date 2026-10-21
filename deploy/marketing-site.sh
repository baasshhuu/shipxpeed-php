#!/usr/bin/env bash
# Deploy the Shipxpeed marketing site on the VPS. Run from the Laravel project root.
set -euo pipefail
git fetch origin
git pull --ff-only origin main
php artisan route:clear
php artisan view:clear
php artisan config:clear
# Re-cache only if the app normally runs with cached routes/config:
# php artisan config:cache && php artisan route:cache
for u in / /about /contact /hyperlocal /domestic-shipping /faq /site/assets/css/site.css /seller-login; do
  printf '%-28s %s\n' "$u" "$(curl -s -o /dev/null -w '%{http_code}' "https://shipxpeed.tech$u")"
done
