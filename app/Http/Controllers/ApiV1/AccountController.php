<?php

namespace App\Http\Controllers\ApiV1;

use App\Http\Controllers\Controller;
use App\Models\Recharge;
use App\Models\Warehouse;
use App\Models\Webhook;
use App\Support\ApiV1\Couriers;
use App\Support\ApiV1\Respond;
use App\Support\ShipRate;
use App\Support\Xpeed\Kit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

/** Account, wallet, couriers, rate card, warehouses, webhooks. */
class AccountController extends Controller
{
    /** GET /api/v1/account - check your key; who you are and your wallet balance. */
    public function account(Request $request)
    {
        $s = Respond::seller($request);
        return Respond::ok([
            'client_id'       => (int) $s->id,
            'name'            => $s->name,
            'email'           => $s->email,
            'phone'           => $s->phone_number,
            'kyc_status'      => $s->kyc_status ?? null,
            'wallet_balance'  => round(Kit::balance((int) $s->id), 2),
            'negative_balance_allowed' => (string) ($s->negative_balance ?? '0') === '1',
            'api_version'     => 'v1',
        ]);
    }

    /** GET /api/v1/wallet */
    public function wallet(Request $request)
    {
        $s = Respond::seller($request);
        $per = min(100, max(1, (int) $request->query('per_page', 25)));
        $p = Recharge::where('seller_id', $s->id)
            ->where(fn ($w) => $w->where('type', 'Debit')->orWhere(fn ($c) => $c->where('type', 'Credit')->where('status', 1)))
            ->orderByDesc('id')->paginate($per);
        return Respond::ok([
            'balance'      => round(Kit::balance((int) $s->id), 2),
            'currency'     => 'INR',
            'page'         => $p->currentPage(),
            'per_page'     => $per,
            'total'        => $p->total(),
            'transactions' => collect($p->items())->map(fn ($r) => [
                'id'          => (int) $r->id,
                'type'        => strtolower((string) $r->type) === 'credit' ? 'credit' : 'debit',
                'amount'      => round((float) $r->amount, 2),
                'description' => $r->description ?: ($r->weight_awb ? 'Weight dispute ' . $r->weight_awb : null),
                'reference'   => $r->code ?: null,
                'created_at'  => optional($r->created_at)->toIso8601String(),
            ])->all(),
        ]);
    }

    /** GET /api/v1/couriers?type=b2c|reverse|b2b - couriers live on your account right now. */
    public function couriers(Request $request)
    {
        $type = $request->query('type');
        if ($type !== null && !in_array($type, ['b2c', 'reverse', 'b2b'], true)) {
            return Respond::error('type must be b2c, reverse or b2b.', 422, 'validation_failed');
        }
        $rows = Couriers::present(Couriers::forSeller((int) Respond::seller($request)->id, $type));
        return Respond::ok(['count' => count($rows), 'couriers' => $rows]);
    }

    /** GET /api/v1/rate-card - your zone-wise B2C rates (GST extra on these base rates). */
    public function rateCard(Request $request)
    {
        $card = ShipRate::sellerRateCard((int) Respond::seller($request)->id);
        return Respond::ok([
            'zones'      => array_values($card['zones']),
            'slab_grams' => 500,
            'gst_percent' => 18,
            'formula'    => 'first 500 g + each additional 500 g; COD = max(cod fixed, cod % of COD amount); 18% GST added on top',
            'couriers'   => array_map(fn ($c) => [
                'courier_code' => Couriers::code($c['name']),
                'courier_name' => $c['name'],
                'zones'        => array_map(fn ($z) => [
                    'first_500g'       => $z['first'],
                    'additional_500g'  => $z['additional'],
                    'cod_fixed'        => $z['cod'],
                    'cod_percent'      => $z['cod_percent'],
                ], $c['zones']),
            ], $card['couriers']),
        ]);
    }

    /** GET /api/v1/pincodes/{pincode} */
    public function pincode(Request $request, string $pincode)
    {
        if (!preg_match('/^[1-9]\d{5}$/', $pincode)) {
            return Respond::error('Pincode must be 6 digits.', 422, 'validation_failed');
        }
        $state = ShipRate::stateForPincode($pincode);
        return $state ? Respond::ok(['pincode' => $pincode, 'state' => $state])
            : Respond::error('Pincode not found.', 404, 'not_found');
    }

    /* ---------------------------- warehouses ---------------------------- */

    private static function wh(Warehouse $w): array
    {
        return [
            'warehouse_id' => (int) $w->id, 'name' => $w->address_title ?: $w->name, 'contact_name' => $w->name,
            'phone' => (string) $w->phone, 'email' => $w->email, 'address' => $w->address_line1, 'address_2' => $w->address_line2,
            'pincode' => (string) $w->pincode, 'city' => $w->city, 'state' => $w->state, 'is_default' => (bool) $w->is_default,
        ];
    }

    /** GET /api/v1/warehouses */
    public function warehouses(Request $request)
    {
        $rows = Warehouse::where('seller_id', Respond::seller($request)->id)->orderByDesc('is_default')->orderBy('id')->get();
        return Respond::ok(['count' => $rows->count(), 'warehouses' => $rows->map(fn ($w) => self::wh($w))->all()]);
    }

    /** POST /api/v1/warehouses */
    public function createWarehouse(Request $request)
    {
        $s = Respond::seller($request);
        $v = Validator::make($request->all(), [
            'name'         => 'required|string|max:100',
            'contact_name' => 'required|string|max:100',
            'phone'        => ['required', 'regex:/^(\+?91)?[6-9]\d{9}$/'],
            'email'        => 'nullable|email|max:120',
            'address'      => 'required|string|min:5|max:250',
            'address_2'    => 'nullable|string|max:250',
            'pincode'      => 'required|digits:6',
            'city'         => 'required|string|max:60',
            'state'        => 'required|string|max:60',
            'is_default'   => 'nullable|boolean',
        ]);
        if ($v->fails()) {
            return Respond::invalid($v);
        }
        $d = $v->validated();
        if (Warehouse::where('seller_id', $s->id)->where('address_title', $d['name'])->exists()) {
            return Respond::error('A warehouse with this name already exists.', 409, 'duplicate_warehouse');
        }
        $w = new Warehouse();
        $w->seller_id = $s->id;
        $w->address_title = $d['name'];
        $w->name = $d['contact_name'];
        $w->phone = Respond::phone10($d['phone']);
        $w->email = $d['email'] ?? ($s->email ?: 'support@shipxpeed.com');
        $w->address_line1 = $d['address'];
        $w->address_line2 = $d['address_2'] ?? '';
        $w->pincode = $d['pincode'];
        $w->city = $d['city'];
        $w->state = $d['state'];
        $w->country = 'India';
        foreach (['registered_name' => $d['name'], 'return_address' => $d['address'], 'return_pin' => $d['pincode'],
                  'return_city' => $d['city'], 'return_state' => $d['state'], 'return_country' => 'India'] as $col => $val) {
            if (Kit::hasCol('warehouses', $col)) {
                $w->{$col} = $val;
            }
        }
        $first = !Warehouse::where('seller_id', $s->id)->exists();
        $w->is_default = $first || !empty($d['is_default']);
        if ($w->is_default && !$first) {
            Warehouse::where('seller_id', $s->id)->update(['is_default' => false]);
        }
        $w->save();
        return Respond::ok(self::wh($w), 'Warehouse created', 201);
    }

    /* ----------------------------- webhooks ----------------------------- */

    public const EVENTS = ['shipment.status_updated'];

    /** GET /api/v1/webhooks */
    public function webhooks(Request $request)
    {
        if (!Schema::hasTable('webhooks')) {
            return Respond::ok(['webhooks' => []]);
        }
        $rows = Webhook::where('seller_id', Respond::seller($request)->id)->orderByDesc('id')->get();
        return Respond::ok(['webhooks' => $rows->map(fn ($w) => [
            'webhook_id' => (int) $w->id, 'url' => $w->webhook_url, 'active' => (string) $w->status === '1', 'events' => self::EVENTS,
        ])->all()]);
    }

    /** POST /api/v1/webhooks  {"url": "https://..."} - one URL per account; a new one replaces the old. */
    public function saveWebhook(Request $request)
    {
        $s = Respond::seller($request);
        $v = Validator::make($request->all(), ['url' => ['required', 'url', 'max:255', 'regex:#^https://#i']], ['url.regex' => 'The webhook URL must use https.']);
        if ($v->fails()) {
            return Respond::invalid($v);
        }
        if (!Schema::hasTable('webhooks')) {
            return Respond::error('Webhooks are not available yet.', 503, 'not_ready');
        }
        $w = Webhook::where('seller_id', $s->id)->first() ?? new Webhook(['seller_id' => $s->id]);
        $w->seller_id = $s->id;
        $w->webhook_url = $request->url;
        $w->status = 1;
        $w->save();
        return Respond::ok(['webhook_id' => (int) $w->id, 'url' => $w->webhook_url, 'active' => true, 'events' => self::EVENTS], 'Webhook saved');
    }

    /** POST /api/v1/webhooks/test - sends a sample event to your URL. */
    public function testWebhook(Request $request)
    {
        $s = Respond::seller($request);
        $w = Schema::hasTable('webhooks') ? Webhook::where('seller_id', $s->id)->where('status', 1)->first() : null;
        if (!$w) {
            return Respond::error('Save a webhook URL first (POST /api/v1/webhooks).', 404, 'not_found');
        }
        $sample = ['event' => 'shipment.status_updated', 'test' => true, 'data' => [
            'awb_number' => 'TEST123456789', 'client_order_id' => 'TEST-1', 'status' => 'in_transit',
            'courier_status' => 'In Transit', 'location' => 'Delhi Hub', 'time' => now()->toIso8601String(),
        ]];
        try {
            $r = Http::timeout(10)->withHeaders(['X-Shipxpeed-Event' => 'shipment.status_updated'])->post($w->webhook_url, $sample);
            return Respond::ok(['url' => $w->webhook_url, 'http_status' => $r->status(), 'delivered' => $r->successful(), 'payload' => $sample],
                $r->successful() ? 'Test event delivered' : 'Your endpoint answered HTTP ' . $r->status());
        } catch (\Throwable $e) {
            return Respond::error('Could not reach your webhook URL: ' . mb_strimwidth($e->getMessage(), 0, 120, '…'), 502, 'webhook_unreachable');
        }
    }
}
