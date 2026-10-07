<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'seller_id',
        'awb_number',
        'courier_id',
        'payment_type',
        'order_amount',
        'shipping_charges',
        'cod_charges',
        'discount',
        'collectable_amount',
        'package_type',
        'package_weight',
        'package_length',
        'package_breadth',
        'package_height',
        'consignee',
        'pickup',
        'rto',
        'order_items',
        'encode_data',
        'shadowfax_json',
        'manifest',
        'label',
        'fwd_destination_code',
        'co_payment_type',
        'additional_info',
        'status',
        'courier_name',
        'co_courier_id',
        'shipment_id',
        'courier_order_id',
        'shipping_status',
        'seller_amount_walate',
        'delhivery_b2c',
        'delhivery_b2c_air',
        'shipper_hub_id',
        'smartship_tracking_url',
        'payment_status',
        'rto_amount',
        'smarship_courier_id',
        'shipping_date',
        'last_sync_at',
        'orders_synced_count',
        'shopify_order_id',
        'shopify_order_number',
        'all_courier_name',
        'order_cancelled_amount',
        'label_pdf',
        'admin_status',
        'rto_reasen',
        'cancelled_amount',
        'customer_order_id',
        'order_status',
        'zone',
        'zone_courier_name',

    ];

    protected $casts = [
        'order_items' => 'array',
        'consignee' => 'array',
        'pickup' => 'array',
        'rto' => 'array',
        'additional_info' => 'array',
        'shipping_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];



    public function seller()
    {
        return $this->belongsTo(SellerList::class, 'seller_id');
    }


}
