<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkCulture extends Model
{
    use HasFactory, SoftDeletes, CustomScopes;

    protected $fillable = [
        'image'
    ];
}
