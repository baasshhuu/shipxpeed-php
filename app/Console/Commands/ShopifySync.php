<?php

namespace App\Console\Commands;

use App\Models\ShopifyConnection;
use App\Services\Shopify\ShopifyClient;
use App\Services\Shopify\ShopifyOrderSync;
use App\Services\Shopify\ShopifyStatusSync;
use Illuminate\Console\Command;

/**
 * Runs every 5 minutes from the scheduler:
 *   1. pulls new / changed Shopify orders (safety net for missed webhooks)
 *   2. pushes AWB + shipment status back to Shopify
 */
class ShopifySync extends Command
{
    protected $signature = 'shopify:sync {--connection= : Only this ShopifyConnections id} {--pull-only} {--push-only}';

    protected $description = 'Sync orders from Shopify and push shipment status back';

    public function handle(): int
    {
        $q = ShopifyConnection::active()->whereNotNull('shopify_shop_domain');
        if ($id = $this->option('connection')) {
            $q->where('id', $id);
        }

        foreach ($q->get() as $conn) {
            $label = "#{$conn->id} {$conn->shopify_shop_domain}";

            if (!$this->option('push-only')) {
                try {
                    $s = ShopifyOrderSync::for($conn)->pull();
                    $this->line("{$label} pull: fetched {$s['fetched']}, new {$s['created']}, failed {$s['failed']}");
                } catch (\Throwable $e) {
                    $conn->forceFill(['last_error' => mb_strimwidth($e->getMessage(), 0, 900, '…')])->save();
                    ShopifyClient::log($conn->id, null, 'pull', 'orders', false, $e->getMessage());
                    $this->error("{$label} pull failed: {$e->getMessage()}");
                    if (preg_match('/rejected the access token|not authorised|refused the Client ID/i', $e->getMessage())) {
                        $conn->forceFill(['status' => 'auth_error'])->save();
                        continue;
                    }
                }
            }

            if (!$this->option('pull-only')) {
                try {
                    $s = ShopifyStatusSync::pushForConnection($conn);
                    $this->line("{$label} push: pushed {$s['pushed']}, failed {$s['failed']}");
                } catch (\Throwable $e) {
                    ShopifyClient::log($conn->id, null, 'push', 'batch', false, $e->getMessage());
                    $this->error("{$label} push failed: {$e->getMessage()}");
                }
            }
        }

        return self::SUCCESS;
    }
}
