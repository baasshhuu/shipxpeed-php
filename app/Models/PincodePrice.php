<?php

namespace App\Models; 

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PincodePrice extends Model
{
    use HasFactory, SoftDeletes, CustomScopes;

    protected $table = 'pincode_price'; // explicitly mention the table name if it's not plural





}
