<?php

namespace App\Http\Controllers\admin;

use App\Models\Cms;
use App\Helper\Helper;
use App\Models\SellerList;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;

class StaticDataController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function kycmanage(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $data = SellerList::select('id', 'name','kyc_status','created_at','pan_card','adhar_card_front','adhar_card_back','gst_no','gst_photo','cancel_cheque');
            return Datatables::of($data)
            ->editColumn('adhar_card_back', function ($row) {
                $url = asset('storage/' . $row['adhar_card_back']);
                return '<div class="img-group">
                            <img src="' . $url . '" class="img-thumbnail img-clickable" style="width: 70px; height:70px; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#imageModal" data-img="' . $url . '">
                        </div>';
            })
            ->editColumn('gst_photo', function ($row) {
                $url = asset('storage/' . $row['gst_photo']);
                return '<div class="img-group">
                            <img src="' . $url . '" class="img-thumbnail img-clickable" style="width: 70px; height:70px; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#imageModal" data-img="' . $url . '">
                        </div>';
            })
            ->editColumn('cancel_cheque', function ($row) {
                $url = asset('storage/' . $row['cancel_cheque']);
                return '<div class="img-group">
                            <img src="' . $url . '" class="img-thumbnail img-clickable" style="width: 70px; height:70px; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#imageModal" data-img="' . $url . '">
                        </div>';
            })
            ->editColumn('pan_card', function ($row) {
                $url = asset('storage/' . $row['pan_card']);
                return '<div class="img-group">
                            <img src="' . $url . '" class="img-thumbnail img-clickable" style="width: 70px; height:70px; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#imageModal" data-img="' . $url . '">
                        </div>';
            })

                ->editColumn('kyc_status', function ($row) {
                    if ($row['kyc_status'] == 1) {
                        return '<small class="badge fw-semi-bold rounded-pill badge-light-success">Approved</small>';
                    } elseif ($row['kyc_status'] == 2) {
                        return '<small class="badge fw-semi-bold rounded-pill badge-light-danger">Disapproved</small>';
                    } else {
                        return '<small class="badge fw-semi-bold rounded-pill badge-light-secondary">Pending</small>';
                    }
                })
                ->addColumn('action', function ($row) {
                    $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" id="drop" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs--1"></span></button><div class="dropdown-menu" aria-labelledby="drop">';
                    if (Helper::userCan(111, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-primary update_status" data-id="' . $row['id'] . '">Update Status</button>';
                    }
                    if (Helper::userAllowed(111)) {
                        return $btn;
                    } else {
                        return '';
                    }
                })
                ->orderColumn('created_at', function ($query, $order) {
                    $query->orderBy('created_at', $order);
                })
                ->rawColumns(['action', 'image', 'status','kyc_status','adhar_card_back','gst_photo','adhar_card_front','cancel_cheque','cancel_cheque','pan_card'])
                ->make(true);
        }
        return view('staticdata.kycmanage');
    }

    public function update_kyc_status(Request $request): JsonResponse
    {
        $seller = SellerList::find($request->id);

        if (!$seller || !$request->has('status')) {
            return response()->json(['kyc_status' => false, 'message' => 'Something went wrong.']);
        }

        $seller->kyc_status = $request->status;
        $seller->save();

        $message = match((int)$request->status) {
            1 => 'KYC Approved successfully.',
            2 => 'KYC Disapproved successfully.',
            default => 'KYC status updated.',
        };

        return response()->json(['kyc_status' => true, 'message' => $message]);
    }

    public function profilemanage(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $data = SellerList::select('id', 'name','status','created_at');
            return Datatables::of($data)
                ->editColumn('created_at', fn($row) => $row['created_at']->format('d M, Y'))
                ->editColumn('kyc_status', function ($row) {
                    if ($row['kyc_status'] == 1) {
                        return '<small class="badge fw-semi-bold rounded-pill badge-light-success">Approved</small>';
                    } elseif ($row['kyc_status'] == 2) {
                        return '<small class="badge fw-semi-bold rounded-pill badge-light-danger">Disapproved</small>';
                    } else {
                        return '<small class="badge fw-semi-bold rounded-pill badge-light-secondary">Pending</small>';
                    }
                })

                ->editColumn('status', function ($row) {
                    return $row['status'] == 1
                        ? '<small class="badge fw-semi-bold rounded-pill badge-light-success">Active</small>'
                        : '<small class="badge fw-semi-bold rounded-pill badge-light-danger">Inactive</small>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" id="drop" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs--1"></span></button><div class="dropdown-menu" aria-labelledby="drop">';
                    if (Helper::userCan(111, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-primary update_status" data-id="' . $row['id'] . '">Update Status</button>';
                    }
                    if (Helper::userAllowed(111)) {
                        return $btn;
                    } else {
                        return '';
                    }
                })
                ->orderColumn('created_at', fn($query, $order) => $query->orderBy('created_at', $order))
                ->rawColumns([ 'kyc_status','action', 'status'])
                ->make(true);
        }
        return view('staticdata.profile');
    }

    public function update_status(Request $request): JsonResponse
    {
        $seller = SellerList::find($request->id);

        if (!$seller || !$request->has('status')) {
            return response()->json(['status' => false, 'message' => 'Something went wrong.']);
        }
        $seller->status = $request->status;
        $seller->save();
        $message = match((int)$request->status) {
            1 => 'Profile Approved successfully.',
            2 => 'Profile Disapproved successfully.',
            default => 'status updated.',
        };

        return response()->json(['status' => true, 'message' => $message]);
    }

}
