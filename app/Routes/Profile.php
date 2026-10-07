<?php

namespace App\Routes;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\ProfileController;

class Profile
{
    // Profile Routes Group
    public static function routes()
    {
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile')->middleware('password.confirm');
        Route::post('profile', [ProfileController::class, 'update'])->name('profile');

        Route::post('update-password', [ProfileController::class, 'update_password'])->name('update-password');
        Route::post('update-image', [ProfileController::class, 'upload_image'])->name('update-image');

        Route::get('lock',  [ProfileController::class, 'lock'])->name('lock');
        Route::post('lock', [ProfileController::class, 'unlock'])->name('lock');
        Route::get('logout', [ProfileController::class, 'logout'])->name('logout');
    }
}
