<?php
/**
 * Old seller API booked Delhivery orders with courier_id "delhivery" / "delhivery_air", which the panel's
 * Delhivery tracking sync, NDR and weight checks never pick up (they look for "delhivery_b2c").
 *
 * This switches ONLY orders that are still on their way and were created in the last 30 days, so they start
 * getting tracking updates. Older or finished orders are left alone (updating them now would replay old
 * statuses and send buyers late WhatsApp alerts) and only counted.
 *
 * Every changed order is saved in table api_fix_delhivery_backfill first. Undo:  php deploy/old-api-delhivery-backfill.php --undo
 * Usage (from the app folder):  php deploy/old-api-delhivery-backfill.php            (dry run: counts only)
 *                               php deploy/old-api-delhivery-backfill.php --apply
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$mode = $argv[1] ?? '';
const BACKUP = 'api_fix_delhivery_backfill';

if ($mode === '--undo') {
    if (!Schema::hasTable(BACKUP)) {
        exit("Nothing to undo.\n");
    }
    $n = 0;
    foreach (DB::table(BACKUP)->get() as $b) {
        $n += Order::where('id', $b->order_id)->where('courier_id', 'delhivery_b2c')->update(['courier_id' => $b->old_courier_id]);
    }
    DB::table(BACKUP)->delete();
    exit("Restored $n orders.\n");
}

$final = fn ($o) => in_array(\App\Support\Xpeed\Kit::bucket($o), ['delivered', 'rto', 'cancelled'], true);
$since = now()->subDays(30);
$fix = [];
$older = 0;
$finished = 0;
Order::whereIn('courier_id', ['delhivery', 'delhivery_air'])->whereNotNull('awb_number')->where('awb_number', '!=', '')
    ->orderBy('id')->chunk(500, function ($rows) use (&$fix, &$older, &$finished, $final, $since) {
        foreach ($rows as $o) {
            if ($final($o)) {
                $finished++;
            } elseif ($o->created_at && $o->created_at->lt($since)) {
                $older++;
            } else {
                $fix[] = $o;
            }
        }
    });

echo 'Old-API Delhivery orders: ' . count($fix) . " in transit (last 30 days) to switch, $older older still open (left alone), $finished finished (left alone).\n";
if ($mode !== '--apply' || !$fix) {
    exit(0);
}

if (!Schema::hasTable(BACKUP)) {
    Schema::create(BACKUP, function (Blueprint $t) {
        $t->unsignedBigInteger('order_id')->primary();
        $t->string('old_courier_id', 40);
        $t->timestamp('changed_at')->nullable();
    });
}
$n = 0;
foreach ($fix as $o) {
    DB::transaction(function () use ($o, &$n) {
        DB::table(BACKUP)->insertOrIgnore(['order_id' => $o->id, 'old_courier_id' => $o->courier_id, 'changed_at' => now()]);
        // query update: no model events, so no buyer alert is sent by this change itself
        $n += Order::where('id', $o->id)->whereIn('courier_id', ['delhivery', 'delhivery_air'])->update(['courier_id' => 'delhivery_b2c']);
    });
}
echo "Switched $n orders to delhivery_b2c (backup in table " . BACKUP . "; undo with --undo).\n";
