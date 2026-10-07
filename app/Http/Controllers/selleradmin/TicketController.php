<?php

namespace App\Http\Controllers\sellerAdmin;

use App\Http\Controllers\Controller;
use App\Models\Recharge;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function index()
    {
        // echo 'xcscs';die;
        $seller = Auth::guard('seller')->user();
        $tickets = Ticket::where('seller_id', $seller->id)->get();
        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
                }
        return view('sellerdashboard.ticket.index', compact('tickets', 'seller', 'totalAmount'));
    }

    public function add()
    {
        $tickets = Ticket::all();
        $seller = Auth::guard('seller')->user();
        $tickets = Ticket::all();
        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;        }
        return view('sellerdashboard.ticket.add', compact('tickets', 'seller', 'totalAmount'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required',
            'message' => 'required|string',
        ]);


        // $lastTicket = Ticket::latest()->first();
        // $nextId = $lastTicket ? $lastTicket->id + 1 : 1;
        // $ticketId = '#'.str_pad($nextId, 4, '0', STR_PAD_LEFT); // Format: #0001, #0002, ...


        Ticket::create([
            // 'ticket_id' => $ticketId,
            'seller_id' => auth()->guard('seller')->id(),
            'order_id' => $request->order_id,
            // 'category_id' => $request->category_id,
            'message' => $request->message,
            'status' => 'Pending',
        ]);

        return redirect()->route('seller.ticket.get')->with('success', 'Ticket raised successfully');
    }

}
