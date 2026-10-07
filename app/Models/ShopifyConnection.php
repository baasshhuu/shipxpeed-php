<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'products_synced_count'
    ];

    protected $casts = [
        'sync_start_date' => 'date',
        'webhook_verified_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    // Relationship with seller
    public function seller()
    {
        return $this->belongsTo(SellerList::class, 'seller_id');
    }

    // Check if connection is active
    public function isActive()
    {
        return $this->status === 'connected';
    }

    // Get formatted store URL
    public function getFormattedStoreUrlAttribute()
    {
        return str_replace(['http://', 'https://'], '', $this->store_url);
    }

    // Shopify API methods
    public function syncOrders()
    {
        // Implementation for syncing orders from Shopify
        try {
            $storeUrl = rtrim($this->store_url, '/');
            $accessToken = $this->admin_api_token;
            
            // Prepare API URL with parameters
            $apiUrl = $storeUrl . '/admin/api/2023-10/orders.json';
            $params = [
                'limit' => 250, // Maximum allowed by Shopify
                'status' => 'any',
                'created_at_min' => $this->sync_start_date->format('Y-m-d\TH:i:s\Z'),
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json',
            ])->get($apiUrl, $params);

            if ($response->successful()) {
                $ordersData = $response->json();
                return $ordersData['orders'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Shopify API Error: ' . $e->getMessage());
            return [];
        }
    }

    public function syncProducts()
    {
        // Implementation for syncing products from Shopify
        try {
            $storeUrl = rtrim($this->store_url, '/');
            $accessToken = $this->admin_api_token;
            
            $apiUrl = $storeUrl . '/admin/api/2023-10/products.json';
            $params = ['limit' => 250];

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json',
            ])->get($apiUrl, $params);

            if ($response->successful()) {
                $productsData = $response->json();
                return $productsData['products'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Shopify Products API Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Test Shopify connection
     */
    public function testConnection()
    {
        try {
            $storeUrl = rtrim($this->store_url, '/');
            $accessToken = $this->admin_api_token;
            
            $apiUrl = $storeUrl . '/admin/api/2023-10/shop.json';

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json',
            ])->get($apiUrl);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}