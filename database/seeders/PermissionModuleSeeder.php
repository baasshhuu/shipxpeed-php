<?php

namespace Database\Seeders;

use App\Models\PermissionModule;
use Illuminate\Database\Seeder;

class PermissionModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        //  Permission Array
        $permissions = [
            [
                'module_id'     => '101',
                'name'          => 'App Setting',
                'can_add'       => 0,
                'can_edit'      => 0,
                'can_delete'    => 0,
                'can_view'      => 0,
                'allow_all'     => 0
            ],
            [
                'module_id'     => '102',
                'name'          => 'Roles',
                'can_add'       => 0,
                'can_edit'      => 0,
                'can_delete'    => 0,
                'can_view'      => 0,
                'allow_all'     => 0
            ],
            [
                'module_id'     => '103',
                'name'          => 'Sub Admin',
                'can_add'       => 0,
                'can_edit'      => 0,
                'can_delete'    => 0,
                'can_view'      => 0,
                'allow_all'     => 0
            ],
            [
                'module_id'     => '104',
                'name'          => 'CMS',
                'can_add'       => 0,
                'can_edit'      => 0,
                'can_delete'    => 0,
                'can_view'      => 0,
                'allow_all'     => 0
            ],
            [
                'module_id'     => '105',
                'name'          => 'Location - State',
                'can_add'       => 0,
                'can_edit'      => 0,
                'can_delete'    => 0,
                'can_view'      => 0,
                'allow_all'     => 0
            ],
            [
                'module_id'     => '106',
                'name'          => 'Location - City',
                'can_add'       => 0,
                'can_edit'      => 0,
                'can_delete'    => 0,
                'can_view'      => 0,
                'allow_all'     => 0
            ],
            [
                'module_id'     => '107',
                'name'          => 'Transaction',
                'can_add'       => 0,
                'can_edit'      => 0,
                'can_delete'    => 0,
                'can_view'      => 0,
                'allow_all'     => 0
            ],
            [
                'module_id'     => '108',
                'name'          => '...',
                'can_add'       => 0,
                'can_edit'      => 0,
                'can_delete'    => 0,
                'can_view'      => 0,
                'allow_all'     => 0
            ],
            [
                'module_id'     => '109',
                'name'          => '...',
                'can_add'       => 0,
                'can_edit'      => 0,
                'can_delete'    => 0,
                'can_view'      => 0,
                'allow_all'     => 0
            ],
            [
                'module_id'     => '110',
                'name'          => '...',
                'can_add'       => 0,
                'can_edit'      => 0,
                'can_delete'    => 0,
                'can_view'      => 0,
                'allow_all'     => 0
            ],
            [
                'module_id'     => '111',
                'name'          => '...',
                'can_add'       => 0,
                'can_edit'      => 0,
                'can_delete'    => 0,
                'can_view'      => 0,
                'allow_all'     => 0
            ],
            [
                'module_id'     => '112',
                'name'          => '...',
                'can_add'       => 0,
                'can_edit'      => 0,
                'can_delete'    => 0,
                'can_view'      => 0,
                'allow_all'     => 0
            ],
            [
                'module_id'     => '113',
                'name'          => '...',
                'can_add'       => 0,
                'can_edit'      => 0,
                'can_delete'    => 0,
                'can_view'      => 0,
                'allow_all'     => 0
            ],
            [
                'module_id'     => '114',
                'name'          => '...',
                'can_add'       => 0,
                'can_edit'      => 0,
                'can_delete'    => 0,
                'can_view'      => 0,
                'allow_all'     => 0
            ],
            [
                'module_id'     => '115',
                'name'          => '...',
                'can_add'       => 0,
                'can_edit'      => 0,
                'can_delete'    => 0,
                'can_view'      => 0,
                'allow_all'     => 0
            ],
        ];

        PermissionModule::insert($permissions);
    }
}
