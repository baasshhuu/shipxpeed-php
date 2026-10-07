<?php

namespace Database\Seeders;

use App\Models\PermissionModule;
use App\Models\UserPermission;
use Illuminate\Database\Seeder;

class UserPermissionSeeder extends Seeder
{
    public function run()
    {
        $user_id = 1;
        $all_permissions = PermissionModule::all();
        $data = [];
        foreach ($all_permissions as $key => $value) {
            array_push($data, [
                'user_id'       => $user_id,
                'module_id'     => $value->module_id,
                'can_view'      => 1,
                'can_add'       => 1,
                'can_edit'      => 1,
                'can_delete'    => 1,
                'allow_all'     => 1,
            ]);
        }
        UserPermission::insert($data);
    }
}
