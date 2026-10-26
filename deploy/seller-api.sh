#!/usr/bin/env bash
# Puts the seller API upgrade + new API docs live on a server whose checkout has local edits.
#  - backs up every file it touches, restores automatically on any failure
#  - new files and the two docs views are copied from origin/main
#  - shared files get only the [seller-api] commits, applied as a 3-way patch on top of local edits
#  - checks: lint, routes, 401 without token, docs page + Postman download
# Usage:  bash deploy/seller-api.sh        (or: git show origin/main:deploy/seller-api.sh | bash)
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
SITE=${SITE:-https://shipxpeed.tech}
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-seller-api-$STAMP.tgz

NEW_FILES="
app/Http/Middleware/SellerApiToken.php
app/Http/Controllers/selleradmin/SellerApiController.php
app/Services/SellerApiDocs.php
resources/views/apidocs/content.blade.php
resources/views/sellerdashboard/api.blade.php
resources/views/api-docs.blade.php
"
# shared file => text that is only there once the change is applied (checked after patching)
declare -A MARK=(
  [routes/api.php]="message' => 'Not available'"
  [routes/web.php]="api.docs.postman"
  [app/Http/Controllers/Shipxpeedapi/AuthController.php]="function newToken"
  [app/Http/Controllers/Shipxpeedapi/CancelshipmentController.php]="get('api_seller')"
  [app/Http/Controllers/Shipxpeedapi/TrackController.php]="get('api_seller')"
  [app/Http/Controllers/Shipxpeedapi/ServiceabilityController.php]="withApiCourierIds"
  [app/Http/Controllers/Shipxpeedapi/NdrController.php]="'rescheduleDate' =>"
)
SHARED="${!MARK[*]}"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
COMMITS=$(git log "$REF" --reverse --no-merges -F --grep='[seller-api]' --format=%H)
[ -n "$COMMITS" ] || { echo "FAIL: [seller-api] commits not found on $REF"; exit 1; }
echo "seller-api commits: $(echo $COMMITS | wc -w)"

EXIST=""; for f in $NEW_FILES $SHARED; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK" || { echo "FAIL: backup"; exit 1; }
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $NEW_FILES; do grep -qxF "$f" <(tar tzf "$BK") || rm -f "$f"; done
  rmdir resources/views/apidocs 2>/dev/null
  php artisan optimize:clear >/dev/null 2>&1
  echo "RESTORED - nothing changed"; exit 1
}

# 1. new files + docs views
mkdir -p resources/views/apidocs app/Services
for f in $NEW_FILES; do git show "$REF:$f" > "$f.new" && mv "$f.new" "$f" || restore; done
echo "Copied new files"

# 2. shared files: apply each [seller-api] commit's change per file, skipping changes already there
#    (so re-running after new commits only adds what is new), 3-way on top of local edits
: > /tmp/seller-api-apply.log
for c in $COMMITS; do
  for f in $SHARED; do
    git diff "$c^" "$c" -- "$f" > /tmp/seller-api.patch
    [ -s /tmp/seller-api.patch ] || continue
    if git apply -R --check --whitespace=nowarn /tmp/seller-api.patch 2>/dev/null; then
      echo "  already done: $f (${c:0:7})"; continue
    fi
    git apply --3way --whitespace=nowarn /tmp/seller-api.patch 2>>/tmp/seller-api-apply.log \
      || git apply --whitespace=nowarn -C1 /tmp/seller-api.patch 2>>/tmp/seller-api-apply.log \
      || { echo "FAIL: could not apply ${c:0:7} to $f"; cat /tmp/seller-api-apply.log; restore; }
    echo "  patched: $f (${c:0:7})"
  done
done
git reset -q -- $SHARED 2>/dev/null   # keep changes in the working tree only
grep -l '^<<<<<<<' $SHARED 2>/dev/null && { echo "FAIL: merge conflict markers"; restore; }
for f in $SHARED; do grep -qF "${MARK[$f]}" "$f" || { echo "FAIL: change missing in $f"; restore; }; done
echo "Shared files up to date"

# 3. lint
for f in $NEW_FILES $SHARED; do
  case "$f" in *.blade.php) ;; *.php) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; php -l "$f"; restore; } ;; esac
done
echo "Lint OK"

chown -R www-data:www-data app routes resources 2>/dev/null
sudo -u www-data php artisan optimize:clear >/dev/null 2>&1 || php artisan optimize:clear >/dev/null 2>&1
php artisan route:list --path=api/serviceability 2>/dev/null | grep -q serviceability || { echo "FAIL: API routes not loading"; php artisan route:list --path=api/serviceability; restore; }

# 4. live checks
code() { curl -s -o /dev/null -w '%{http_code}' "$@"; }
FAILED=0
c=$(code -X POST -H 'Accept: application/json' "$SITE/api/serviceability");                       [ "$c" = 401 ] && echo "OK   no token -> 401" || { echo "FAIL no token -> $c"; FAILED=1; }
c=$(code -X POST -H 'Accept: application/json' -H 'Authorization: Bearer x' "$SITE/api/cancel-shipment" -d awb=12345); [ "$c" = 401 ] && echo "OK   cancel needs token -> 401" || { echo "FAIL cancel without valid token -> $c"; FAILED=1; }
c=$(code -X POST -H 'Accept: application/json' "$SITE/api/track-shipment" -d awb=12345);            [ "$c" = 401 ] && echo "OK   track needs token -> 401" || { echo "FAIL track without token -> $c"; FAILED=1; }
c=$(code -X POST -H 'Accept: application/json' "$SITE/api/token-generate");                         [ "$c" = 422 ] && echo "OK   token-generate validates -> 422" || { echo "FAIL token-generate -> $c"; FAILED=1; }
# GET only - never send DELETE here
c=$(code -H 'Accept: application/json' "$SITE/api/orders");                                         [ "$c" = 404 ] && echo "OK   /api/orders disabled -> 404" || { echo "FAIL /api/orders -> $c"; FAILED=1; }
c=$(code -H 'Accept: application/json' "$SITE/api/destroy");                                        [ "$c" = 404 ] && echo "OK   /api/destroy disabled -> 404" || { echo "FAIL /api/destroy -> $c"; FAILED=1; }
curl -s "$SITE/api-docs" | grep -q 'id="sxapi"' && echo "OK   /api-docs" || { echo "FAIL /api-docs"; FAILED=1; }
curl -s "$SITE/api-docs/postman.json" | grep -q '"Shipxpeed Seller API"' && echo "OK   Postman download" || { echo "FAIL Postman download"; FAILED=1; }
[ "$FAILED" = 0 ] || restore
echo "DONE - seller API + docs are live (backup: $BK)"
