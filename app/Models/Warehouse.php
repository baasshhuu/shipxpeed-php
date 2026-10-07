<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'address_title',
        'name',
        'phone',
        'alt_phone',
        'email',
        'pincode',
        'city',
        'state',
        'country',
        'address_line1',
        'address_line2',
        'is_default',
        'box_d_address_id',
        'parcelx_warehouse_id',
        'shiprocket_pickup_id',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function seller()
    {
        return $this->belongsTo(SellerList::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
