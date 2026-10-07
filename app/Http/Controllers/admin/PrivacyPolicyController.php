<?php

namespace App\Http\Controllers\admin;

use App\Models\Cms;
use App\Helper\Helper;
use App\Models\PrivacyPolicy;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;

class PrivacyPolicyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $data = PrivacyPolicy::select('id', 'title',  'status', 'created_at');
            return Datatables::of($data)

                ->editColumn('created_at', function ($row) {
                    return $row['created_at']->format('d M, Y');
                })
                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Active</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Inactive</small>';
                })
                ->addColumn('action', function ($row) {

                    $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" id="drop" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs--1"></span></button><div class="dropdown-menu" aria-labelledby="drop">';
                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('privacy-policy.edit', $row['id']) . '">Edit</a>';
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
                ->rawColumns(['action', 'status'])
                ->make(true);
        }
        return view('privacypolicy.index');
    }

    public function add(): View
    {
        return view('privacypolicy.add');
    }

    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'         => ['required', 'string', 'max:200'],
            'description'   => ['required'],
            'status'        => ['required', 'integer'],

        ]);

        PrivacyPolicy::create($validated);
        return to_route('privacy-policy')->withSuccess('Privacy Policy Added Successfully..!!');
    }

    public function edit($id): View|RedirectResponse
    {
        $cms = PrivacyPolicy::find($id);
        if (!$cms) {
            return to_route('privacy-policy')->withError('Privacy Policy Not Found..!!');
        }
        return view('privacypolicy.edit', compact('cms'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $cms = PrivacyPolicy::find($id);
        if (!$cms) {
            return to_route('privacy-policy')->withError('Privacy Policy Not Found..!!');
        }

        $data = $request->validate([
            'title'         => ['required', 'string', 'max:200'],
            'description'   => ['required', 'string'],
            'status'        => ['required', 'integer'],

        ]);

        $cms->update($data);
        return to_route('privacy-policy')->withSuccess('Privacy Policy Updated Successfully..!!');
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Cms, $request->id);
    }
}
