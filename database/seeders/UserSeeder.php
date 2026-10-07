<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'id'                => 1,
            'slug'              => Str::uuid(),
            'name'              => 'Admin',
            'email'             => 'admin@admin.com',
            'mobile'            => '7568457070',
            'status'            => '1',
            'role_id'           => 1,
            'image'             => 'admin/avatar.png',
            'email_verified_at' => NULL,
            'password'          => Hash::make(123456789),
            'remember_token'    => 'CfaY4OZWO7bLxsnytPwn78B2mxdnGJcW16JNgYawHvCa6x85UMRkNLOyBxn1',
            'email_verified_at' => Carbon::now(),
            'created_at'        => Carbon::now(),
            'updated_at'        => Carbon::now(),
        ]);
    }
}
