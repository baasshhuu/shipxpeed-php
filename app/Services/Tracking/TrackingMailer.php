<?php

namespace App\Services\Tracking;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Buyer-requested tracking emails (opt-in from the public tracking page).
 * Free for the seller: the buyer asked for them. Sent at most once per 24 hours per subscription,
 * plus one final email when the shipment is delivered / returned / cancelled.
 */
class TrackingMailer
{
    public function send(object $sub, Order $order, array $track, bool $welcome = false): bool
    {
        $lastEventAt = $sub->last_event_at ? \Carbon\Carbon::parse($sub->last_event_at) : null;
        $new = array_values(array_filter($track['events'], fn ($e) => !$lastEventAt || ($e['time'] && $e['time']->gt($lastEventAt))));
        $final = in_array($track['stage'], PublicTracker::FINAL, true);

        $subject = $welcome
            ? "Tracking updates on: {$track['brand']} order #" . ltrim($track['order_no'], '# ')
            : "{$track['status_label']}: {$track['brand']} order #" . ltrim($track['order_no'], '# ');

        try {
            $html = view('emails.tracking-update', [
                't' => $track,
                'newEvents' => $welcome ? array_slice($track['events'], 0, 5) : array_slice($new, 0, 15),
                'welcome' => $welcome,
                'final' => $final,
                'noMovement' => !$welcome && !$new,
                'trackUrl' => rtrim(config('whatsapp.public_url', config('app.url')), '/') . '/order-details?awb=' . urlencode($track['awb']),
                'stopUrl' => rtrim(config('whatsapp.public_url', config('app.url')), '/') . '/order-details/unsubscribe/' . $sub->token,
            ])->render();

            Mail::html($html, function ($m) use ($sub, $subject, $track) {
                $m->to($sub->email)->subject($subject);
                if ($from = config('mail.from.address')) {
                    $m->from($from, mb_substr($track['brand'] . ' via Shipxpeed', 0, 70));
                }
            });
        } catch (\Throwable $e) {
            Log::warning('Tracking email failed', ['sub' => $sub->id, 'error' => $e->getMessage()]);
            DB::table('tracking_subscriptions')->where('id', $sub->id)->update(['last_error' => mb_substr($e->getMessage(), 0, 250), 'updated_at' => now()]);
            return false;
        }

        $newest = $track['events'][0]['time'] ?? null;
        DB::table('tracking_subscriptions')->where('id', $sub->id)->update([
            'last_sent_at' => now(),
            'last_event_at' => $newest ?: $sub->last_event_at,
            'last_status' => $track['status_label'],
            'emails_sent' => DB::raw('emails_sent + 1'),
            'last_error' => null,
            'active' => $final ? 0 : 1,
            'stopped_reason' => $final ? $track['stage'] : null,
            'updated_at' => now(),
        ]);
        return true;
    }
}
