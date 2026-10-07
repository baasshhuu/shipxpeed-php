<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Investor extends Authenticatable
{
    // use HasApiTokens, HasFactory, Notifiable;

        protected $table = 'investors';   // 👈 yahan table ka name


    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'company_name',
        'investment_amount',
        'status',
        'email_verified_at',
        'last_login_at',
    ];

    // protected $hidden = [
    //     'password',
    //     'remember_token',
    // ];

    // protected $casts = [
    //     'email_verified_at' => 'datetime',
    //     'last_login_at' => 'datetime',
    //     'investment_amount' => 'decimal:2',
    //     'status' => 'boolean',
    // ];

    /**
     * Get the guard name for the investor
     */
    // public function getAuthGuard()
    // {
    //     return 'investor';
    // }

    /**
     * Check if investor is active
     */
    public function isActive()
    {
        return true; // All investors are always active
    }

    /**
     * Get formatted investment amount
     */
    // public function getFormattedInvestmentAmountAttribute()
    // {
    //     return '₹' . number_format($this->investment_amount, 2);
    // }
}