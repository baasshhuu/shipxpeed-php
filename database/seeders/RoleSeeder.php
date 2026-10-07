<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create([
            'id'            => 1,
            'slug'          => '34286c4c-a60a-49dc-afb4-b67c17c794a9',
            'name'          => 'Super Admin',
            'status'        => '1',
            'created_at'    => Carbon::now(),
            'updated_at'    => Carbon::now(),
            'deleted_at'    => NULL
        ]);
    }
}
