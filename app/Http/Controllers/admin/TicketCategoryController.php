<?php

namespace App\Http\Controllers\admin;

use App\Models\Cms;
use App\Helper\Helper;
use App\Models\TicketCategory;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
class TicketCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $data = TicketCategory::select('id', 'name', 'image', 'status', 'created_at');
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
                        $btn .= '<a class="dropdown-item" href="' . route('cms.edit', $row['id']) . '">Edit</a>';
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
        return view('ticketcategory.index');
    }

    public function add(): View
    {
        return view('cms.add');
    }

    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'         => ['required', 'string', 'max:200'],
            'description'   => ['required', 'string', 'max:10000'],
            'status'        => ['required', 'integer'],
            'image'         => ['image', 'mimes:jpg,png,jpeg', 'max:5048']
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
        $cms = Cms::find($id);
        if (!$cms) {
            return to_route('cms')->withError('Cms Not Found..!!');
        }
        return view('cms.edit', compact('cms'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $cms = Cms::find($id);
        if (!$cms) {
            return to_route('cms')->withError('Cms Not Found..!!');
        }

        $data = $request->validate([
            'title'         => ['required', 'string', 'max:200'],
            'description'   => ['required', 'string', 'max:10000'],
            'status'        => ['required', 'integer'],
            'image'         => ['image', 'mimes:jpg,png,jpeg', 'max:5048']
        ]);

        if ($request->file('image')) {
            Helper::deleteFile($cms->image);
            $data['image'] = Helper::saveFile($request->file('image'), 'cms');
        }

        $cms->update($data);
        return to_route('cms')->withSuccess('Cms Updated Successfully..!!');
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Cms, $request->id);
    }




    public function ticketindex(Request $request)
    {
        $query = Ticket::with('seller')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('ticket_id', 'like', "%$q%")
                  ->orWhere('order_id', 'like', "%$q%")
                  ->orWhere('message', 'like', "%$q%")
                  ->orWhereHas('seller', fn ($s) => $s->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%"));
            });
        }

        $tickets = $query->paginate(25)->withQueryString();
        $counts = Ticket::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $statuses = Ticket::STATUSES;

        return view('ticket.ticketindex', compact('tickets', 'counts', 'statuses'));
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'status'      => ['required', 'in:' . implode(',', Ticket::STATUSES)],
            'admin_reply' => ['nullable', 'string', 'max:5000'],
        ]);

        $changed = $ticket->status !== $data['status'];
        $ticket->status = $data['status'];
        if ($request->has('admin_reply') && trim((string) $data['admin_reply']) !== '' && trim((string) $data['admin_reply']) !== (string) $ticket->admin_reply) {
            $ticket->admin_reply = trim($data['admin_reply']);
            $ticket->replied_at = now();
            $changed = true;
        }
        $ticket->save();

        $note = 'Ticket ' . $ticket->ticket_id . ' updated';
        $seller = $ticket->seller;
        if ($changed && $seller && filter_var($seller->email, FILTER_VALIDATE_EMAIL)) {
            try {
                \Illuminate\Support\Facades\Mail::to($seller->email)->send(new \App\Mail\TicketUpdatedMail($ticket));
                $note .= ' and seller emailed';
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Ticket update email failed for ' . $ticket->ticket_id . ': ' . $e->getMessage());
                $note .= ' (seller email could not be sent)';
            }
        }

        return back()->with('success', $note);
    }

}
