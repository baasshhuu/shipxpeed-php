<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Tracking\PublicTracker;
use App\Services\Tracking\TrackingMailer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Every hour: email buyers who opted in on the tracking page, once per 24 hours each,
 * with the exact courier remarks since their last email. Stops after delivery / return / cancel.
 */
class TrackingDigest extends Command
{
    protected $signature = 'tracking:digest {--limit=400} {--dry}';
    protected $description = 'Send 24-hourly tracking emails to buyers who asked for them';

    public function handle(PublicTracker $tracker, TrackingMailer $mailer): int
    {
        DB::table('tracking_subscriptions')->where('active', 1)->where('expires_at', '<', now())
            ->update(['active' => 0, 'stopped_reason' => 'expired', 'updated_at' => now()]);

        $due = DB::table('tracking_subscriptions')->where('active', 1)
            ->where(fn ($q) => $q->whereNull('last_sent_at')->orWhere('last_sent_at', '<=', now()->subMinutes(24 * 60 - 20)))
            ->orderBy('last_sent_at')->limit((int) $this->option('limit'))->get();

        $sent = 0;
        $cache = [];
        foreach ($due as $sub) {
            $order = Order::find($sub->order_id);
            if (!$order || blank($order->awb_number)) {
                DB::table('tracking_subscriptions')->where('id', $sub->id)->update(['active' => 0, 'stopped_reason' => 'order_missing', 'updated_at' => now()]);
                continue;
            }
            try {
                $track = $cache[$order->id] ??= $tracker->track($order, true);
                if ($this->option('dry')) {
                    $this->line("{$sub->email} {$order->awb_number} {$track['status_label']} (" . count($track['events']) . ' events)');
                    continue;
                }
                if ($mailer->send($sub, $order, $track)) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                $this->warn("{$order->awb_number}: " . $e->getMessage());
            }
            usleep(300000); // stay well under Gmail's sending rate
        }

        $this->info("Due: {$due->count()}, emailed: {$sent}");
        return self::SUCCESS;
    }
}
