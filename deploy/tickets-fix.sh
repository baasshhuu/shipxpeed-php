#!/usr/bin/env bash
# Fixes the live tickets table column types (status / AWB / ticket no. were numeric, so
# no ticket could ever be saved), then proves a ticket saves, inside a rolled-back test.
# Usage:  cd /var/www/shipxpeed && git fetch origin && git show origin/main:deploy/tickets-fix.sh | bash
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=origin/main
MIG=database/migrations/2026_10_27_000005_fix_ticket_column_types.php
git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }
git show "$REF:$MIG" > "$MIG" || { echo "FAIL: copy migration"; exit 1; }
php -l "$MIG" >/dev/null || { echo "LINT FAIL"; rm -f "$MIG"; exit 1; }
mysqldump "$(grep ^DB_DATABASE= .env | cut -d= -f2 | tr -d '"')" tickets > /root/pre-tickets-fix-$(date +%Y%m%d-%H%M%S).sql 2>/dev/null && echo "Tickets table backed up in /root"
php artisan migrate --force --path="$MIG" || { echo "FAIL: migration"; exit 1; }

php artisan tinker --execute='
$c = collect(DB::select("SHOW COLUMNS FROM tickets"))->whereIn("Field", ["ticket_id","order_id","status"])->map(fn($r) => $r->Field . "=" . $r->Type)->implode(" ");
echo "TYPES " . $c . PHP_EOL;
DB::beginTransaction();
try {
  $t = App\Models\Ticket::create(["seller_id" => optional(App\Models\SellerList::first())->id, "order_id" => "TEST-AWB-1", "subject" => "Other", "message" => "self test", "status" => "Pending"]);
  $t->refresh();
  echo "SAVE OK " . $t->ticket_id . " " . $t->order_id . " " . $t->status . PHP_EOL;
} catch (Throwable $e) { echo "SAVE FAIL " . substr($e->getMessage(), 0, 300) . PHP_EOL; }
DB::rollBack();
' 2>&1 | grep -E "^(TYPES|SAVE)"
php artisan view:clear >/dev/null
echo "DONE"
