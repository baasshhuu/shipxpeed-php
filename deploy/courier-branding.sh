#!/usr/bin/env bash
# Seller courier branding: real courier names + logos, never aggregator names (Shipclues, Fship ...).
# Safe on a server whose checkout has local edits:
#  - backs up every file it touches, restores automatically on any failure
#  - new files are copied from origin/main
#  - seller views get only this change (3-way patch)
#  - controllers/services get small text replacements (skipped where the text is not found)
# Usage:  bash deploy/courier-branding.sh
set -uo pipefail
APP=${APP:-/var/www/shipxpeed}
cd "$APP" || { echo "FAIL: $APP not found"; exit 1; }
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-courier-branding-$STAMP.tgz

NEW_FILES="
app/Support/CourierBrand.php
public/brands/courier-default.svg
public/brands/couriers/.gitkeep
"
VIEWS="
resources/views/sellerdashboard/courier/index.blade.php
resources/views/sellerdashboard/courier/bulk_index.blade.php
resources/views/sellerdashboard/reverse/index.blade.php
resources/views/sellerdashboard/passbook/shippingcharge.blade.php
"
CODE="
app/Http/Controllers/selleradmin/CourierController.php
app/Http/Controllers/selleradmin/ShipmentController.php
app/Http/Controllers/Shipxpeedapi/ServiceabilityController.php
app/Services/ShipcluesService.php
"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
FIRST=$(git log "$REF" --reverse --no-merges --grep='Seller courier branding' --format=%H | head -1)
[ -n "$FIRST" ] || { echo "FAIL: courier-branding commit not found on $REF"; exit 1; }

EXIST=""; for f in $NEW_FILES $VIEWS $CODE; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $NEW_FILES; do tar tzf "$BK" | grep -qx "$f" || rm -f "$f"; done
  echo "RESTORED - nothing changed"; exit 1
}

# 1. new files
mkdir -p app/Support public/brands/couriers
for f in $NEW_FILES; do git show "$REF:$f" > "$f" || restore; done
echo "Copied new files"

# 2. seller views
for f in $VIEWS; do
  if grep -q 'CourierBrand::forSeller\|CourierBrand::cleanName' "$f" 2>/dev/null && ! grep -q '__courierLabel = \\App' "$f"; then
    echo "  already up to date: $f"; continue
  fi
  if grep -q 'CourierBrand' "$f" 2>/dev/null; then
    # first version already applied -> apply only the later commits
    git diff "$FIRST" "$REF" -- "$f" > /tmp/cb-view.patch
  else
    git diff "$FIRST^" "$REF" -- "$f" > /tmp/cb-view.patch
  fi
  [ -s /tmp/cb-view.patch ] || continue
  git apply --3way --whitespace=nowarn /tmp/cb-view.patch 2>/tmp/cb-apply.log \
    || git apply --whitespace=nowarn -C1 /tmp/cb-view.patch 2>>/tmp/cb-apply.log \
    || { echo "View patch failed: $f"; cat /tmp/cb-apply.log; restore; }
  git reset -q -- "$f" 2>/dev/null
done
grep -l '^<<<<<<<' $VIEWS 2>/dev/null && { echo "FAIL: merge conflict markers"; restore; }
echo "Seller views updated"

# 3. small text replacements in controllers/services (each one skipped if its text is not present)
php -r '
$edits = [
  "app/Http/Controllers/selleradmin/CourierController.php" => [
    "https://shipxpeed.com/api/" => "https://shipxpeed.tech/api/",
  ],
  "app/Http/Controllers/selleradmin/ShipmentController.php" => [
    "\x27Shipclues assignment failed\x27" => "\x27Courier assignment failed\x27",
  ],
  "app/Http/Controllers/Shipxpeedapi/ServiceabilityController.php" => [
    "\x27provider_code\x27 => \$provider->code,\n                                \x27provider_name\x27 => \$provider->name,"
      => "\x27provider_code\x27 => \\App\\Support\\CourierBrand::isAggregator(\$provider) ? \x27shipxpeed\x27 : \$provider->code,\n                                \x27provider_name\x27 => \\App\\Support\\CourierBrand::isAggregator(\$provider) ? \x27ShipXpeed\x27 : \$provider->name,",
    "\$assignmentResult[\x27courier_name\x27] ?? \x27Shipclues\x27" => "\$assignmentResult[\x27courier_name\x27] ?? \x27Courier\x27",
  ],
  "app/Services/ShipcluesService.php" => [
    "(string) (\$r[\x27courier_name\x27] ?? \x27Shipclues\x27)" => "\\App\\Support\\CourierBrand::cleanName(\$r[\x27courier_name\x27] ?? null)",
    "\x27Shipclues is currently disabled.\x27" => "\x27This courier is currently unavailable.\x27",
    "\x27The selected Shipclues courier is not serviceable for this order.\x27" => "\x27The selected courier is not serviceable for this order.\x27",
    "\x27Shipclues could not assign an AWB: \x27 . \$this->errorText(\$shipRes)" => "\x27The courier could not assign an AWB: \x27 . \\App\\Support\\CourierBrand::cleanName(\$this->errorText(\$shipRes), \x27please try another courier\x27)",
    "\x27Shipclues booking failed: \x27 . \$e->getMessage()" => "\x27Courier booking failed. Please try again or choose another courier.\x27",
    "\x27Shipclues order id missing for AWB \x27" => "\x27Courier order reference missing for AWB \x27",
    "\x27Shipclues cancellation failed: \x27 . \$this->errorText(\$res)" => "\x27Cancellation failed: \x27 . \\App\\Support\\CourierBrand::cleanName(\$this->errorText(\$res), \x27please try again\x27)",
    "\x27Shipment cancelled successfully via Shipclues\x27" => "\x27Shipment cancelled successfully\x27",
    "(\$params[\x27provider_name\x27] ?? \x27Shipclues\x27)" => "\\App\\Support\\CourierBrand::cleanName(\$params[\x27provider_name\x27] ?? null)",
  ],
];
foreach ($edits as $file => $pairs) {
  if (!is_file($file)) { echo "  missing (skipped): $file\n"; continue; }
  $s = file_get_contents($file); $n = 0;
  foreach ($pairs as $from => $to) { $c = 0; $s = str_replace($from, $to, $s, $c); $n += $c; }
  file_put_contents($file, $s);
  echo "  $file: $n change(s)\n";
}' || restore

# 4. lint + compile views
for f in app/Support/CourierBrand.php $CODE; do
  [ -f "$f" ] && { php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; php -l "$f"; restore; }; }
done
echo "Lint OK"

chown -R www-data:www-data app resources public/brands storage bootstrap/cache 2>/dev/null
composer dump-autoload -q 2>/dev/null
sudo -u www-data php artisan optimize:clear >/dev/null
sudo -u www-data php artisan view:cache >/dev/null 2>&1 || { echo "VIEW COMPILE FAIL"; sudo -u www-data php artisan view:cache 2>&1 | tail -5; restore; }
sudo -u www-data php artisan tinker --execute="echo 'Check: '.App\Support\CourierBrand::forSeller(['provider_name'=>'Shipclues','courierName'=>'Ekart Prime 1kg'])[0].PHP_EOL;" 2>&1 | tail -1
echo "Self API: $(curl -s -o /dev/null -w '%{http_code}' 'https://shipxpeed.tech/api/couriers/serviceability?order_id=6') (200 = OK)"
echo "DEPLOY_DONE backup=$BK"
