<?php

// SellerAddress Model
namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SellerAddress extends Model
{
    use HasFactory, SoftDeletes, CustomScopes;

    protected $fillable = [
        'seller_id',
        'city_id',
        'state_id',
        'country',
        'pincode',
        'address_line'
    ];

    // Relationships
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }
}
