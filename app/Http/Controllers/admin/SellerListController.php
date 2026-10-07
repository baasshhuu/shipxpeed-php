<?php

namespace App\Http\Controllers\admin;

use App\Models\SellerList;
use App\Models\SellerAgreement;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class SellerListController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $data = SellerList::with('agreement')->select(
                'id',
                'negative_balance',
                'fixed_price',
                'user_type',
                'name',
                'email',
                'phone_number',
                'pan_card',
                'adhar_card_front',
                'adhar_card_back',
                'profile',
                'gst_no',
                'gst_photo',
                'cancel_cheque',
                'status',
                'created_at'
            );

            return Datatables::of($data)
                ->editColumn('profile', fn($row) => '<div class="img-group"><img src="' . asset('storage/' . $row->profile) . '" alt="" width="50"></div>')
                ->editColumn('adhar_card_front', fn($row) => '<div class="img-group"><img src="' . asset('storage/' . $row->adhar_card_front) . '" alt="" width="50"></div>')
                ->editColumn('adhar_card_back', fn($row) => '<div class="img-group"><img src="' . asset('storage/' . $row->adhar_card_back) . '" alt="" width="50"></div>')
                ->editColumn('gst_photo', fn($row) => '<div class="img-group"><img src="' . asset('storage/' . $row->gst_photo) . '" alt="" width="50"></div>')
                ->editColumn('cancel_cheque', fn($row) => '<div class="img-group"><img src="' . asset('storage/' . $row->cancel_cheque) . '" alt="" width="50"></div>')
                ->editColumn('pan_card', fn($row) => '<div class="img-group"><img src="' . asset('storage/' . $row->pan_card) . '" alt="" width="50"></div>')
                ->editColumn('created_at', fn($row) => $row->created_at->format('d M, Y'))
                ->editColumn('status', fn($row) => $row->status == 1
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-danger">Inactive</span>')
                ->addColumn('view_agreement', fn($row) => '<button class="btn btn-sm btn-primary view-agreement" data-id="' . $row->id . '">Agreement</button>')
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                            <button class="btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">Actions</button>
                            <ul class="dropdown-menu">';
                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<li><a class="dropdown-item" href="' . route('seller-list.edit', $row->id) . '">Edit</a></li>';
                    }
                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<li><button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button></li>';
                    }
                    $btn .= '<li><a class="dropdown-item" href="' . route('seller.impersonate', $row->id) . '">Access Seller Account</a></li>
                        </ul>
                    </div>';
                    return $btn;
                })
                ->rawColumns(['profile', 'adhar_card_front', 'adhar_card_back', 'gst_photo', 'cancel_cheque', 'pan_card', 'status', 'view_agreement', 'action'])
                ->make(true);
        }

        return view('sellerlist.index');
    }

public function download()
{
    $seller = Auth::guard('seller')->user(); 
    
    $agreement = SellerAgreement::where('seller_id', $seller->id)->first();

    if (!$agreement || !$agreement->pdf_path || !Storage::disk('public')->exists($agreement->pdf_path)) {
        return back()->with('error', 'Agreement file not found.');
    }

    return Storage::disk('public')->download($agreement->pdf_path);
}


    public function fetchAgreement(Request $request): JsonResponse
    {
        $seller = SellerList::with('agreement')->find($request->seller_id);

        if ($seller && $seller->agreement) {
            return response()->json([
                'client_name' => $seller->agreement->client_name,
                'client_address' => $seller->agreement->client_address,
                'client_pan' => $seller->agreement->client_pan,
                'agreement_content' => nl2br(e($seller->agreement->agreement_content)),
            ]);
        }

        return response()->json(['error' => 'Agreement not found.'], 404);
    }


    public function add(): View
    {
        return view('cms.add');
    }

    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:10000'],
            'status' => ['required', 'integer'],
            'image' => ['image', 'mimes:jpg,png,jpeg', 'max:5048']
        ]);

        $data = [...$validated, 'image' => 'cms/image.png'];
        if ($request->file('image')) {
            $data['image'] = Helper::saveFile($request->file('image'), 'cms');
        }

        Cms::create($data);
        return to_route('cms')->withSuccess('Cms Added Successfully..!!');
    }

    public function edit($id): View|RedirectResponse
    {
        $cms = SellerList::find($id);
        if (!$cms) {
            return to_route('seller-list')->withError('Seller Not Found..!!');
        }
        return view('sellerlist.edit', compact('cms'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        // dd($request);
        $cms = SellerList::find($id);
        if (!$cms) {
            return to_route('seller-list')->withError('Seller Not Found..!!');
        }

        $data = $request->validate([
            'user_type' => ['nullable'],
            'negative_balance' => ['nullable'],
            'fixed_price' => ['nullable'],

            'name' => ['nullable'],
            'email' => ['nullable'],
            'phone_number' => ['nullable'],
            'status' => ['nullable'],
            'pan_card' => ['nullable'],
            'adhar_card_front' => ['nullable'],
            'adhar_card_back' => ['nullable'],
            'gst_no' => ['nullable'],
            'gst_photo' => ['nullable'],
            'cancel_cheque' => ['nullable'],
            'profile' => ['nullable'],

            'kyc_type' => ['nullable'],
            'ie_Code' => ['nullable'],
            'ie_photo' => ['nullable'],
            'ad_Code' => ['nullable'],
            'ad_photo' => ['nullable'],

        ]);



        if ($request->file('ie_photo')) {
            Helper::deleteFile($cms->ie_photo);
            $data['ie_photo'] = Helper::saveFile($request->file('ie_photo'), 'seller');
        }

        if ($request->file('ad_photo')) {
            Helper::deleteFile($cms->ad_photo);
            $data['ad_photo'] = Helper::saveFile($request->file('ad_photo'), 'seller');
        }


        if ($request->file('image')) {
            Helper::deleteFile($cms->image);
            $data['image'] = Helper::saveFile($request->file('image'), 'seller');
        }

        if ($request->file('pan_card')) {
            Helper::deleteFile($cms->pan_card);
            $data['pan_card'] = Helper::saveFile($request->file('pan_card'), 'seller');
        }
        if ($request->file('profile')) {
            Helper::deleteFile($cms->profile);
            $data['profile'] = Helper::saveFile($request->file('profile'), 'profile');
        }

        if ($request->file('adhar_card_front')) {
            Helper::deleteFile($cms->adhar_card_front);
            $data['adhar_card_front'] = Helper::saveFile($request->file('adhar_card_front'), 'seller');
        }

        if ($request->file('adhar_card_back')) {
            Helper::deleteFile($cms->adhar_card_back);
            $data['adhar_card_back'] = Helper::saveFile($request->file('adhar_card_back'), 'seller');
        }

        if ($request->file('gst_photo')) {
            Helper::deleteFile($cms->gst_photo);
            $data['gst_photo'] = Helper::saveFile($request->file('gst_photo'), 'seller');
        }

        if ($request->file('cancel_cheque')) {
            Helper::deleteFile($cms->cancel_cheque);
            $data['cancel_cheque'] = Helper::saveFile($request->file('cancel_cheque'), 'seller');
        }

        $cms->update($data);
        return to_route('seller-list')->withSuccess('Seller List Updated Successfully..!!');
    }


    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new SellerList, $request->id);
    }
}
