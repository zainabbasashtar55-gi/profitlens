<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invitation $invitation)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You have been invited to ' . tenant('name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenant-invitation',
            with: [
                'acceptUrl' => $this->invitation->acceptUrl(),
                'role'      => $this->invitation->role,
                'tenant'    => tenant('name'),
                'invitedBy' => $this->invitation->invitedBy?->name,
            ],
        );
    }
}
