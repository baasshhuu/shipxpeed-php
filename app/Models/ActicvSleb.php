<?php

namespace App\Models; 

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActicvSleb extends Model
{
    use HasFactory, SoftDeletes, CustomScopes;

    protected $table = 'zone_price'; // explicitly mention the table name if it's not plural

    protected $fillable = [
        'seller_id',
        'LogisticProvider',
        'status',
        
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
