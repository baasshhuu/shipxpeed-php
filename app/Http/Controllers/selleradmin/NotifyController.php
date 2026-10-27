<?php

namespace App\Http\Controllers\selleradmin;

use App\Http\Controllers\Controller;
use App\Services\Notify\BuyerNotifier;
use App\Services\Notify\NotifySettings;
use App\Services\WhatsApp\OrderStatusNotifier;
use App\Services\WhatsApp\WhatsAppSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Seller › Notify: WhatsApp Communication + Email & SMS Communication for buyers.
 */
class NotifyController extends Controller
{
    public function index(Request $request)
    {
        $seller = Auth::guard('seller')->user();

        $waReady = Schema::hasTable('whatsapp_messages') && Schema::hasColumn($seller->getTable(), 'whatsapp_alerts_active');
        $waEvents = [];
        foreach (WhatsAppSettings::events() as $key => $e) {
            if ($e['usable']) {
                $waEvents[$key] = $e + ['seller_rate' => WhatsAppSettings::rateFor($seller, $key)];
            }
        }
        $paid = array_filter(array_column($waEvents, 'seller_rate'), fn ($r) => $r > 0);
        $waMonth = ['sent' => 0, 'charged' => 0.0];
        if ($waReady) {
            $m = DB::table('whatsapp_messages')->where('seller_id', $seller->id)->where('status', 'sent')
                ->where('created_at', '>=', now()->startOfMonth())->selectRaw('COUNT(*) c, COALESCE(SUM(charge),0) s')->first();
            $waMonth = ['sent' => (int) $m->c, 'charged' => (float) $m->s];
        }

        $ready = NotifySettings::ready();
        $emailLog = collect();
        if ($ready) {
            $emailLog = DB::table('buyer_notifications')->where('seller_id', $seller->id)->orderByDesc('id')->limit(50)->get();
        }

        $balance = OrderStatusNotifier::walletBalance($seller->id);
        $sample = BuyerNotifier::sampleOrder($seller);

        return view('sellerdashboard.notify.index', [
            'seller'      => $seller,
            'tab'         => $request->query('tab') === 'email' ? 'email' : 'whatsapp',
            'waReady'     => $waReady,
            'waActive'    => (bool) ($seller->whatsapp_alerts_active ?? false),
            'waPlatform'  => WhatsAppSettings::alertsEnabled(),
            'waEvents'    => $waEvents,
            'waRate'      => $paid ? max($paid) : 0,
            'waMonth'     => $waMonth,
            'brandName'   => $seller->whatsapp_brand_name ?? '',
            'ready'       => $ready,
            'settings'    => NotifySettings::for($seller->id),
            'stages'      => NotifySettings::STAGES,
            'emailRate'   => (float) config('notify.email_rate', 0.5),
            'smsLive'     => (bool) config('notify.sms_enabled', false),
            'emailLog'    => $emailLog,
            'demoSms'     => BuyerNotifier::sms($seller, $sample, 'out_for_delivery'),
            'demoBrand'   => OrderStatusNotifier::brandName($seller, $sample),
            'balance'     => $balance,
            'totalAmount' => $balance, // seller layout header
        ]);
    }

    /** Auto-saved from the Email & SMS tab. */
    public function save(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!NotifySettings::ready()) {
            return response()->json(['ok' => false, 'message' => 'Notifications are being set up. Please try again shortly.'], 503);
        }
        $v = $request->validate([
            'hide_product' => 'nullable|boolean',
            'hide_price_prepaid' => 'nullable|boolean',
            'email' => 'nullable|array',
            'email.*' => 'string|max:32',
            'sms' => 'nullable|array',
            'sms.*' => 'string|max:32',
        ]);
        $s = NotifySettings::save($seller->id, $v);
        return response()->json(['ok' => true, 'settings' => $s, 'message' => 'Saved']);
    }

    /** Preview of one stage, with the seller's saved privacy settings applied. */
    public function preview(Request $request, string $channel, string $event)
    {
        $seller = Auth::guard('seller')->user();
        abort_unless(isset(NotifySettings::STAGES[$event]) && in_array($channel, ['email', 'sms'], true), 404);
        $s = NotifySettings::for($seller->id);
        // unsaved toggles on the page are passed along so the preview matches what the seller sees
        foreach (['hide_product', 'hide_price_prepaid'] as $k) {
            if ($request->has($k)) {
                $s[$k] = $request->boolean($k);
            }
        }
        $order = BuyerNotifier::sampleOrder($seller);
        if ($channel === 'sms') {
            return response()->json(['ok' => true, 'text' => BuyerNotifier::sms($seller, $order, $event)]);
        }
        $mail = BuyerNotifier::email($seller, $order, $event, $s);
        return response()->json(['ok' => true, 'subject' => $mail['subject'], 'html' => $mail['html']]);
    }

    /** Sends one stage's email to the seller's own address (free). */
    public function test(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        $v = $request->validate(['event' => 'required|string|max:32']);
        abort_unless(isset(NotifySettings::STAGES[$v['event']]), 422);
        $to = trim((string) $seller->email);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['ok' => false, 'message' => 'Your account has no valid email address.'], 422);
        }
        $mail = BuyerNotifier::email($seller, BuyerNotifier::sampleOrder($seller), $v['event'], NotifySettings::for($seller->id));
        try {
            Mail::html($mail['html'], function ($m) use ($to, $mail) {
                $m->to($to)->subject('[Test] ' . $mail['subject']);
                if ($from = config('mail.from.address')) {
                    $m->from($from, $mail['from_name']);
                }
            });
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Email could not be sent: ' . mb_substr($e->getMessage(), 0, 160)], 500);
        }
        return response()->json(['ok' => true, 'message' => "Test email sent to {$to}"]);
    }
}
