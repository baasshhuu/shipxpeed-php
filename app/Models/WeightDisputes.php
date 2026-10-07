<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeightDisputes extends Model
{
    use HasFactory;

    protected $fillable = [
        'awb',
        'courier',
        'Mentionedweight',
        'chargedweight',
        'weightmissmatched',
        'weightdisputecharges',
                'status',

    ];

  
}
