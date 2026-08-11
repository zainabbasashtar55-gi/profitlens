<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice)
    {
    }

    public function envelope(): Envelope
    {
        $tenantName = tenant('name') ?? config('app.name');
        return new Envelope(
            subject: "Invoice {$this->invoice->invoice_number} from {$tenantName}",
            replyTo: array_filter([
                $this->invoice->creator?->email,
            ]),
        );
    }

    public function content(): Content
    {
        // Public magic-link URL — works without auth, hits PublicInvoiceController.
        $publicUrl = request()->getSchemeAndHttpHost() . '/pay/' . $this->invoice->public_token;

        return new Content(
            view: 'emails.invoice',
            with: [
                'invoice'    => $this->invoice,
                'tenantName' => tenant('name') ?? config('app.name'),
                'publicUrl'  => $publicUrl,
            ],
        );
    }
}
