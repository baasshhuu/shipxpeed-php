<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_name',
        'hsn',
        'quantity',
        'unit_price',
        'discount',
        'tax_rate',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getTotalAttribute()
    {
        return $this->quantity * $this->unit_price;
    }

    public function getTotalAfterDiscountAttribute()
    {
        return $this->total - ($this->total * ($this->discount / 100));
    }

    public function getTaxAmountAttribute()
    {
        return $this->total_after_discount * ($this->tax_rate / 100);
    }

    public function getGrandTotalAttribute()
    {
        return $this->total_after_discount + $this->tax_amount;
    }
}
