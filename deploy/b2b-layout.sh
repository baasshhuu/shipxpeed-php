#!/usr/bin/env bash
# Fix: B2B Shipments pages opened half-hidden under the sidebar. Copies only the 5 B2B views.
# Backs up first, restores automatically if the views don't compile. Re-runnable.
# Usage:  cd /var/www/shipxpeed && git fetch origin && git show origin/main:deploy/b2b-layout.sh | bash
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=c05650663b7b
FILES="resources/views/sellerdashboard/b2b/create.blade.php resources/views/sellerdashboard/b2b/index.blade.php resources/views/sellerdashboard/b2b/rates.blade.php resources/views/sellerdashboard/b2b/show.blade.php resources/views/sellerdashboard/b2b/track.blade.php"
BK=/root/pre-b2b-layout-$(date +%Y%m%d-%H%M%S).tgz
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
tar czf "$BK" $FILES 2>/dev/null && echo "Backup: $BK"
restore() { echo "!! restoring backup"; tar xzf "$BK"; php artisan view:clear >/dev/null 2>&1; echo "RESTORED - nothing changed"; exit 1; }
for f in $FILES; do git show "$REF:$f" > "$f.new" && mv "$f.new" "$f" || { rm -f "$f.new"; restore; }; done
php artisan view:clear >/dev/null 2>&1
php artisan view:cache >/dev/null 2>&1 || { php artisan view:cache 2>&1 | tail -5; restore; }
echo "OK - B2B pages now open full width next to the menu."
