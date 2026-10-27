<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SellerList;
use App\Services\WhatsApp\AiSensyClient;
use App\Services\WhatsApp\OrderStatusNotifier;
use App\Services\WhatsApp\WhatsAppSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin › WhatsApp (AiSensy): API key, OTP + alert switches, campaign names,
 * default rates, per-seller rates and service on/off, message log, NDR queue.
 */
class WhatsAppController extends Controller
{
    public function index(Request $request)
    {
        $migrated = Schema::hasTable('whatsapp_messages');
        $sellerTable = (new SellerList)->getTable();
        $sellerCols = $migrated && Schema::hasColumn($sellerTable, 'whatsapp_alerts_active');

        $sellers = collect();
        $stats = ['sent_today' => 0, 'failed_today' => 0, 'billed_month' => 0, 'active_sellers' => 0];
        $messages = collect();
        $ndrQueue = collect();
        $editSeller = null;

        if ($migrated) {
            $q = DB::table('whatsapp_messages')->orderByDesc('id');
            if ($request->filled('status')) {
                $q->where('status', $request->status);
            }
            if ($request->filled('awb')) {
                $q->where('awb_number', trim($request->awb));
            }
            $messages = $q->limit(150)->get();
            $stats['sent_today'] = DB::table('whatsapp_messages')->where('status', 'sent')->where('created_at', '>=', today())->count();
            $stats['failed_today'] = DB::table('whatsapp_messages')->where('status', 'failed')->where('created_at', '>=', today())->count();
            $stats['billed_month'] = (float) DB::table('whatsapp_messages')->where('status', 'sent')->where('created_at', '>=', now()->startOfMonth())->sum('charge');
            $ndrQueue = DB::table('ndr_responses')->whereIn('status', ['needs_manual', 'processing'])->orderBy('responded_at')->limit(100)->get();
        }
        if ($sellerCols) {
            $sellers = SellerList::orderBy('name')->get(['id', 'name', 'email', 'phone_number', 'whatsapp_alerts_active', 'whatsapp_rates', 'whatsapp_brand_name']);
            $stats['active_sellers'] = $sellers->where('whatsapp_alerts_active', 1)->count();
            if ($request->filled('seller')) {
                $editSeller = $sellers->firstWhere('id', (int) $request->seller);
            }
        }
        $sellerNames = $sellers->pluck('name', 'id');

        return view('admin.whatsapp.index', [
            'migrated'    => $migrated && $sellerCols,
            'keySource'   => WhatsAppSettings::apiKeySource(),
            'saved'       => WhatsAppSettings::saved(),
            'events'      => WhatsAppSettings::events(),
            'otpCampaign' => WhatsAppSettings::otpCampaign(),
            'sellers'     => $sellers,
            'sellerNames' => $sellerNames,
            'editSeller'  => $editSeller,
            'messages'    => $messages,
            'ndrQueue'    => $ndrQueue,
            'stats'       => $stats,
        ]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'api_key'           => 'nullable|string|max:2000',
            'otp_enabled'       => 'required|in:0,1',
            'otp_copy_button'   => 'required|in:0,1',
            'alerts_enabled'    => 'required|in:0,1',
            'otp_campaign'      => 'nullable|string|max:80',
            'events'            => 'required|array',
            'events.*.campaign' => 'nullable|string|max:80',
            'events.*.rate'     => 'required|numeric|min:0|max:100',
            'events.*.enabled'  => 'nullable|in:1',
        ]);

        $events = [];
        foreach (array_keys(config('whatsapp.events')) as $key) {
            $e = $data['events'][$key] ?? [];
            $events[$key] = [
                'campaign' => trim((string) ($e['campaign'] ?? '')),
                'rate'     => round((float) ($e['rate'] ?? 0), 2),
                'enabled'  => !empty($e['enabled']),
            ];
        }

        $save = [
            'otp_enabled'     => $data['otp_enabled'] === '1',
            'otp_copy_button' => $data['otp_copy_button'] === '1',
            'alerts_enabled'  => $data['alerts_enabled'] === '1',
            'otp_campaign'    => trim((string) ($data['otp_campaign'] ?? '')) ?: config('whatsapp.otp.campaign'),
            'events'          => $events,
        ];
        if (filled($data['api_key'] ?? null)) {
            $save['api_key'] = trim($data['api_key']);
        }
        WhatsAppSettings::save($save);

        return redirect()->route('admin.whatsapp.index')->with('success', 'WhatsApp settings saved.');
    }

    /** Per-seller: service on/off + custom rates (blank = default). */
    public function saveSeller(Request $request, int $id)
    {
        $seller = SellerList::findOrFail($id);
        $data = $request->validate([
            'active'  => 'required|in:0,1',
            'rates'   => 'nullable|array',
            'rates.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $rates = [];
        foreach (array_keys(config('whatsapp.events')) as $key) {
            $v = $data['rates'][$key] ?? null;
            if ($v !== null && $v !== '') {
                $rates[$key] = round((float) $v, 2);
            }
        }

        $seller->whatsapp_alerts_active = (int) $data['active'];
        $seller->whatsapp_rates = $rates ? json_encode($rates) : null;
        $seller->save();

        return redirect()->route('admin.whatsapp.index', ['seller' => $id])->with('success', "Saved WhatsApp settings for {$seller->name}.");
    }

    /** Send a test message from any campaign to a number. */
    public function test(Request $request, AiSensyClient $client)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:20',
            'event' => 'required|string',
        ]);
        $phone = AiSensyClient::normalizePhone($data['phone']);
        if (!$phone) {
            return back()->with('error', 'Enter a valid 10-digit Indian mobile.');
        }

        if ($data['event'] === 'otp') {
            $code = (string) random_int(100000, 999999);
            $extra = (WhatsAppSettings::saved()['otp_copy_button'] ?? true)
                ? ['buttons' => [['type' => 'button', 'sub_type' => 'url', 'index' => 0, 'parameters' => [['type' => 'text', 'text' => $code]]]]]
                : [];
            $res = $client->send(WhatsAppSettings::otpCampaign(), $phone, 'Test', [$code], $extra);
        } else {
            $e = WhatsAppSettings::events()[$data['event']] ?? null;
            if (!$e) {
                return back()->with('error', 'Unknown event.');
            }
            $res = $client->send($e['campaign'], $phone, 'Test Buyer', [
                'Test Buyer', 'Demo Store', 'TEST-1001', 'Delhivery', '1234567890123', config('whatsapp.public_url') . '/order-details?awb=1234567890123',
            ]);
        }

        return back()->with($res['ok'] ? 'success' : 'error', $res['ok']
            ? 'Test sent to ' . AiSensyClient::mask($phone) . ' (HTTP ' . $res['http'] . ').'
            : 'AiSensy rejected it: ' . $res['error']);
    }

    /** Re-send an alert for an order (e.g. after a failed send). Charged like a normal alert. */
    public function resend(int $id)
    {
        $msg = DB::table('whatsapp_messages')->where('id', $id)->first();
        $order = $msg ? Order::find($msg->order_id) : null;
        if (!$order) {
            return back()->with('error', 'Order not found.');
        }
        $r = app(OrderStatusNotifier::class)->notify($order, $msg->event, true);
        return back()->with($r['status'] === 'sent' ? 'success' : 'error', 'Resend: ' . $r['status'] . (isset($r['reason']) ? ' – ' . $r['reason'] : ''));
    }

    public function ndrDone(int $id)
    {
        DB::table('ndr_responses')->where('id', $id)->update(['status' => 'actioned_by_ops', 'updated_at' => now()]);
        return back()->with('success', 'Marked as actioned.');
    }
}
