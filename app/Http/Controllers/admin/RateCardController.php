<?php

namespace App\Http\Controllers\admin;

use App\Models\Cms;
use App\Helper\Helper;
use App\Models\RateCard;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Models\SellerList;

class RateCardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $data = RateCard::select('id', 'seller_id', 'rate_pdf_1', 'rate_pdf_2', 'rate_pdf_3', 'status', 'created_at');
            return Datatables::of($data)
                ->editColumn('rate_pdf_1', function ($row) {
                    $downloadUrl = asset('storage/' . $row['rate_pdf_1']);
                    return '<a href="' . $downloadUrl . '" class="btn btn-sm btn-primary" download>Download 10% PDF</a>';
                })
                ->editColumn('rate_pdf_2', function ($row) {
                    $downloadUrl = asset('storage/' . $row['rate_pdf_2']);
                    return '<a href="' . $downloadUrl . '" class="btn btn-sm btn-primary" download>Download 20% PDF</a>';
                })
                ->editColumn('rate_pdf_3', function ($row) {
                    $downloadUrl = asset('storage/' . $row['rate_pdf_3']);
                    return '<a href="' . $downloadUrl . '" class="btn btn-sm btn-primary" download>Download 30% PDF</a>';
                })
                ->editColumn('created_at', function ($row) {
                    return $row['created_at']->format('d M, Y');
                })
                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Active</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Inactive</small>';
                })
                ->addColumn('seller_name', function ($row) {
                    $name = SellerList::find($row['seller_id']);
                    return $name ? $name->name : '';
                })
                ->addColumn('action', function ($row) {

                    $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" id="drop" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs--1"></span></button><div class="dropdown-menu" aria-labelledby="drop">';
                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('rate-card.edit', $row['id']) . '">Edit</a>';
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
                ->rawColumns(['action', 'seller_name', 'rate_pdf_1', 'rate_pdf_2', 'rate_pdf_3', 'status'])
                ->make(true);
        }
        return view('ratecards.index');
    }

    public function add(): View
    {
        $sellers = SellerList::active()->get();

        return view('ratecards.add', compact('sellers'));
    }


    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'seller_id'   => ['nullable'],
            'rate_pdf_1'  => ['nullable', 'file', 'mimes:pdf'],
            'rate_pdf_2'  => ['nullable', 'file', 'mimes:pdf'],
            'rate_pdf_3'  => ['nullable', 'file', 'mimes:pdf'],
            'status'      => ['required'],
        ]);

        $data = $validated;

        if ($request->hasFile('rate_pdf_1') && $request->input('seller_id')) {
            $data['rate_pdf_1'] = Helper::saveFile($request->file('rate_pdf_1'), 'ratecard');
        }

        if ($request->hasFile('rate_pdf_2') && $request->input('seller_id')) {
            $data['rate_pdf_2'] = Helper::saveFile($request->file('rate_pdf_2'), 'ratecard');
        }

        if ($request->hasFile('rate_pdf_3')) {
            // Save only once and store globally
            $pdfPath = Helper::saveFile($request->file('rate_pdf_3'), 'ratecard');

            \App\Models\AppSetting::updateOrCreate(
                ['key' => 'common_rate_pdf_3'],
                ['value' => $pdfPath]
            );
        }
        RateCard::create($data);

        return to_route('rate-card')->withSuccess('Rate card added successfully!');
    }


    public function edit($id)
    {
        $cms = RateCard::find($id);
        if (!$cms) {
            return to_route('rate-card')->withError('Rate Card Not Found..!!');
        }
        $sellers = SellerList::active()->get();
        return view('ratecards.edit', compact('cms', 'sellers'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $cms = RateCard::find($id);
        if (!$cms) {
            return to_route('rate-card')->withError('Rate Card Not Found..!!');
        }

        $data = $request->validate([
            'seller_id'         => ['nullable'],
            'rate_pdf_1'   => ['nullable'],
            'rate_pdf_2'        => ['nullable'],
            'rate_pdf_3'         => ['nullable'],
            'status'         => ['required']
        ]);

        if ($request->file('rate_pdf_1')) {
            Helper::deleteFile($cms->image);
            $data['rate_pdf_1'] = Helper::saveFile($request->file('rate_pdf_1'), 'ratecard');
        }

        if ($request->file('rate_pdf_2')) {
            Helper::deleteFile($cms->image);
            $data['rate_pdf_2'] = Helper::saveFile($request->file('rate_pdf_2'), 'ratecard');
        }
        if ($request->file('rate_pdf_3')) {
            Helper::deleteFile($cms->image);
            $data['rate_pdf_3'] = Helper::saveFile($request->file('rate_pdf_3'), 'ratecard');
        }
        $cms->update($data);
        return to_route('rate-card')->withSuccess('Cms Updated Successfully..!!');
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new RateCard(), $request->id);
    }
}
