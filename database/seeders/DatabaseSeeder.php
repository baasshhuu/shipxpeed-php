<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionModuleSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            UserPermissionSeeder::class,
            GeneralSettingSeeder::class,
            StateSeeder::class,
            CitiesSeeder::class,
            CmsSeeder::class,
        ]);
    }
}
