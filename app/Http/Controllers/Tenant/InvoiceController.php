<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Mail\InvoiceMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Invoice::query()->with(['customer', 'creator']);

        if ($status = $request->query('status')) {
            if ($status === 'overdue') {
                $query->overdue();
            } else {
                $query->where('status', $status);
            }
        }
        if ($customerId = $request->query('customer_id')) {
            $query->where('customer_id', $customerId);
        }
        if ($search = $request->query('q')) {
            $query->where(fn ($q) => $q
                ->where('invoice_number', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"))
            );
        }

        // Headline counters for the index page chips.
        $counts = [
            'total'    => Invoice::count(),
            'draft'    => Invoice::where('status', Invoice::STATUS_DRAFT)->count(),
            'sent'     => Invoice::whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_VIEWED])->count(),
            'overdue'  => Invoice::overdue()->count(),
            'paid_mtd' => Invoice::where('status', Invoice::STATUS_PAID)
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('total'),
            'outstanding' => Invoice::open()->sum(DB::raw('total - amount_paid')),
        ];

        return view('tenant.invoices.index', [
            'invoices'  => $query->orderByDesc('issue_date')->orderByDesc('id')->paginate(20)->withQueryString(),
            'customers' => Customer::orderBy('name')->get(),
            'counts'    => $counts,
            'filters'   => $request->only(['status', 'customer_id', 'q']),
        ]);
    }

    public function create(Request $request): View
    {
        // Pre-fill from an existing customer if ?customer_id=X
        $invoice = new Invoice([
            'customer_id'  => $request->query('customer_id'),
            'issue_date'   => now()->toDateString(),
            'due_date'     => now()->addDays(14)->toDateString(),
            'currency'     => 'USD',
            'payment_terms'=> 'Net 14',
        ]);

        return view('tenant.invoices.form', [
            'invoice'   => $invoice,
            'customers' => Customer::orderBy('name')->get(),
            'products'  => Product::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateInvoice($request);

        $invoice = DB::transaction(function () use ($data, $request) {
            $invoice = Invoice::create([
                'customer_id'   => $data['customer_id'],
                'created_by'    => $request->user()->id,
                'issue_date'    => $data['issue_date'],
                'due_date'      => $data['due_date'],
                'currency'      => $data['currency'] ?? 'USD',
                'notes'         => $data['notes'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
            ]);

            foreach ($data['items'] as $row) {
                $invoice->items()->create([
                    'product_id'  => $row['product_id'] ?? null,
                    'description' => $row['description'],
                    'quantity'    => $row['quantity'],
                    'unit_price'  => $row['unit_price'],
                    'unit_cost'   => $row['unit_cost'] ?? 0,
                    'tax_rate'    => $row['tax_rate'] ?? 0,
                ]);
            }

            $invoice->recomputeTotals();
            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('status', "Invoice {$invoice->invoice_number} created. Click Send when ready.");
    }

    public function show(Invoice $invoice): View
    {
        return view('tenant.invoices.show', [
            'invoice' => $invoice->load(['customer', 'items', 'creator']),
        ]);
    }

    public function edit(Invoice $invoice): View
    {
        // Once sent/paid, the invoice is the contract — disallow edits.
        abort_if(in_array($invoice->status, [Invoice::STATUS_PAID, Invoice::STATUS_VOID], true), 403, 'Cannot edit a paid or void invoice. Void it and create a new one.');

        return view('tenant.invoices.form', [
            'invoice'   => $invoice->load('items'),
            'customers' => Customer::orderBy('name')->get(),
            'products'  => Product::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_if(in_array($invoice->status, [Invoice::STATUS_PAID, Invoice::STATUS_VOID], true), 403);

        $data = $this->validateInvoice($request);

        DB::transaction(function () use ($invoice, $data) {
            $invoice->update([
                'customer_id'   => $data['customer_id'],
                'issue_date'    => $data['issue_date'],
                'due_date'      => $data['due_date'],
                'currency'      => $data['currency'] ?? 'USD',
                'notes'         => $data['notes'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
            ]);

            $invoice->items()->delete();
            foreach ($data['items'] as $row) {
                $invoice->items()->create([
                    'product_id'  => $row['product_id'] ?? null,
                    'description' => $row['description'],
                    'quantity'    => $row['quantity'],
                    'unit_price'  => $row['unit_price'],
                    'unit_cost'   => $row['unit_cost'] ?? 0,
                    'tax_rate'    => $row['tax_rate'] ?? 0,
                ]);
            }
            $invoice->recomputeTotals();
        });

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice updated.');
    }

    public function send(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->customer?->email, 422, 'Customer has no email on file. Edit the customer first.');

        Mail::to($invoice->customer->email)->send(new InvoiceMail($invoice));

        $invoice->markAsSent();

        return back()->with('status', "Invoice {$invoice->invoice_number} emailed to {$invoice->customer->email}.");
    }

    public function markPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        $request->validate(['amount' => ['nullable', 'numeric', 'min:0.01']]);

        $amount = $request->input('amount') !== null ? (float) $request->input('amount') : null;
        $invoice->recordPayment($amount, $request->user()->id);

        return back()->with('status', $invoice->isPaid()
            ? "Invoice {$invoice->invoice_number} marked as paid."
            : 'Payment recorded — balance still due.');
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);
        // Don't truly delete if it's been sent — void it instead so audit trail is intact.
        if (in_array($invoice->status, [Invoice::STATUS_DRAFT], true)) {
            $invoice->delete();
            return redirect()->route('invoices.index')->with('status', 'Draft invoice deleted.');
        }

        $invoice->voidInvoice();
        return redirect()->route('invoices.index')->with('status', "Invoice {$invoice->invoice_number} voided.");
    }

    public function print(Invoice $invoice): View
    {
        return view('tenant.invoices.print', ['invoice' => $invoice->load(['customer', 'items'])]);
    }

    private function validateInvoice(Request $request): array
    {
        return $request->validate([
            'customer_id'   => ['required', 'exists:customers,id'],
            'issue_date'    => ['required', 'date'],
            'due_date'      => ['required', 'date', 'after_or_equal:issue_date'],
            'currency'      => ['nullable', 'string', 'size:3'],
            'notes'         => ['nullable', 'string', 'max:2000'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.description'  => ['required', 'string', 'max:255'],
            'items.*.quantity'     => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'   => ['required', 'numeric', 'min:0'],
            'items.*.unit_cost'    => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate'     => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.product_id'   => ['nullable', 'exists:products,id'],
        ]);
    }
}
