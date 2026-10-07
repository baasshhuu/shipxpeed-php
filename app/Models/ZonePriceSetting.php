<?php

namespace App\Models; 

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ZonePriceSetting extends Model
{
    use HasFactory, SoftDeletes, CustomScopes;

    protected $table = 'zone_price_Settings'; // explicitly mention the table name if it's not plural

    protected $fillable = [
        'zone',
        'seller_id',
        'LogisticProvider',
        'cod_price',
        'cod_fix_price',
        'prepaid_price',
        'prepaid_fix_price',
        'cod_charge_parsent',
        'status',
        'id',
        'rto_credit'

    ];

    protected $casts = [
        'cod_price' => 'decimal:2',
        'cod_fix_price' => 'decimal:2',
        'prepaid_price' => 'decimal:2',
        'prepaid_fix_price' => 'decimal:2',
        'cod_charge_parsent' => 'decimal:2',
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
