#!/usr/bin/env bash
# Status filter pills: icon, label and count never overlap on any seller page. Loads one shared style
# (partials/status-pills-css) from the seller layout, after each page's own CSS. Re-runnable.
# Usage: cd /var/www/shipxpeed && git fetch -q origin && git show origin/main:deploy/status-pills.sh | bash
set -uo pipefail
cd /var/www/shipxpeed || { echo "FAIL: /var/www/shipxpeed not found"; exit 1; }
L=resources/views/layouts/sellerdash.blade.php
P=resources/views/partials/status-pills-css.blade.php
rm -f resources/views/sellerdashboard/partials/status-count.blade.php.rej
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
cp -p "$L" "/root/pre-status-pills-layout-$(date +%Y%m%d-%H%M%S).blade.php"
mkdir -p resources/views/partials
git show origin/main:$P > "$P" || { echo "FAIL: style file not on origin/main"; exit 1; }
if ! grep -q "partials.status-pills-css" "$L"; then
  sed -i "0,/@yield('content')/s|@yield('content')|&\n    @includeIf('partials.status-pills-css')|" "$L"
fi
grep -q "partials.status-pills-css" "$L" || { echo "FAIL: could not add include to layout"; exit 1; }
php artisan view:clear >/dev/null 2>&1
echo "STATUS PILLS FIX LIVE - layout include: $(grep -c 'partials.status-pills-css' "$L"), style: $(grep -c 'status-pills-v3' "$P")"
