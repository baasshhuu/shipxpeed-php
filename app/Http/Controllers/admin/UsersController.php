<?php

namespace App\Http\Controllers\admin;

use App\Models\Role;
use App\Models\User;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Models\RolePermission;
use App\Http\Controllers\Controller;

use App\Models\UserPermission;
use App\Models\PermissionModule;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $data = User::select('users.id', 'users.name', 'users.email', 'users.slug', 'users.mobile', 'users.image', 'users.status', 'users.created_at', 'roles.name as role_name')
                ->whereNot('users.id', 1)
                ->leftJoin('roles', 'roles.id', '=', 'users.role_id');
            return Datatables::of($data)
                ->editColumn('image', function ($row) {
                    $btn = '<div class="avatar avatar-md"><img class="rounded-circle" src="' . asset('storage/' . $row['image']) . '" alt=""></div>';
                    return $btn;
                })
                ->editColumn('name', function ($row) {
                    return '<b class="text-dark">' . $row['name'] . '</b><br /> <span class="text-secondary">(' . $row['role_name'] . ')<span>';
                })
                ->editColumn('email', function ($row) {
                    return '<b class="text-dark">' . $row['email'] . '</b><br /> <b class="text-dark">' . $row['mobile'] . '<span>';
                })
                ->editColumn('created_at', function ($row) {
                    return $row['created_at']->format('d M, Y');
                })
                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Active</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Inactive</small>';
                })
                ->addColumn('action', function ($row) {

                    $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" id="drop" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs--1"></span></button><div class="dropdown-menu" aria-labelledby="drop">';
                    if (Helper::userCan(103, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('users.edit', $row->slug) . '">Edit</a>';
                        $btn .= '<a class="dropdown-item" href="' . route('users.permission.view', $row->slug) . '">Permission</a>';
                    }
                    if (Helper::userCan(103, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row['id'] . '">Delete</button>';
                    }

                    if (Helper::userAllowed(103)) {
                        return $btn;
                    } else {
                        return '';
                    }
                })
                ->orderColumn('created_at', function ($query, $order) {
                    $query->orderBy('created_at', $order);
                })
                ->rawColumns(['action', 'email', 'name', 'image', 'status'])
                ->make(true);
        }
        return view('users.index');
    }

    public function add(): View
    {
        $roles = Role::active()->whereNot('id', 1)->get();
        return view('users.add', compact('roles'));
    }
    public function save(UserRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $user = User::create($request->filter());
            $data = RolePermission::where('role_id', $request->role_id)->get()->map(function ($value) use ($user) {
                return [
                    'user_id'       => $user->id,
                    'module_id'     => $value->module_id,
                    'can_view'      => $value->can_view,
                    'can_add'       => $value->can_add,
                    'can_edit'      => $value->can_edit,
                    'can_delete'    => $value->can_delete,
                    'allow_all'     => $value->allow_all,
                ];
            });

            UserPermission::insert($data->toArray());
        });

        return to_route('users')->withSuccess('User Added Successfully..!!');
    }

    public function edit($slug): View|RedirectResponse
    {
        $roles  = Role::active()->whereNot('id', 1)->get();
        $user   = User::slug($slug);
        if (!$user) {
            return to_route('users')->withError('User Not Found..!!');
        }
        return view('users.edit', compact('user', 'roles'));
    }
    public function update(UserRequest $request, $slug): RedirectResponse
    {
        $user   = User::slug($slug);
        if (!$user) {
            return to_route('users')->withError('User Not Found..!!');
        }

        $user->update($request->filter($user));
        return to_route('users')->withSuccess('User Updated Successfully..!!');
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new User, $request->id);
    }

    public function permission($slug): View|RedirectResponse
    {
        $user = User::slug($slug);
        if (!$user) {
            return to_route('users')->withError('User Not Found..!!');
        }

        $permissions = PermissionModule::select('user_permissions.*', 'permission_modules.id as modules_id', 'permission_modules.module_id as module_id', 'permission_modules.name')
            ->leftJoin('user_permissions', function ($join) use ($user) {
                $join->on('user_permissions.module_id', '=', 'permission_modules.module_id')
                    ->where('user_permissions.user_id', $user->id);
            })->get();

        if (!$user) {
            return to_route('users')->withError('User Not Found..!!');
        }

        return view('users.permission', compact('user', 'permissions'));
    }

    public function permission_update(Request $request): bool
    {
        $user_permission = UserPermission::firstWhere(['user_id' => $request->user_id, 'module_id' => $request->module_id]);
        if (!$user_permission) {
            UserPermission::create([
                'user_id'       => $request->user_id,
                'module_id'     => $request->module_id,
                'can_view'      => $request->type == 'can_view' ? 1 : 0,
                'can_add'       => $request->type == 'can_add' ? 1 : 0,
                'can_edit'      => $request->type == 'can_edit' ? 1 : 0,
                'can_delete'    => $request->type == 'can_delete' ? 1 : 0,
                'allow_all'     => $request->type == 'allow_all' ? 1 : 0,
            ]);
            return true;
        }

        if (array($request->type, ['can_view', 'can_add', 'can_edit', 'can_delete', 'allow_all'])) {
            $user_permission->toggle($request->type);
            return  true;
        }
        return false;
    }
}
