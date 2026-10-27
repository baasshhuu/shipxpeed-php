#!/usr/bin/env bash
# Removes the theme toggle include from views that are not seller-panel pages
# (emails, PDFs, label print, public website). Safe to run twice.
set -uo pipefail
cd /var/www/shipxpeed || exit 1
BK=/root/pre-theme-cleanup-$(date +%Y%m%d-%H%M%S).tgz
FILES="
resources/views/lebal.blade.php
resources/views/invoice/invoice.blade.php
resources/views/email/userverify.blade.php
resources/views/email/custom.blade.php
resources/views/sellerdashboard/invoice/invoice.blade.php
resources/views/frontend/layouts/fullwidth.blade.php
resources/views/frontend/website/layout/index.blade.php
resources/views/cod_mail.blade.php
resources/views/maintenance.blade.php
resources/views/seller/agreement-pdf.blade.php
"
EXIST=""; for f in $FILES; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
for f in $EXIST; do
  if grep -q "@include('partials.theme-toggle')" "$f"; then
    sed -i "/^@include('partials.theme-toggle')\$/d" "$f" && echo "cleaned: $f"
  fi
done
php artisan view:clear >/dev/null
echo "DONE"
