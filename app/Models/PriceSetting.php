<?php

namespace App\Models; 

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceSetting extends Model
{
    use HasFactory, SoftDeletes, CustomScopes;

    protected $table = 'Price_Settings'; // explicitly mention the table name if it's not plural

    protected $fillable = [
        'seller_id',
        'shipping_charge',
        'cod_charge_parsent',
        'cod_charge',
        'LogisticProvider',
        'fixed_courier_price',
        'status'
     
    ];

        public function Provider()
    {
        return $this->belongsTo(LogisticProvider::class, 'LogisticProvider');
    }
    // app/Models/PriceSetting.php
    public function seller()
    {
        return $this->belongsTo(SellerList::class, 'seller_id');
    }



}
