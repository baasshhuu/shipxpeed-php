<?php

namespace App\Http\Controllers\Shopifyconnect;

use App\Http\Controllers\Controller;
use App\Models\Recharge;
use App\Models\SellerList;
use App\Models\ShopifyConnection;
use App\Models\Warehouse;
use App\Services\Shopify\ShopifyClient;
use App\Services\Shopify\ShopifyOrderSync;
use App\Services\Shopify\ShopifyStatusSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Shopify channel for sellers.
 *
 * Connect flow (default, as in the "new shopify" reference doc):
 *   1. Seller creates an app in Shopify Dev Dashboard (custom distribution, own store),
 *      sets Allowed redirection URL = config('shopify.redirect_uri'), enables the scopes.
 *   2. Seller enters store domain + Client ID + Client Secret here and clicks
 *      "Connect & authorize on Shopify".
 *   3. We redirect to Shopify's approve screen; Shopify sends the seller back to
 *      /auth/shopify/callback; we exchange the code for a permanent token.
 *   4. Webhooks are registered and the first order pull runs; afterwards the
 *      scheduler (shopify:sync, every 5 min) pulls orders and pushes AWB/status.
 */
class ShopifyController extends Controller
{
    private function sellerContext(): array
    {
        $seller = Auth::guard('seller')->user();
        $total = 0;
        if ($seller && $seller->status == 1) {
            $credit = Recharge::where('seller_id', $seller->id)->where('status', 1)->where('type', 'Credit')->sum('amount');
            $debit = Recharge::where('seller_id', $seller->id)->where('type', 'Debit')->sum('amount');
            $total = $credit - $debit;
        }
        return [
            'seller'      => $seller,
            'totalAmount' => $total,
            'warehouses'  => $seller ? Warehouse::where('seller_id', $seller->id)->get() : collect(),
            'allSellers'  => SellerList::active()->get(),
            'couriers'    => 'vicky',
        ];
    }

    /* ================================================================= */
    /*  Pages                                                            */
    /* ================================================================= */

    public function channelList()
    {
        $ctx = $this->sellerContext();
        $seller = $ctx['seller'];

        $channels = ShopifyConnection::where('seller_id', $seller->id)->orderByDesc('id')->get();
        $ids = $channels->pluck('id');

        $logs = DB::table('shopify_sync_logs')->whereIn('connection_id', $ids)->orderByDesc('id')->limit(30)->get();

        $orderErrors = \App\Models\Order::where('seller_id', $seller->id)
            ->whereNotNull('shopify_order_id')->whereNotNull('shopify_sync_error')
            ->orderByDesc('updated_at')->limit(20)
            ->get(['id', 'order_number', 'shopify_order_number', 'awb_number', 'shipping_status', 'shopify_sync_error', 'updated_at']);

        return view('shopify.index', $ctx + compact('channels', 'logs', 'orderErrors'));
    }

    public function channelAdd()
    {
        return view('shopify.add-channel', $this->sellerContext());
    }

    public function shopifyIntegration(Request $request)
    {
        $ctx = $this->sellerContext();
        $reconnect = $request->filled('reconnect')
            ? ShopifyConnection::where('seller_id', $ctx['seller']->id)->find($request->reconnect)
            : null;

        return view('shopify.shopify-integration', $ctx + [
            'redirectUri' => config('shopify.redirect_uri'),
            'appUrl'      => config('shopify.app_url'),
            'scopes'      => config('shopify.scopes'),
            'reconnect'   => $reconnect,
        ]);
    }

    /* ================================================================= */
    /*  Connect                                                          */
    /* ================================================================= */

    public function shopifyConnect(Request $request)
    {
        $seller = Auth::guard('seller')->user();

        $data = $request->validate([
            'auth_method'     => 'required|in:oauth,client_credentials,legacy_token',
            'store_domain'    => 'required|string|max:255',
            'client_id'       => 'required_unless:auth_method,legacy_token|nullable|string|max:255',
            'client_secret'   => 'required_unless:auth_method,legacy_token|nullable|string|max:255',
            'access_token'    => 'required_if:auth_method,legacy_token|nullable|string|max:255',
            'sync_start_date' => 'nullable|date|before_or_equal:today',
            'notify_customer' => 'nullable|boolean',
        ], [
            'client_id.required_unless'     => 'Client ID is required.',
            'client_secret.required_unless' => 'Client Secret is required.',
            'access_token.required_if'      => 'Admin API access token is required.',
        ]);

        $shop = ShopifyClient::normalizeShop($data['store_domain']);
        if (!$shop) {
            return back()->withInput()->with('error', 'Enter your myshopify.com domain (e.g. my-store.myshopify.com) — not your custom website domain. Find it in Shopify admin › Settings › Domains.');
        }

        // Same shop already connected by another seller?
        $taken = ShopifyConnection::where('shopify_shop_domain', $shop)->where('seller_id', '!=', $seller->id)->active()->exists();
        if ($taken) {
            return back()->withInput()->with('error', 'This Shopify store is already connected to another Shipxpeed account.');
        }

        $conn = ShopifyConnection::firstOrNew(['seller_id' => $seller->id, 'shopify_shop_domain' => $shop]);
        $conn->fill([
            'store_url'       => 'https://' . $shop,
            'host_name'       => $shop,
            'auth_type'       => $data['auth_method'],
            'client_id'       => $data['client_id'] ?? null,
            'sync_start_date' => $data['sync_start_date'] ?? ($conn->sync_start_date ?: now()->subDays(config('shopify.default_lookback_days'))->toDateString()),
            'notify_customer' => $request->boolean('notify_customer', true),
            'auto_fulfill'    => true,
            'last_error'      => null,
        ]);
        if (!empty($data['client_secret'])) {
            $conn->setClientSecret(trim($data['client_secret']));
        }

        try {
            if ($data['auth_method'] === 'oauth') {
                $conn->status = $conn->exists && $conn->status === 'connected' ? 'connected' : 'pending_auth';
                $conn->oauth_state = Str::random(40);
                $conn->save();

                $url = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
                    'client_id'    => trim($data['client_id']),
                    'scope'        => config('shopify.scopes'),
                    'redirect_uri' => config('shopify.redirect_uri'),
                    'state'        => $conn->oauth_state,
                ]);
                ShopifyClient::log($conn->id, null, 'auth', 'redirect_to_shopify', true, $shop);
                return redirect()->away($url);
            }

            if ($data['auth_method'] === 'client_credentials') {
                [$token, $ttl, $scope] = ShopifyClient::exchangeClientCredentials($shop, trim($data['client_id']), trim($data['client_secret']));
                $conn->setToken($token);
                $conn->token_expires_at = now()->addSeconds($ttl);
                $conn->scopes = $scope;
            } else {
                $conn->setToken(trim($data['access_token']));
                $conn->token_expires_at = null;
            }

            $conn->status = 'connected';
            $conn->save();

            return $this->afterAuthorised($conn);
        } catch (\Throwable $e) {
            Log::error('Shopify connect failed', ['shop' => $shop, 'error' => $e->getMessage()]);
            $conn->exists && $conn->forceFill(['last_error' => $e->getMessage()])->save();
            return back()->withInput()->with('error', 'Could not connect: ' . $e->getMessage());
        }
    }

    /** GET /auth/shopify/callback — Shopify sends the seller here after "Install / Approve". */
    public function oauthCallback(Request $request)
    {
        $state = (string) $request->query('state');
        $shop = ShopifyClient::normalizeShop($request->query('shop'));

        $conn = $state ? ShopifyConnection::where('oauth_state', $state)->first() : null;
        if (!$conn || !$shop || $conn->shopify_shop_domain !== $shop) {
            return redirect()->route('seller.channel.list')->with('error', 'Shopify authorisation link expired or did not match. Please click Connect again.');
        }

        $secret = $conn->getClientSecret();
        // Use the raw query string: app middleware merges extra keys into $request->query()
        $raw = [];
        foreach (explode('&', (string) $request->server('QUERY_STRING')) as $pair) {
            if ($pair === '') {
                continue;
            }
            [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
            $raw[urldecode($k)] = urldecode($v);
        }
        if (!ShopifyClient::validQueryHmac($raw, (string) $secret)) {
            ShopifyClient::log($conn->id, null, 'auth', 'callback_hmac', false, 'HMAC mismatch — Client Secret wrong?');
            $conn->forceFill(['last_error' => 'Shopify signature check failed. Re-check the Client Secret.', 'oauth_state' => null])->save();
            return redirect()->route('seller.channel.list')->with('error', 'Shopify signature check failed. Please re-enter the Client Secret exactly as shown in Dev Dashboard › Settings.');
        }

        if ($request->filled('error')) {
            $conn->forceFill(['last_error' => 'Seller declined on Shopify', 'oauth_state' => null])->save();
            return redirect()->route('seller.channel.list')->with('error', 'Authorisation was cancelled on Shopify.');
        }

        try {
            [$token, $scope] = ShopifyClient::exchangeOAuthCode($shop, (string) $request->query('code'), $conn->client_id, $secret);
            $conn->setToken($token);
            $conn->scopes = $scope;
            $conn->status = 'connected';
            $conn->oauth_state = null;
            $conn->token_expires_at = null;
            $conn->save();

            // Make sure the seller session is the right one (callback is a new top-level visit)
            if (!Auth::guard('seller')->check() && ($seller = SellerList::find($conn->seller_id))) {
                Auth::guard('seller')->login($seller);
            }

            return $this->afterAuthorised($conn);
        } catch (\Throwable $e) {
            Log::error('Shopify OAuth callback failed', ['shop' => $shop, 'error' => $e->getMessage()]);
            $conn->forceFill(['last_error' => $e->getMessage(), 'oauth_state' => null])->save();
            return redirect()->route('seller.channel.list')->with('error', 'Could not finish connecting: ' . $e->getMessage());
        }
    }

    /** Test, register webhooks, first pull. */
    private function afterAuthorised(ShopifyConnection $conn)
    {
        $info = ShopifyClient::for($conn)->shopInfo();
        $conn->shop_name = $info['name'] ?? $conn->shop_name;
        $conn->webhook_verified_at = now();
        $conn->save();
        ShopifyClient::log($conn->id, null, 'auth', 'connected', true, ($info['name'] ?? '') . ' / ' . $conn->auth_type);

        $this->registerWebhooks($conn);

        $msg = "Shopify store \"{$conn->shop_name}\" connected.";
        try {
            $s = ShopifyOrderSync::for($conn)->pull();
            $msg .= " {$s['created']} order(s) imported" . ($s['failed'] ? ", {$s['failed']} need attention (see Sync log)." : '.');
        } catch (\Throwable $e) {
            $msg .= ' First order sync failed: ' . $e->getMessage();
        }

        return redirect()->route('seller.channel.list')->with('success', $msg . ' New orders now sync automatically, and AWB/status is sent back to Shopify.');
    }

    private function registerWebhooks(ShopifyConnection $conn): void
    {
        if (!$conn->canVerifyWebhooks()) {
            return; // legacy tokens: polling only
        }
        $base = rtrim(config('shopify.webhook_base'), '/');
        if (!str_starts_with($base, 'https://')) {
            return;
        }

        $client = ShopifyClient::for($conn);
        $topics = [
            'ORDERS_CREATE'    => 'orders-create',
            'ORDERS_UPDATED'   => 'orders-updated',
            'ORDERS_CANCELLED' => 'orders-cancelled',
            'APP_UNINSTALLED'  => 'app-uninstalled',
        ];
        $ok = true;

        try {
            $existing = $client->graphql('{ webhookSubscriptions(first: 50) { nodes { id topic uri } } }');
            $have = collect($existing['webhookSubscriptions']['nodes'] ?? [])->map(fn ($w) => $w['topic'] . '|' . $w['uri'])->all();

            foreach ($topics as $topic => $slug) {
                $uri = "{$base}/webhooks/shopify/{$slug}";
                if (in_array("{$topic}|{$uri}", $have, true)) {
                    continue;
                }
                $d = $client->graphql('mutation($t: WebhookSubscriptionTopic!, $s: WebhookSubscriptionInput!) {
                        webhookSubscriptionCreate(topic: $t, webhookSubscription: $s) { webhookSubscription { id } userErrors { field message } } }',
                    ['t' => $topic, 's' => ['uri' => $uri, 'format' => 'JSON']]);
                if (!empty($d['webhookSubscriptionCreate']['userErrors'])) {
                    $ok = false;
                    ShopifyClient::log($conn->id, null, 'auth', 'webhook_' . $slug, false, json_encode($d['webhookSubscriptionCreate']['userErrors']));
                }
            }
        } catch (\Throwable $e) {
            $ok = false;
            ShopifyClient::log($conn->id, null, 'auth', 'webhooks', false, $e->getMessage());
        }

        $conn->forceFill(['webhooks_registered' => $ok])->save();
    }

    /* ================================================================= */
    /*  Manual actions                                                   */
    /* ================================================================= */

    public function syncOrders(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        $q = ShopifyConnection::where('seller_id', $seller->id)->active();
        if ($request->filled('channel_id')) {
            $q->where('id', $request->channel_id);
        }
        $conns = $q->get();

        if ($conns->isEmpty()) {
            return $this->reply($request, false, 'No connected Shopify store. Connect your store first.');
        }

        $created = $failed = $pushed = 0;
        $errors = [];
        foreach ($conns as $conn) {
            try {
                $s = ShopifyOrderSync::for($conn)->pull();
                $created += $s['created'];
                $failed += $s['failed'];
                $errors = array_merge($errors, array_slice($s['errors'], 0, 3));
                $p = ShopifyStatusSync::pushForConnection($conn);
                $pushed += $p['pushed'];
            } catch (\Throwable $e) {
                $conn->forceFill(['last_error' => $e->getMessage()])->save();
                $errors[] = $conn->shopify_shop_domain . ': ' . $e->getMessage();
            }
        }

        $msg = "Sync done: {$created} new order(s) imported, {$pushed} status update(s) sent to Shopify.";
        if ($failed || $errors) {
            $msg .= ' Issues: ' . implode(' | ', array_slice($errors, 0, 3));
        }
        return $this->reply($request, empty($errors), $msg);
    }

    public function updateSettings(Request $request, $id)
    {
        $conn = ShopifyConnection::where('seller_id', Auth::guard('seller')->id())->findOrFail($id);
        $conn->update([
            'notify_customer' => $request->boolean('notify_customer'),
            'auto_fulfill'    => $request->boolean('auto_fulfill'),
        ]);
        return back()->with('success', 'Shopify settings saved.');
    }

    public function disconnectStore(Request $request, $connectionId)
    {
        $conn = ShopifyConnection::where('seller_id', Auth::guard('seller')->id())->find($connectionId);
        if (!$conn) {
            return back()->with('error', 'Connection not found.');
        }

        // Best effort: remove our webhooks from the store
        try {
            if ($conn->isActive()) {
                $client = ShopifyClient::for($conn);
                $subs = $client->graphql('{ webhookSubscriptions(first: 50) { nodes { id uri } } }');
                $base = rtrim(config('shopify.webhook_base'), '/');
                foreach ($subs['webhookSubscriptions']['nodes'] ?? [] as $w) {
                    if (str_starts_with($w['uri'], $base . '/webhooks/shopify/')) {
                        $client->graphql('mutation($id: ID!) { webhookSubscriptionDelete(id: $id) { deletedWebhookSubscriptionId userErrors { message } } }', ['id' => $w['id']]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $conn->status = 'disconnected';
        $conn->setToken(null);
        $conn->admin_api_token = null;
        $conn->webhooks_registered = false;
        $conn->save();
        ShopifyClient::log($conn->id, null, 'auth', 'disconnected', true);

        return back()->with('success', 'Shopify store disconnected. Orders already imported stay in your panel.');
    }

    public function getConnectedStores()
    {
        $conns = ShopifyConnection::where('seller_id', Auth::guard('seller')->id())->orderByDesc('id')->get();
        return response()->json([
            'success'     => true,
            'connections' => $conns->map(fn ($c) => [
                'id'                  => $c->id,
                'shop'                => $c->shopify_shop_domain,
                'shop_name'           => $c->shop_name,
                'status'              => $c->status,
                'auth_type'           => $c->auth_type,
                'last_order_pull_at'  => $c->last_order_pull_at?->toDateTimeString(),
                'last_status_push_at' => $c->last_status_push_at?->toDateTimeString(),
                'orders_synced_count' => (int) $c->orders_synced_count,
                'last_error'          => $c->last_error,
            ]),
        ]);
    }

    private function reply(Request $request, bool $ok, string $msg)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => $ok, 'message' => $msg]);
        }
        return back()->with($ok ? 'success' : 'error', $msg);
    }

    /* ================================================================= */
    /*  Webhooks (public, HMAC-verified)                                 */
    /* ================================================================= */

    public function webhook(Request $request, string $topic = 'orders-create')
    {
        $raw = $request->getContent();
        $shop = ShopifyClient::normalizeShop($request->header('X-Shopify-Shop-Domain'));
        $hmac = $request->header('X-Shopify-Hmac-Sha256');

        // GDPR compliance topics: nothing personal is stored beyond orders the seller ships
        if (in_array($topic, ['customers-data-request', 'customers-redact', 'shop-redact'], true)) {
            return response()->json(['ok' => true]);
        }

        $conn = ShopifyConnection::where('shopify_shop_domain', $shop)
            ->whereIn('status', ['connected', 'auth_error'])
            ->get()
            ->first(fn ($c) => $c->canVerifyWebhooks() && ShopifyClient::validWebhookHmac($raw, $hmac, $c->getClientSecret()));

        if (!$conn) {
            Log::warning('Shopify webhook rejected (unknown shop or bad HMAC)', ['shop' => $shop, 'topic' => $topic]);
            return response()->json(['error' => 'unauthorised'], 401);
        }

        $payload = json_decode($raw, true) ?: [];

        if ($topic === 'app-uninstalled') {
            $conn->forceFill(['status' => 'disconnected', 'webhooks_registered' => false, 'last_error' => 'App was uninstalled from Shopify'])->save();
            $conn->setToken(null);
            $conn->save();
            ShopifyClient::log($conn->id, null, 'webhook', 'app_uninstalled', true);
            return response()->json(['ok' => true]);
        }

        $orderId = (string) ($payload['id'] ?? '');
        if ($orderId === '') {
            return response()->json(['ok' => true]);
        }

        // Answer Shopify within its 5s limit; import right after the response is sent.
        $connId = $conn->id;
        dispatch(function () use ($connId, $orderId, $topic) {
            $c = ShopifyConnection::find($connId);
            if (!$c) {
                return;
            }
            try {
                $r = ShopifyOrderSync::for($c)->pullOne($orderId);
                ShopifyClient::log($c->id, $r['order_id'] ?? null, 'webhook', $topic, $r['result'] !== 'failed', $orderId . ' ' . $r['result'] . ' ' . ($r['message'] ?? ''));
            } catch (\Throwable $e) {
                ShopifyClient::log($c->id, null, 'webhook', $topic, false, $orderId . ': ' . $e->getMessage());
            }
        })->afterResponse();

        return response()->json(['ok' => true]);
    }

    /** Old route names kept for compatibility. */
    public function handleOrderWebhook(Request $request)
    {
        return $this->webhook($request, 'orders-create');
    }
}
