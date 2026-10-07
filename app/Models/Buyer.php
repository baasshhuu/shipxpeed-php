<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Buyer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'alt_phone',
        'email',
        'gstin',
        'address_line1',
        'address_line2',
        'pincode',
        'city',
        'state',
        'country',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
