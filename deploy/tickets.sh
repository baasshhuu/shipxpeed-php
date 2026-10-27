#!/usr/bin/env bash
# Puts the working support-ticket system live (re-runnable):
#  - completes the tickets table (ticket no., subject, admin reply, email sent time),
#  - seller "Add ticket" saves properly with an issue type and a ticket number (SXT-00001),
#  - every new ticket is emailed to baasshhuu@gmail.com (change with TICKET_NOTIFY_EMAIL in .env),
#  - Admin > Tickets shows all tickets with seller name/email/phone, status filter, search and reply,
#  - status change or reply by admin emails the seller and shows on the seller's ticket list.
# Backs up every touched file first and restores automatically if any check fails.
# Usage:  git fetch origin && git show origin/main:deploy/tickets.sh | bash
set -uo pipefail
cd /var/www/shipxpeed || exit 1
REF=origin/main
STAMP=$(date +%Y%m%d-%H%M%S)
BK=/root/pre-tickets-$STAMP.tgz

COPY_FILES="
app/Models/Ticket.php
app/Mail/TicketRaisedMail.php
app/Mail/TicketUpdatedMail.php
app/Http/Controllers/selleradmin/TicketController.php
app/Http/Controllers/admin/TicketCategoryController.php
resources/views/sellerdashboard/ticket/add.blade.php
resources/views/sellerdashboard/ticket/index.blade.php
resources/views/ticket/ticketindex.blade.php
resources/views/email/ticket_raised.blade.php
resources/views/email/ticket_updated.blade.php
database/migrations/2026_10_27_000004_complete_tickets_table.php
"
PATCHED="config/mail.php app/Http/Controllers/admin/HomeController.php"

git fetch -q origin || { echo "FAIL: git fetch"; exit 1; }

EXIST=""; for f in $COPY_FILES $PATCHED; do [ -e "$f" ] && EXIST="$EXIST $f"; done
tar czf "$BK" $EXIST && echo "Backup: $BK"
restore() {
  echo "!! restoring backup"; tar xzf "$BK"
  for f in $COPY_FILES; do tar tzf "$BK" | grep -qx "$f" || rm -f "$f"; done
  php artisan config:clear >/dev/null 2>&1; php artisan view:clear >/dev/null 2>&1; php artisan route:clear >/dev/null 2>&1
  echo "RESTORED - code is back as it was (the extra ticket table columns, if added, are harmless)"; exit 1
}

for f in $COPY_FILES; do mkdir -p "$(dirname "$f")"; git show "$REF:$f" > "$f" || restore; done
echo "Copied files"

php <<'PHP' || restore
<?php
// Ticket inbox setting in config/mail.php (only if missing)
$f = 'config/mail.php'; $s = file_get_contents($f);
if (strpos($s, 'ticket_notify') === false) {
    $anchor = "    'from' => [";
    if (strpos($s, $anchor) === false) { fwrite(STDERR, "mail.php anchor not found\n"); exit(1); }
    $add = "    // Every new seller support ticket is emailed to this address.\n    'ticket_notify' => env('TICKET_NOTIFY_EMAIL', 'baasshhuu@gmail.com'),\n\n";
    $s = preg_replace('/' . preg_quote($anchor, '/') . '/', $add . $anchor, $s, 1);
    file_put_contents($f, $s); echo "patched: $f\n";
}
// Dashboard ticket counters use the real status names
$f = 'app/Http/Controllers/admin/HomeController.php'; $s = file_get_contents($f); $o = $s;
$s = str_replace("Ticket::where('status', 'open')->count()", "Ticket::whereIn('status', ['Pending', 'In Progress'])->count()", $s);
$s = str_replace("Ticket::where('status', 'closed')->count()", "Ticket::whereIn('status', ['Resolved', 'Rejected'])->count()", $s);
if ($s !== $o) { file_put_contents($f, $s); echo "patched: $f\n"; }
PHP

for f in $COPY_FILES $PATCHED; do
  case "$f" in *.php) php -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL: $f"; restore; } ;; esac
done

php artisan migrate --force --path=database/migrations/2026_10_27_000004_complete_tickets_table.php || restore
php artisan config:clear >/dev/null 2>&1
php artisan view:clear >/dev/null
php artisan route:clear >/dev/null

# Open the admin and seller ticket pages inside the app and check they load
OUT=$(php artisan tinker --execute='
$k = app(Illuminate\Contracts\Http\Kernel::class);
$chk = function ($label, $url) use ($k) {
  $r = $k->handle(Illuminate\Http\Request::create($url));
  $c = $r->getStatusCode();
  echo "CHK " . $label . " " . $c . ($c >= 500 ? " ERR " . substr(strip_tags((string) $r->getContent()), 0, 300) : "") . PHP_EOL;
};
Auth::guard("web")->setUser(App\Models\User::first());
$chk("admin-tickets", route("ticket"));
$s = App\Models\SellerList::first();
if ($s) { Auth::guard("seller")->setUser($s); $chk("seller-tickets", route("seller.ticket.get")); $chk("seller-add", route("seller.ticket.add")); }
echo "COLS " . implode(",", Schema::getColumnListing("tickets")) . PHP_EOL;
echo "TOTAL " . App\Models\Ticket::count() . PHP_EOL;
' 2>&1 | grep -E "^(CHK|COLS|TOTAL)|Error|Exception" )
echo "$OUT"
echo "$OUT" | grep -q "^CHK admin-tickets 200" || { echo "admin ticket page check failed"; restore; }
echo "$OUT" | grep -qE "^CHK .* 5[0-9][0-9]" && { echo "a page returned a server error"; restore; }

# Send one test email through the panel's Gmail settings to prove delivery
MAILOUT=$(php artisan tinker --execute='
try {
  Mail::raw("The Shipxpeed support ticket system is live. Every new ticket raised by a seller will arrive at this address.", function ($m) { $m->to(config("mail.ticket_notify"))->subject("Shipxpeed: ticket emails are working"); });
  echo "MAIL OK " . config("mail.ticket_notify") . PHP_EOL;
} catch (Throwable $e) { echo "MAIL FAIL " . $e->getMessage() . PHP_EOL; }
' 2>&1 | grep -E "^MAIL")
echo "$MAILOUT"

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
echo "DONE. Backup kept at $BK"
