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
                'kyc_status',
                'created_at'
            );

            switch ($request->get('filter')) {
                case 'active':      $data->where('status', 1); break;
                case 'inactive':    $data->where('status', 0); break;
                case 'kyc_pending': $data->where(fn($q) => $q->where('kyc_status', 0)->orWhereNull('kyc_status')); break;
                case 'kyc_done':    $data->where('kyc_status', 1); break;
            }

            $doc = function ($path, $label) {
                if (!$path) {
                    return '<span class="sx-chip miss" title="Not uploaded">' . $label . '</span>';
                }
                return '<a class="sx-chip ok" target="_blank" rel="noopener" href="' . e(asset('storage/' . $path)) . '">' . $label . '</a>';
            };
            $switch = function ($row, $field, $checked, $title) {
                return '<label class="sx-switch" title="' . $title . '"><input type="checkbox" class="sx-toggle" data-id="' . $row->id . '" data-field="' . $field . '" ' . ($checked ? 'checked' : '') . '><span class="s"></span></label>';
            };

            return Datatables::of($data)
                ->addColumn('seller', function ($row) {
                    $initial = strtoupper(mb_substr($row->name ?: 'S', 0, 1));
                    $avatar = $row->profile
                        ? '<img src="' . e(asset('storage/' . $row->profile)) . '" alt="" onerror="this.remove()">'
                        : '';
                    $type = $row->user_type == 2 ? 'Business' : 'Individual';
                    return '<div class="sx-seller-cell"><div class="sx-avatar">' . $initial . $avatar . '</div><div>'
                        . '<div class="n">' . e($row->name ?: '—') . ' <span class="text-muted" style="font-weight:400;font-size:12px">#' . $row->id . ' · ' . $type . '</span></div>'
                        . '<div class="e">' . e($row->email) . ($row->phone_number ? ' · ' . e($row->phone_number) : '') . '</div>'
                        . ($row->gst_no ? '<div class="e">GST: ' . e($row->gst_no) . '</div>' : '')
                        . '</div></div>';
                })
                ->addColumn('documents', function ($row) use ($doc) {
                    return '<div class="sx-doc-chips">'
                        . $doc($row->pan_card, 'PAN')
                        . $doc($row->adhar_card_front, 'Aadhaar F')
                        . $doc($row->adhar_card_back, 'Aadhaar B')
                        . $doc($row->gst_photo, 'GST')
                        . $doc($row->cancel_cheque, 'Cheque')
                        . '</div>';
                })
                ->addColumn('kyc', fn($row) => $row->kyc_status == 1
                    ? '<span class="badge bg-success">Verified</span>'
                    : '<span class="badge bg-warning">Pending</span>')
                ->addColumn('status_switch', fn($row) => $switch($row, 'status', $row->status == 1, 'Seller can log in when ON'))
                ->addColumn('negative_switch', fn($row) => $switch($row, 'negative_balance', $row->negative_balance == 1, 'Allow shipping on negative wallet balance'))
                // fixed_price is stored inverted: 0 = RTO charge active, 1 = inactive
                ->addColumn('rto_switch', fn($row) => $switch($row, 'rto_charge', $row->fixed_price == 0, 'Charge RTO to this seller'))
                ->editColumn('created_at', fn($row) => optional($row->created_at)->format('d M, Y'))
                ->addColumn('action', function ($row) {
                    $btn = '<div class="sx-actions">';
                    $btn .= '<a class="btn btn-outline-primary" target="_blank" rel="noopener" title="Open this seller\'s panel in a new tab" href="' . route('seller.impersonate', $row->id) . '"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Panel</a>';
                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="btn btn-outline-secondary" title="Edit seller" href="' . route('seller-list.edit', $row->id) . '"><i class="fa-solid fa-pen"></i></a>';
                    }
                    $btn .= '<button class="btn btn-outline-secondary view-agreement" title="Agreement" data-id="' . $row->id . '"><i class="fa-solid fa-file-signature"></i></button>';
                    if (Helper::userCan(104, 'can_delete')) {
                        $btn .= '<button class="btn btn-outline-danger delete" title="Delete seller" data-id="' . $row->id . '"><i class="fa-solid fa-trash"></i></button>';
                    }
                    return $btn . '</div>';
                })
                ->filterColumn('seller', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                          ->orWhere('email', 'like', "%{$keyword}%")
                          ->orWhere('phone_number', 'like', "%{$keyword}%")
                          ->orWhere('gst_no', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['seller', 'documents', 'kyc', 'status_switch', 'negative_switch', 'rto_switch', 'action'])
                ->make(true);
        }

        $counts = [
            'all'         => SellerList::count(),
            'active'      => SellerList::where('status', 1)->count(),
            'inactive'    => SellerList::where('status', 0)->count(),
            'kyc_pending' => SellerList::where(fn($q) => $q->where('kyc_status', 0)->orWhereNull('kyc_status'))->count(),
            'kyc_done'    => SellerList::where('kyc_status', 1)->count(),
        ];

        return view('sellerlist.index', compact('counts'));
    }

    /**
     * Quick on/off switches from the seller list. Takes effect in the seller panel immediately.
     */
    public function toggle(Request $request, $id): JsonResponse
    {
        $request->validate([
            'field' => ['required', 'in:status,negative_balance,kyc_status,rto_charge'],
            'value' => ['required', 'boolean'],
        ]);

        $seller = SellerList::find($id);
        if (!$seller) {
            return response()->json(['status' => false, 'message' => 'Seller not found.'], 404);
        }

        $on = (bool) $request->boolean('value');
        $field = $request->input('field');

        if ($field === 'rto_charge') {
            $seller->fixed_price = $on ? 0 : 1; // inverted storage
        } else {
            $seller->{$field} = $on ? 1 : 0;
        }
        $seller->save();

        $labels = [
            'status' => 'Account', 'negative_balance' => 'Negative balance',
            'kyc_status' => 'KYC', 'rto_charge' => 'RTO charge',
        ];

        return response()->json([
            'status'  => true,
            'message' => $labels[$field] . ' ' . ($on ? 'turned on' : 'turned off') . ' for ' . ($seller->name ?: 'seller #' . $seller->id) . '.',
        ]);
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
        $cms->loadMissing('sellerAddress');
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
            'kyc_status' => ['nullable', 'in:0,1'],
            'new_password' => ['nullable', 'string', 'min:6'],
        ]);

        // Never wipe an existing document just because no new file was chosen
        foreach (['pan_card', 'adhar_card_front', 'adhar_card_back', 'gst_photo', 'cancel_cheque', 'profile', 'ie_photo', 'ad_photo'] as $fileField) {
            if (!$request->hasFile($fileField)) {
                unset($data[$fileField]);
            }
        }

        if (!empty($data['new_password'])) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['new_password']);
        }
        unset($data['new_password']);



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
        return to_route('seller-list.edit', $cms->id)->withSuccess('Seller updated. Changes are live in the seller panel.');
    }


    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new SellerList, $request->id);
    }
}
