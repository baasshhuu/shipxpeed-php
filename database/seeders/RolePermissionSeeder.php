<?php

namespace Database\Seeders;

use App\Models\PermissionModule;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{

    public function run()
    {
        $role_id = 1;
        $all_permissions = PermissionModule::all();
        $data = [];
        foreach ($all_permissions as $key => $value) {
            array_push($data, [
                'role_id'       => $role_id,
                'module_id'     => $value->module_id,
                'can_view'      => 1,
                'can_add'       => 1,
                'can_edit'      => 1,
                'can_delete'    => 1,
                'allow_all'     => 1,
            ]);
        }
        RolePermission::insert($data);
    }
}
