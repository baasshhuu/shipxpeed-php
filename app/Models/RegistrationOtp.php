<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationOtp extends Model
{
    use HasFactory, CustomScopes;

    protected $fillable = [
        'mobile',
        'otp',
        'expire_at'
    ];
}
