<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ShopifyConnection extends Model
{
    use HasFactory;

    protected $table = 'ShopifyConnections';

    protected $fillable = [
        'seller_id',
        'store_url',
        'api_key',
        'admin_api_token',
        'host_name',
        'sync_start_date',
        'status',
        'shopify_shop_domain',
        'access_token',
        'webhook_verified_at',
        'last_sync_at',
        'orders_synced_count',
        'products_synced_count',
        // channel sync
        'auth_type',
        'client_id',
        'client_secret',
        'token_expires_at',
        'scopes',
        'shop_name',
        'auto_fulfill',
        'notify_customer',
        'last_order_pull_at',
        'last_status_push_at',
        'last_error',
        'oauth_state',
        'webhooks_registered',
    ];

    protected $hidden = ['access_token', 'admin_api_token', 'client_secret', 'oauth_state'];

    protected $casts = [
        'sync_start_date'     => 'date',
        'webhook_verified_at' => 'datetime',
        'last_sync_at'        => 'datetime',
        'token_expires_at'    => 'datetime',
        'last_order_pull_at'  => 'datetime',
        'last_status_push_at' => 'datetime',
        'auto_fulfill'        => 'boolean',
        'notify_customer'     => 'boolean',
        'webhooks_registered' => 'boolean',
    ];

    public function seller()
    {
        return $this->belongsTo(SellerList::class, 'seller_id');
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'connected');
    }

    public function isActive(): bool
    {
        return $this->status === 'connected';
    }

    public function getFormattedStoreUrlAttribute()
    {
        return $this->shopify_shop_domain ?: str_replace(['http://', 'https://'], '', (string) $this->store_url);
    }

    /* ---- secrets are stored encrypted with APP_KEY ---- */

    public function setToken(?string $token): void
    {
        $this->access_token = $token ? Crypt::encryptString($token) : null;
    }

    public function getToken(): ?string
    {
        if ($this->access_token) {
            try {
                return Crypt::decryptString($this->access_token);
            } catch (\Throwable $e) {
                return $this->access_token; // value saved in plain text by an older build
            }
        }
        return $this->admin_api_token ?: null; // old rows
    }

    public function setClientSecret(?string $secret): void
    {
        $this->client_secret = $secret ? Crypt::encryptString($secret) : null;
    }

    public function getClientSecret(): ?string
    {
        if (!$this->client_secret) {
            return null;
        }
        try {
            return Crypt::decryptString($this->client_secret);
        } catch (\Throwable $e) {
            return $this->client_secret;
        }
    }

    /** True when Shopify can sign webhooks we can verify (we hold the app's secret). */
    public function canVerifyWebhooks(): bool
    {
        return in_array($this->auth_type, ['oauth', 'client_credentials'], true) && $this->getClientSecret();
    }

    public function testConnection(): bool
    {
        try {
            \App\Services\Shopify\ShopifyClient::for($this)->shopInfo();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
