<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Sent to the support inbox every time a seller raises a ticket. */
class TicketRaisedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket)
    {
    }

    public function build()
    {
        $seller = $this->ticket->seller;
        $mail = $this->subject(sprintf(
            'New ticket %s - %s (AWB %s)',
            $this->ticket->ticket_id,
            $this->ticket->subject ?: 'Support',
            $this->ticket->order_id
        ))->view('email.ticket_raised', ['ticket' => $this->ticket, 'seller' => $seller]);

        if ($seller && filter_var($seller->email, FILTER_VALIDATE_EMAIL)) {
            $mail->replyTo($seller->email, $seller->name);
        }

        return $mail;
    }
}
