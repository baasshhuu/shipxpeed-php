<?php

namespace App\Mail;

use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\URL;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;

class UserVerification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $site_settings;
    public $url;

    public function __construct($user, $site_settings)
    {
        $this->user     = $user;
        $this->site_settings  = Arr::only($site_settings, ['logo', 'application_name',]);
        $this->url      = $this->verificationUrl($user);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'User Verification :: ' . $this->site_settings['application_name'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'email.userverify',
        );
    }

    public function attachments(): array
    {
        return [];
    }

    protected function verificationUrl($user)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'guard' => $user->getGuard(),
                'id'    => $user->getKey(),
                'hash'  => sha1($user->getEmailForVerification()),
            ]
        );
    }
}
