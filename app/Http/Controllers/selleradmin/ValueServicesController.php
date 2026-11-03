<?php

namespace App\Http\Controllers\selleradmin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Xpeed\Kit;
use App\Support\Xpeed\Protection;
use App\Support\Xpeed\RtoRisk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Seller › Value Added Services (Shiprocket-style hub, one left menu):
 *  RTO Score · Early COD · Secure Shipments · Delivery Boost · Brand Boost · Notify (/notifications)
 * Each tab is a hero + the working tool underneath. Actions post to the existing
 * Control Suite / WhatsApp routes, so there is one source of truth per feature.
 */
class ValueServicesController extends Controller
{
    public const TABS = [
        'rto-score'      => ['RTO Score', 'ti ti-shield-x'],
        'ndr'            => ['NDR', 'ti ti-alert-triangle'],
        'early-cod'      => ['Early COD', 'ti ti-cash'],
        'secure'         => ['Protection', 'ti ti-shield-check'],
        'couriers'       => ['Courier preferences', 'ti ti-truck'],
        'bulk'           => ['Bulk actions', 'ti ti-stack-2'],
        'notify'         => ['Shipping notifications', 'ti ti-bell-ringing'],
        'brand-boost'    => ['Brand Boost', 'ti ti-map-pin'],
    ];

    /** Tabs that are full pages elsewhere: key => route name. */
    public const LINKED = [
        'ndr' => 'xp.ndr',
        'couriers' => 'xp.couriers',
        'bulk' => 'xp.bulk',
        'notify' => 'notifications.index',
    ];

    /** URL of a tab (null when that page is not installed). */
    public static function url(string $key): ?string
    {
        if (isset(self::LINKED[$key])) {
            return Route::has(self::LINKED[$key]) ? route(self::LINKED[$key]) : null;
        }
        return route('vas.show', $key);
    }

    /** Which tab the current request belongs to. */
    public static function current(): string
    {
        foreach (self::LINKED as $key => $r) {
            if (request()->routeIs($r . '*')) {
                return $key;
            }
        }
        if (request()->routeIs('xp.protection*')) {
            return 'secure';
        }
        if (request()->routeIs('xp.tracking*')) {
            return 'brand-boost';
        }
        if (request()->routeIs('xp.risk*')) {
            return 'rto-score';
        }
        $t = request()->route('tab');
        return is_string($t) ? $t : '';
    }

    public function index()
    {
        return redirect()->route('vas.show', 'rto-score');
    }

    public function show(Request $request, string $tab)
    {
        if (isset(self::LINKED[$tab])) {
            $u = self::url($tab);
            return $u ? redirect($u) : abort(404);
        }
        abort_unless(isset(self::TABS[$tab]), 404);
        $seller = Auth::guard('seller')->user();
        $sid = (int) $seller->id;
        $data = ['seller' => $seller, 'tab' => $tab, 'tabs' => self::TABS, 'totalAmount' => Kit::balance($sid), 'suite' => Kit::ready('xp_seller_services')];

        $data += match ($tab) {
            'rto-score' => $this->rto($request, $sid),
            'early-cod' => $this->earlyCod($sid),
            'secure' => $this->secure($sid),
            'delivery-boost' => $this->deliveryBoost($seller),
            'brand-boost' => $this->brandBoost($sid),
        };
        return view('sellerdashboard.vas.' . $tab, $data);
    }

    private function rto(Request $request, int $sid): array
    {
        $codOnly = $request->get('cod', '1') === '1';
        $orders = Order::where('seller_id', $sid)
            ->where(fn ($q) => $q->whereNull('awb_number')->orWhere('awb_number', ''))
            ->where('created_at', '>=', now()->subDays(30))
            ->when($codOnly, fn ($q) => $q->where('payment_type', 'cod'))
            ->orderByDesc('id')->limit(150)->get()
            ->filter(fn ($o) => Kit::bucket($o) === 'new');
        $rows = $orders->map(fn ($o) => ['order' => $o, 'risk' => RtoRisk::score($o)])
            ->sortByDesc(fn ($r) => $r['risk']['score'])->values();

        // your own return rate, last 90 days (delivered vs returned)
        $done = Order::where('seller_id', $sid)->whereNotNull('awb_number')->where('awb_number', '!=', '')
            ->where('created_at', '>=', now()->subDays(90))->get(['id', 'shipping_status', 'order_status', 'awb_number', 'payment_type'])
            ->map(fn ($o) => ['b' => Kit::bucket($o), 'cod' => strtolower((string) $o->payment_type) === 'cod']);
        $delivered = $done->where('b', 'delivered')->count();
        $rto = $done->where('b', 'rto')->count();

        return [
            'rows' => $rows, 'codOnly' => $codOnly,
            'counts' => ['high' => $rows->where('risk.level', 'high')->count(), 'medium' => $rows->where('risk.level', 'medium')->count(), 'low' => $rows->where('risk.level', 'low')->count()],
            'rtoRate' => ($delivered + $rto) ? round($rto * 100 / ($delivered + $rto), 1) : null,
            'closed90' => $delivered + $rto,
        ];
    }

    private function earlyCod(int $sid): array
    {
        $svc = Protection::service($sid);
        $cod = \App\Support\Cod\CodRemittance::class;
        $ready = Kit::ready('cod_remittances');
        $today = now()->toDateString();
        $pending = $ready ? (float) \DB::table('cod_remittances')->where('seller_id', $sid)->where('status', 'pending')->sum('net_payable') : 0.0;
        $dueQ = $ready ? \DB::table('cod_remittances')->where('seller_id', $sid)->where('status', 'pending')->where('due_on', '<=', $today) : null;
        return [
            'svc' => $svc, 'plans' => $cod::plans(), 'plan' => $cod::sellerPlan($sid), 'open' => true,
            'dueAmount' => $dueQ ? (float) (clone $dueQ)->sum('net_payable') : 0.0, 'dueCount' => $dueQ ? (clone $dueQ)->count() : 0, 'pendingCod' => $pending,
            'action' => Route::has('seller.cod.plan') ? route('seller.cod.plan') : null,
        ];
    }

    private function secure(int $sid): array
    {
        $svc = Protection::service($sid);
        $ready = Kit::ready('xp_protection_charges', 'xp_claims');
        $month = $ready ? DB::table('xp_protection_charges')->where('seller_id', $sid)->where('status', 'charged')
            ->where('created_at', '>=', now()->startOfMonth())->selectRaw('COUNT(*) c, COALESCE(SUM(fee),0) f')->first() : null;
        $claims = $ready ? DB::table('xp_claims')->where('seller_id', $sid)->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status')->all() : [];
        return [
            'svc' => $svc, 'month' => $month, 'claims' => $claims,
            'cfg' => [
                'open' => Kit::setting('protection_enabled') === '1', 'pct' => Kit::num('protection_fee_pct'), 'min' => Kit::num('protection_min_fee'),
                'min_value' => Kit::num('protection_min_value'), 'max_cover' => Kit::num('protection_max_cover'), 'window' => (int) Kit::num('claim_window_days'),
            ],
            'action' => Route::has('xp.protection.save') ? route('xp.protection.save') : null,
        ];
    }

    private function deliveryBoost($seller): array
    {
        $sid = (int) $seller->id;
        $ndr = Order::where('seller_id', $sid)->whereNotNull('awb_number')->where('created_at', '>=', now()->subDays(30))
            ->get(['id', 'shipping_status', 'order_status', 'awb_number'])->filter(fn ($o) => Kit::bucket($o) === 'ndr')->count();
        $responses = Schema::hasTable('ndr_responses')
            ? DB::table('ndr_responses')->where('seller_id', $sid)->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('COUNT(*) total, SUM(CASE WHEN action IS NOT NULL THEN 1 ELSE 0 END) answered')->first()
            : null;
        $waReady = Schema::hasColumn($seller->getTable(), 'whatsapp_alerts_active');
        return [
            'ndrOpen' => $ndr,
            'sentLinks' => (int) ($responses->total ?? 0), 'answered' => (int) ($responses->answered ?? 0),
            'waReady' => $waReady, 'waActive' => (bool) ($seller->whatsapp_alerts_active ?? false),
            'ndrRate' => class_exists(\App\Services\WhatsApp\WhatsAppSettings::class) ? \App\Services\WhatsApp\WhatsAppSettings::rateFor($seller, 'ndr') : 0,
            'brandName' => $seller->whatsapp_brand_name ?? '',
        ];
    }

    private function brandBoost(int $sid): array
    {
        $brand = Kit::ready('xp_tracking_brands') ? DB::table('xp_tracking_brands')->where('seller_id', $sid)->first() : null;
        $awb = Order::where('seller_id', $sid)->whereNotNull('awb_number')->where('awb_number', '!=', '')->orderByDesc('id')->value('awb_number');
        return [
            'brand' => $brand,
            'previewUrl' => $awb && Route::has('xp.track.public') ? route('xp.track.public', $awb) : null,
            'settingsUrl' => Route::has('xp.tracking') ? route('xp.tracking') : null,
        ];
    }
}
