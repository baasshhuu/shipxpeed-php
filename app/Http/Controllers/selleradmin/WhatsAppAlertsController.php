<?php

namespace App\Http\Controllers\selleradmin;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\OrderStatusNotifier;
use App\Services\WhatsApp\WhatsAppSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seller › WhatsApp alerts: turn the paid buyer-alert service on/off, see the
 * per-message rates (set by Shipxpeed admin), sent messages and NDR replies.
 */
class WhatsAppAlertsController extends Controller
{
    public function index()
    {
        $seller = Auth::guard('seller')->user();
        $ready = Schema::hasTable('whatsapp_messages') && Schema::hasColumn($seller->getTable(), 'whatsapp_alerts_active');

        $events = [];
        foreach (WhatsAppSettings::events() as $key => $e) {
            if ($e['enabled']) {
                $events[$key] = $e + ['seller_rate' => WhatsAppSettings::rateFor($seller, $key)];
            }
        }

        $messages = collect();
        $ndr = collect();
        $month = ['sent' => 0, 'charged' => 0.0];
        if ($ready) {
            $messages = DB::table('whatsapp_messages')->where('seller_id', $seller->id)->orderByDesc('id')->limit(100)->get();
            $m = DB::table('whatsapp_messages')->where('seller_id', $seller->id)->where('status', 'sent')
                ->where('created_at', '>=', now()->startOfMonth())
                ->selectRaw('COUNT(*) c, COALESCE(SUM(charge),0) s')->first();
            $month = ['sent' => (int) $m->c, 'charged' => (float) $m->s];
            $ndr = DB::table('ndr_responses')->where('seller_id', $seller->id)->whereNotNull('action')->orderByDesc('responded_at')->limit(30)->get();
        }

        return view('sellerdashboard.whatsapp-alerts.index', [
            'seller'      => $seller,
            'ready'       => $ready,
            'active'      => (bool) ($seller->whatsapp_alerts_active ?? false),
            'platformOn'  => WhatsAppSettings::alertsEnabled(),
            'events'      => $events,
            'messages'    => $messages,
            'ndr'         => $ndr,
            'month'       => $month,
            'balance'     => OrderStatusNotifier::walletBalance($seller->id),
            'brandName'   => $seller->whatsapp_brand_name ?? '',
            'totalAmount' => OrderStatusNotifier::walletBalance($seller->id), // used by the seller layout header
        ]);
    }

    public function update(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!Schema::hasColumn($seller->getTable(), 'whatsapp_alerts_active')) {
            return back()->with('error', 'WhatsApp alerts are being set up. Please try again shortly.');
        }

        $data = $request->validate([
            'active'     => 'required|in:0,1',
            'brand_name' => 'nullable|string|max:40',
        ]);

        $seller->whatsapp_alerts_active = (int) $data['active'];
        $seller->whatsapp_brand_name = filled($data['brand_name'] ?? null) ? trim(strip_tags($data['brand_name'])) : null;
        $seller->save();

        return back()->with('success', $data['active'] === '1'
            ? 'WhatsApp alerts are ON. Your buyers will get updates for every new status change.'
            : 'WhatsApp alerts are OFF. No messages will be sent and nothing will be charged.');
    }
}
