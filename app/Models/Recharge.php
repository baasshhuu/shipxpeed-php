<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recharge extends Model 
{
    use HasFactory, SoftDeletes, CustomScopes;

    protected $fillable = ['amount', 'code', 'seller_id','type','status','debit','weight_id','weight_awb','weight_courier','weight_mentionedweight','weight_chargedweight','weight_weightmissmatched','weight_seller_name','description','gateway','gateway_order_id','gateway_payment_id','gateway_status','paid_at'];


    public function seller()
    {
        return $this->belongsTo(SellerList::class, 'seller_id');
    }

    public function recharges()
{
    return $this->hasMany(Recharge::class, 'seller_id');
}

public function orders()
{
    return $this->hasMany(Order::class, 'seller_id');
}

}
