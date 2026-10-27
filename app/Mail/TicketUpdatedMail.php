<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Sent to the seller when the admin changes status or replies. */
class TicketUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket)
    {
    }

    public function build()
    {
        return $this->subject(sprintf(
            'Your ticket %s is now %s',
            $this->ticket->ticket_id,
            $this->ticket->status
        ))->view('email.ticket_updated', ['ticket' => $this->ticket, 'seller' => $this->ticket->seller]);
    }
}
