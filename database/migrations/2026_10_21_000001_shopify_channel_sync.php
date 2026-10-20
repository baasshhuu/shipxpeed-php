<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shopify channel: order pull + shipment status push.
 * Written defensively (hasTable / hasColumn) because the live DB has tables
 * that were created by hand without migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ShopifyConnections')) {
            Schema::create('ShopifyConnections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seller_id')->index();
                $table->string('store_url')->nullable();
                $table->string('api_key')->nullable();
                $table->text('admin_api_token')->nullable();
                $table->string('host_name')->nullable();
                $table->date('sync_start_date')->nullable();
                $table->string('status')->default('connected');
                $table->string('shopify_shop_domain')->nullable();
                $table->text('access_token')->nullable();
                $table->timestamp('webhook_verified_at')->nullable();
                $table->timestamp('last_sync_at')->nullable();
                $table->integer('orders_synced_count')->default(0);
                $table->integer('products_synced_count')->default(0);
                $table->timestamps();
            });
        }

        $connCols = [
            'auth_type'            => fn (Blueprint $t) => $t->string('auth_type', 30)->nullable(),   // oauth (seller's own app, default) | client_credentials | legacy_token
            'client_id'            => fn (Blueprint $t) => $t->string('client_id')->nullable(),
            'client_secret'        => fn (Blueprint $t) => $t->text('client_secret')->nullable(),     // encrypted
            'token_expires_at'     => fn (Blueprint $t) => $t->timestamp('token_expires_at')->nullable(),
            'scopes'               => fn (Blueprint $t) => $t->text('scopes')->nullable(),
            'shop_name'            => fn (Blueprint $t) => $t->string('shop_name')->nullable(),
            'auto_fulfill'         => fn (Blueprint $t) => $t->boolean('auto_fulfill')->default(true),
            'notify_customer'      => fn (Blueprint $t) => $t->boolean('notify_customer')->default(true),
            'last_order_pull_at'   => fn (Blueprint $t) => $t->timestamp('last_order_pull_at')->nullable(),
            'last_status_push_at'  => fn (Blueprint $t) => $t->timestamp('last_status_push_at')->nullable(),
            'last_error'           => fn (Blueprint $t) => $t->text('last_error')->nullable(),
            'oauth_state'          => fn (Blueprint $t) => $t->string('oauth_state', 64)->nullable(),
            'webhooks_registered'  => fn (Blueprint $t) => $t->boolean('webhooks_registered')->default(false),
        ];
        foreach ($connCols as $col => $def) {
            if (!Schema::hasColumn('ShopifyConnections', $col)) {
                Schema::table('ShopifyConnections', fn (Blueprint $t) => $def($t));
            }
        }

        $orderCols = [
            'shopify_order_id'       => fn (Blueprint $t) => $t->string('shopify_order_id')->nullable()->index(),
            'shopify_order_number'   => fn (Blueprint $t) => $t->string('shopify_order_number')->nullable(),
            'shopify_connection_id'  => fn (Blueprint $t) => $t->unsignedBigInteger('shopify_connection_id')->nullable()->index(),
            'shopify_fulfillment_id' => fn (Blueprint $t) => $t->string('shopify_fulfillment_id')->nullable(),
            'shopify_pushed_status'  => fn (Blueprint $t) => $t->string('shopify_pushed_status', 40)->nullable(),
            'shopify_pushed_awb'     => fn (Blueprint $t) => $t->string('shopify_pushed_awb')->nullable(),
            'shopify_sync_error'     => fn (Blueprint $t) => $t->text('shopify_sync_error')->nullable(),
            'shopify_synced_at'      => fn (Blueprint $t) => $t->timestamp('shopify_synced_at')->nullable(),
        ];
        foreach ($orderCols as $col => $def) {
            if (!Schema::hasColumn('orders', $col)) {
                Schema::table('orders', fn (Blueprint $t) => $def($t));
            }
        }

        // Old rows: keep ones that really have a token (as legacy_token), flag the rest to reconnect
        foreach (\DB::table('ShopifyConnections')->whereNull('auth_type')->get() as $row) {
            $shop = \App\Services\Shopify\ShopifyClient::normalizeShop($row->shopify_shop_domain ?: $row->host_name ?: $row->store_url);
            $hasToken = filled($row->admin_api_token) || filled($row->access_token);
            \DB::table('ShopifyConnections')->where('id', $row->id)->update([
                'shopify_shop_domain' => $shop,
                'auth_type'           => $hasToken ? 'legacy_token' : null,
                'status'              => ($hasToken && $shop && $row->status === 'connected') ? 'connected' : 'reconnect_required',
            ]);
        }

        if (!Schema::hasTable('shopify_sync_logs')) {
            Schema::create('shopify_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('connection_id')->nullable()->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->string('direction', 10);   // pull | push | webhook | auth
                $table->string('action', 60);
                $table->boolean('success')->default(true);
                $table->text('message')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_sync_logs');
        // Columns are intentionally left in place: dropping them would lose live order links.
    }
};
