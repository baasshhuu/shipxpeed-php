<?php

namespace App\Http\Controllers\sellerAdmin;

use App\Http\Controllers\Controller;
use App\Mail\TicketRaisedMail;
use App\Models\Recharge;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TicketController extends Controller
{
    private function walletBalance($seller): float
    {
        if (!$seller || $seller->status != 1) {
            return 0;
        }
        $credit = Recharge::where('seller_id', $seller->id)->where('status', 1)->where('type', 'Credit')->sum('amount');
        $debit  = Recharge::where('seller_id', $seller->id)->where('type', 'Debit')->sum('amount');
        return $credit - $debit;
    }

    public function index()
    {
        $seller = Auth::guard('seller')->user();
        $tickets = Ticket::where('seller_id', $seller->id)->latest()->get();
        $totalAmount = $this->walletBalance($seller);
        return view('sellerdashboard.ticket.index', compact('tickets', 'seller', 'totalAmount'));
    }

    public function add()
    {
        $seller = Auth::guard('seller')->user();
        $totalAmount = $this->walletBalance($seller);
        $subjects = Ticket::SUBJECTS;
        return view('sellerdashboard.ticket.add', compact('seller', 'totalAmount', 'subjects'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|string|max:100',
            'subject'  => 'nullable|string|max:100',
            'message'  => 'required|string|max:5000',
        ]);

        $ticket = Ticket::create([
            'seller_id' => auth()->guard('seller')->id(),
            'order_id'  => trim($data['order_id']),
            'subject'   => $data['subject'] ?? null,
            'message'   => trim($data['message']),
            'status'    => 'Pending',
        ]);
        $ticket->refresh()->load('seller');

        // Email every new ticket to the support inbox. A mail failure never loses the ticket.
        $to = config('mail.ticket_notify', 'baasshhuu@gmail.com');
        try {
            Mail::to($to)->send(new TicketRaisedMail($ticket));
            $ticket->forceFill(['mail_sent_at' => now()])->saveQuietly();
        } catch (\Throwable $e) {
            Log::error('Ticket email failed for ' . $ticket->ticket_id . ': ' . $e->getMessage());
        }

        return redirect()->route('seller.ticket.get')
            ->with('success', 'Ticket ' . $ticket->ticket_id . ' raised. Our team will reply soon.');
    }
}
