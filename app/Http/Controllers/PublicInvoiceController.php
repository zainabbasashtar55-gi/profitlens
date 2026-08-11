<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Customer-facing invoice view, authenticated by magic-link token.
 * Routes pass through tenant init middleware so we resolve the right tenant
 * DB — but NOT through `auth`, since the customer has no profitlens account.
 */
class PublicInvoiceController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $invoice = Invoice::where('public_token', $token)->firstOrFail();
        $invoice->markAsViewed();

        return view('tenant.invoices.public', [
            'invoice' => $invoice->load(['customer', 'items']),
            'tenant'  => tenant(),
        ]);
    }

    public function print(Request $request, string $token): View
    {
        $invoice = Invoice::where('public_token', $token)->firstOrFail();

        return view('tenant.invoices.print', [
            'invoice' => $invoice->load(['customer', 'items']),
        ]);
    }

    /**
     * Placeholder "pay now" handler. Until Stripe Connect lands, this just
     * records a manual self-paid mark — useful for demoing the post-payment
     * flow (sale auto-records, status flips to paid).
     */
    public function pay(Request $request, string $token): RedirectResponse
    {
        $invoice = Invoice::where('public_token', $token)->firstOrFail();

        if ($invoice->isPaid()) {
            return back()->with('status', 'This invoice has already been paid.');
        }

        $invoice->recordPayment();

        return redirect()->route('public.invoice.show', $token)
            ->with('status', 'Payment confirmed — thank you!');
    }
}
