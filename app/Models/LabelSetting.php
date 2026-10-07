<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabelSetting extends Model
{
    use HasFactory;

    protected $table = 'label_settings';

    protected $fillable = [
        'seller_id',
        'show_logo',
        'logo_path',
        'show_support_contact',
        'hide_prepaid_amount',
        'hide_customer_mobile',
        'hide_gst_number',
        'hide_return_address_line_1',
        'hide_return_address_line_2',
        'hide_return_city_state_pincode',
        'hide_return_mobile_number',
        'hide_return_contact_name',
        'hide_sku',
        'hide_product',
        'hide_discount',
        'hide_qty',
        'hide_amount',
        'label_type',
        'label_layout', // New field for layout system
        'label_size',
    ];

    protected $casts = [
        'show_logo' => 'boolean',
        'show_support_contact' => 'boolean',
        'hide_prepaid_amount' => 'boolean',
        'hide_customer_mobile' => 'boolean',
        'hide_gst_number' => 'boolean',
        'hide_return_address_line_1' => 'boolean',
        'hide_return_address_line_2' => 'boolean',
        'hide_return_city_state_pincode' => 'boolean',
        'hide_return_mobile_number' => 'boolean',
        'hide_return_contact_name' => 'boolean',
        'hide_sku' => 'boolean',
        'hide_product' => 'boolean',
        'hide_discount' => 'boolean',
        'hide_qty' => 'boolean',
        'hide_amount' => 'boolean',
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }
}