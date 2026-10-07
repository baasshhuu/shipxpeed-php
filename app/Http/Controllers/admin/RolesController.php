<?php

namespace App\Http\Controllers\admin;

use App\Models\Role;
use App\Models\User;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\RolePermission;
use App\Models\UserPermission;
use App\Models\PermissionModule;
use \Yajra\Datatables\Datatables;
use App\Http\Controllers\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;

class RolesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $data = Role::select('id', 'name', 'slug', 'status')->whereNot('id', 1);
            return Datatables::of($data)
                ->addColumn('action', function ($row) {
                    $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" id="drop" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs--1"></span></button><div class="dropdown-menu" aria-labelledby="drop">';
                    if (Helper::userCan(102, 'can_edit')) {
                        $btn .= '<button class="dropdown-item edit" data-all="' . htmlspecialchars(json_encode($row))  . '">Edit</button>';
                        $btn .= '<a class="dropdown-item" href="' . route('roles.permission.view', $row->slug) . '">Permission</a>';
                    }
                    if (Helper::userCan(102, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row['id'] . '">Delete</button>';
                    }

                    if (Helper::userAllowed(102)) {
                        return $btn;
                    } else {
                        return '';
                    }
                })
                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Active</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Inactive</small>';
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }
        return view('roles.index');
    }

    public function save(Request $request): JsonResponse
    {
        return Helper::checkValid([
            'name'      => ['required', 'string', 'max:100', 'unique:roles,name,NULL,id,deleted_at,NULL'],
            'status'    => ['required', 'integer'],
        ], function ($validator) {

            DB::transaction(function () use ($validator) {
                $new_role   = Role::create([...$validator->validated(), 'slug'  => Str::uuid()]);
                $data       = PermissionModule::select('module_id')->get()->map(function ($value) use ($new_role) {
                    return [
                        'role_id'       => $new_role->id,
                        'module_id'     => $value->module_id,
                        'can_view'      => 0,
                        'can_add'       => 0,
                        'can_edit'      => 0,
                        'can_delete'    => 0,
                        'allow_all'     => 0,
                    ];
                });

                RolePermission::insert($data->toArray());
            });

            return response()->json([
                'status'    => true,
                'message'   => 'Role Added Successfully',
                'data'      => ''
            ]);
        });
    }

    public function update(Request $request): JsonResponse
    {
        $role = Role::find($request->id);
        if (!$role) {
            return response()->json([
                'status'    => false,
                'message'   => 'Role Not Found..!!',
            ]);
        }

        return Helper::checkValid([
            'id'        => ['required'],
            'name'      => ['required', 'string', 'max:100', 'unique:roles,name,' . $role['id'] . ',id,deleted_at,NULL'],
            'status'    => ['required', 'integer'],
        ], function ($validator) use ($role) {
            $role->update($validator->validated());
            return response()->json([
                'status'    => true,
                'message'   => 'Role Updated Successfully',
            ]);
        });
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Role, $request->id);
    }

    public function permission($slug = null): View|RedirectResponse
    {
        $role = Role::slug($slug);
        if (!$role) {
            return to_route('roles')->withError('Role Not Found..!!');
        }

        $permissions = PermissionModule::select('role_permissions.*', 'permission_modules.module_id as module_id', 'permission_modules.id as modules_id', 'permission_modules.name')
            ->leftJoin('role_permissions', function ($join) use ($role) {
                $join->on('role_permissions.module_id', '=', 'permission_modules.module_id')
                    ->where('role_permissions.role_id', $role['id']);
            })->get();

        if (!$role) {
            return to_route('users')->withError('Role Not Found..!!');
        }
        return view('roles.permission', compact('role', 'permissions'));
    }

    public function permission_update(Request $request): bool
    {
        $role_permission = RolePermission::firstWhere(['role_id' => $request->role_id, 'module_id' => $request->module_id]);
        if (!$role_permission) {
            RolePermission::create([
                'role_id'       =>  $request->role_id,
                'module_id'     => $request->module_id,
                'can_view'      => $request->type == 'can_view' ? 1 : 0,
                'can_add'       => $request->type == 'can_add' ? 1 : 0,
                'can_edit'      => $request->type == 'can_edit' ? 1 : 0,
                'can_delete'    => $request->type == 'can_delete' ? 1 : 0,
                'allow_all'     => $request->type == 'allow_all' ? 1 : 0,
            ]);
            return true;
        }

        $val = $role_permission[$request->type] == 1 ? 0 : 1;
        if (array($request->type, ['can_view', 'can_add', 'can_edit', 'can_delete',  'allow_all'])) {
            $role_permission->toggle($request->type);
            $users = User::where('role_id', $role_permission->role_id)->get()->pluck('id');
            UserPermission::whereIn('user_id', $users)->where('module_id', $role_permission->module_id)->update([$request->type => $val]);
            return  true;
        }
        return false;
    }
}
