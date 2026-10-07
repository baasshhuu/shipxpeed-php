<?php

namespace App\Http\Controllers\admin;


use App\Models\State;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;

class StateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $data = State::select('id', 'name', 'status');
            return Datatables::of($data)
                ->addColumn('action', function ($row) {
                    $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" id="drop" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs--1"></span></button><div class="dropdown-menu" aria-labelledby="drop">';
                    if (Helper::userCan(105, 'can_edit')) {
                        $btn .= '<button class="dropdown-item edit" data-all="' . htmlspecialchars(json_encode($row))  . '">Edit</button>';
                    }
                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row['id'] . '">Delete</button>';
                    }

                    if (Helper::userAllowed(105)) {
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
        return view('states.index');
    }

    public function save(Request $request): JsonResponse
    {
        return Helper::checkValid([
            'name'      => ['required', 'string', 'max:100', 'unique:states,name'],
            'status'    => ['required', 'integer'],
        ], function ($validator) {
            State::create($validator->validated());
            return response()->json([
                'status'    => true,
                'message'   => 'State Added Successfully',
                'data'      => ''
            ]);
        });
    }

    public function update(Request $request): JsonResponse
    {
        $state = State::find($request->id);
        if (!$state) {
            return response()->json([
                'status'    => false,
                'message'   => 'State Not Found..!!',
            ]);
        }

        return Helper::checkValid([
            'id'        => ['required'],
            'name'      => ['required', 'string', 'max:100', 'unique:states,name,' . $state['id']],
            'status'    => ['required', 'integer'],
        ], function ($validator) use ($state) {
            $state->update($validator->validated());
            return response()->json([
                'status'    => true,
                'message'   => 'State Added Successfully',
            ]);
        });
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new State, $request->id);
    }
}
