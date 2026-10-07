<?php

namespace App\Models;

use App\Traits\CustomScopes;
use App\Observers\UserObserver;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, CustomScopes;

    public static function boot()
    {
        parent::boot();
        self::observe(new UserObserver);
    }

    protected $fillable = [
        'slug',
        'role_id',
        'name',
        'email',
        'mobile',
        'status',
        'image',
        'password',
        'state_id',
        'city_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function permission()
    {
        return $this->hasMany(UserPermission::class); //->with('permission_name:id,name')
    }

    // protected function image(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn ($value) => asset('storage/' . $value),
    //     );
    // }
}
