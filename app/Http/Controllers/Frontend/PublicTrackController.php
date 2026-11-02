<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Tracking\PublicTracker;
use App\Services\Tracking\TrackingMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Public tracking page (/order-details) for every courier + buyer "email me updates" opt-in.
 */
class PublicTrackController extends Controller
{
    public function show(Request $request)
    {
        $q = trim((string) $request->query('awb', $request->query('q', '')));
        if ($q === '') {
            return view('tracking.public', ['q' => '', 'track' => null, 'error' => null]);
        }

        [$order, $error] = PublicTracker::find($q);
        $track = null;
        if ($order) {
            try {
                $track = app(PublicTracker::class)->track($order, $request->boolean('refresh'));
            } catch (\Throwable $e) {
                Log::error('Public tracking failed', ['q' => $q, 'error' => $e->getMessage()]);
                $error = 'Tracking is taking longer than usual. Please try again in a minute.';
            }
        }

        return view('tracking.public', ['q' => $q, 'track' => $track, 'error' => $error]);
    }

    /** Old POST form (/track-order) → same page. */
    public function legacy(Request $request)
    {
        return redirect()->route('order.details', ['awb' => trim((string) $request->input('awb', ''))]);
    }

    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'awb' => 'required|string|max:64',
            'email' => 'required|email:rfc|max:191',
        ]);
        if (filled($request->input('website'))) { // bot trap
            return back();
        }
        $back = route('order.details', ['awb' => $data['awb']]) . '#notify';

        [$order] = PublicTracker::find($data['awb']);
        if (!$order || blank($order->awb_number)) {
            return redirect($back)->with('notify_error', 'Updates can be turned on once this order has an AWB number.');
        }

        $email = strtolower(trim($data['email']));
        $tracker = app(PublicTracker::class);
        $track = $tracker->track($order);
        if (in_array($track['stage'], PublicTracker::FINAL, true)) {
            return redirect($back)->with('notify_error', 'This shipment is already ' . strtolower($track['status_label']) . ', so there are no more updates to send.');
        }

        $existing = DB::table('tracking_subscriptions')->where('order_id', $order->id)->where('email', $email)->first();
        if ($existing && $existing->active) {
            return redirect($back)->with('notify_ok', 'You are already subscribed. Updates go to ' . self::mask($email) . ' every 24 hours.');
        }
        if (DB::table('tracking_subscriptions')->where('order_id', $order->id)->where('active', 1)->count() >= 5) {
            return redirect($back)->with('notify_error', 'This shipment already has the maximum number of email subscribers.');
        }

        $token = $existing->token ?? Str::random(40);
        $row = [
            'order_id' => $order->id, 'seller_id' => $order->seller_id, 'awb_number' => $order->awb_number,
            'email' => $email, 'token' => $token, 'active' => 1, 'ip' => $request->ip(),
            'expires_at' => now()->addDays(45), 'updated_at' => now(),
        ];
        if ($existing) {
            DB::table('tracking_subscriptions')->where('id', $existing->id)->update($row);
            $id = $existing->id;
        } else {
            $id = DB::table('tracking_subscriptions')->insertGetId($row + ['created_at' => now()]);
        }

        // first email right away, then every 24 hours
        $sent = app(TrackingMailer::class)->send(DB::table('tracking_subscriptions')->find($id), $order, $track, true);
        if (!$sent) {
            return redirect($back)->with('notify_ok', 'Subscribed. We could not send the first email just now; your next update will arrive within 24 hours at ' . self::mask($email) . '.');
        }
        return redirect($back)->with('notify_ok', 'Done! We sent the latest update to ' . self::mask($email) . ' and will email you every 24 hours until delivery.');
    }

    public function unsubscribe(string $token)
    {
        $sub = DB::table('tracking_subscriptions')->where('token', $token)->first();
        if (!$sub) {
            return redirect()->route('order.details')->with('notify_ok', 'This link has expired. You will not receive more emails.');
        }
        DB::table('tracking_subscriptions')->where('id', $sub->id)->update(['active' => 0, 'stopped_reason' => 'unsubscribed', 'updated_at' => now()]);
        return redirect()->route('order.details', ['awb' => $sub->awb_number])
            ->with('notify_ok', 'Email updates stopped for ' . self::mask($sub->email) . '.');
    }

    public static function mask(string $email): string
    {
        [$u, $d] = array_pad(explode('@', $email, 2), 2, '');
        return mb_substr($u, 0, 2) . str_repeat('•', max(1, min(6, mb_strlen($u) - 2))) . '@' . $d;
    }
}
