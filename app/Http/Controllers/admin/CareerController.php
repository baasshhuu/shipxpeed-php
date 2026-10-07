<?php

namespace App\Http\Controllers\admin;

use App\Models\Career;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;

class CareerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $data = Career::select('id', 'title',  'location', 'timing', 'short_description', 'status', 'created_at');
            return Datatables::of($data)
                ->editColumn('image', function ($row) {
                    $btn = '<div class="img-group"><img class="" src="' . asset('storage/' . $row['image']) . '" alt=""></div>';
                    return $btn;
                })
                ->editColumn('created_at', function ($row) {
                    return $row['created_at']->format('d M, Y');
                })
                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Active</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Inactive</small>';
                })
                ->addColumn('action', function ($row) {

                    $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" id="drop" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs--1"></span></button><div class="dropdown-menu" aria-labelledby="drop">';
                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('careers.edit', $row['id']) . '">Edit</a>';
                    }
                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row['id'] . '">Delete</button>';
                    }
                    if (Helper::userAllowed(104)) {
                        return $btn;
                    } else {
                        return '';
                    }
                })
                ->orderColumn('created_at', function ($query, $order) {
                    $query->orderBy('created_at', $order);
                })
                ->rawColumns(['action', 'image', 'status'])
                ->make(true);
        }
        return view('career.index');
    }

    public function add(): View
    {
        return view('career.add');
    }

    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'         => ['required'],
            'location'         => ['required'],
            'timing'         => ['required'],
            'short_description'   => ['required'],
            'status'        => ['required'],
        ]);



        Career::create($validated);
        return to_route('careers')->withSuccess('Career Added Successfully..!!');
    }

    public function edit($id): View|RedirectResponse
    {
        $cms = Career::find($id);
        if (!$cms) {
            return to_route('careers')->withError('Career Not Found..!!');
        }
        return view('career.edit', compact('cms'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $cms = Career::find($id);
        if (!$cms) {
            return to_route('careers')->withError('Career Not Found..!!');
        }

        $data = $request->validate([
            'title'         => ['required'],
            'location'         => ['required'],
            'status'        => ['required'],
            'timing'         => ['required'],
            'short_description'         => ['required'],
        ]);


        $cms->update($data);
        return to_route('careers')->withSuccess('Career Updated Successfully..!!');
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Career, $request->id);
    }
}
