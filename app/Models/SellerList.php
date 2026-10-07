<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class SellerList extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_type',
        'negative_balance',
        'name',
        'profile',
        'email',
        'phone_number',
        'password',
        'pan_card',
        'adhar_card_front',
        'adhar_card_back',
        'gst_no',
        'gst_photo',
        'cancel_cheque',
        'status',
        'kyc_status',
        'kyc_type',
        'ie_Code',
        'ie_photo',
        'ad_Code',
        'ad_photo',
        'agreement_accepted',

        'bank_name',
        'bank_account_verified',
        'gst_verified_name',
        'pan_verified_name',
        'account_number',
        'ifsc_code',
        'gst_number',
        'pan_number',
        'fixed_price',
        'api_token',
    
    ];



    protected $hidden = ['password'];


    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function agreement()
    {
        return $this->hasOne(SellerAgreement::class, 'seller_id');
    }

    public function recharges()
    {
        return $this->hasMany(Recharge::class, 'seller_id');
    }


// Define the relationship with orders
    public function orders()
    {
        return $this->hasMany(Order::class, 'seller_id', 'id');
    }

    public function sellers()
    {
        return $this->hasMany(Seller::class, 'seller_id', 'id');
    }

    public function sellerAddress()
    {
        return $this->hasOne(SellerAddress::class, 'seller_id', 'id');
    }




    protected static function booted()
    {
        static::deleting(function ($seller) {
            $seller->recharges()->delete();
        });
    }
}
