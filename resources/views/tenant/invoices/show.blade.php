@extends('layouts.tenant')

@section('title', 'Invoice ' . $invoice->invoice_number)

@php
    $fmt = fn ($n) => '$' . number_format((float) $n, 2);
    $publicUrl = url('/pay/' . $invoice->public_token);
    $isLocked  = in_array($invoice->status, [\App\Models\Invoice::STATUS_PAID, \App\Models\Invoice::STATUS_VOID], true);
    $statusCls = match ($invoice->statusLabel()) {
        'paid'    => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'sent'    => 'bg-sky-50 text-sky-700 ring-sky-200',
        'viewed'  => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'overdue' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'void'    => 'bg-slate-100 text-slate-500 ring-slate-200',
        default   => 'bg-amber-50 text-amber-700 ring-amber-200',
    };
@endphp

@section('content')
    <div class="flex items-start justify-between mb-6 flex-wrap gap-3">
        <div>
            <div class="flex items-baseline gap-3">
                <h1 class="text-2xl font-bold">{{ $invoice->invoice_number }}</h1>
                <span class="text-xs px-2 py-0.5 rounded ring-1 ring-inset capitalize {{ $statusCls }}">{{ $invoice->statusLabel() }}</span>
            </div>
            <p class="text-sm text-slate-600 mt-1">To {{ $invoice->customer->name }} · Issued {{ $invoice->issue_date->format('M j, Y') }} · Due {{ $invoice->due_date->format('M j, Y') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="px-3 py-2 rounded-md border border-slate-300 bg-white text-sm hover:bg-slate-50">Print</a>
            @unless ($isLocked)
                <a href="{{ route('invoices.edit', $invoice) }}" class="px-3 py-2 rounded-md border border-slate-300 bg-white text-sm hover:bg-slate-50">Edit</a>
            @endunless
            @if ($invoice->status === \App\Models\Invoice::STATUS_DRAFT)
                <form method="POST" action="{{ route('invoices.send', $invoice) }}" onsubmit="return confirm('Email this invoice to {{ $invoice->customer->email }}?')">
                    @csrf
                    <button class="px-3 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Send →</button>
                </form>
            @elseif (! $isLocked)
                <form method="POST" action="{{ route('invoices.send', $invoice) }}" onsubmit="return confirm('Resend this invoice email?')">
                    @csrf
                    <button class="px-3 py-2 rounded-md border border-indigo-300 text-indigo-700 bg-white text-sm hover:bg-indigo-50">Resend email</button>
                </form>
            @endif
            @unless ($invoice->isPaid() || $invoice->status === \App\Models\Invoice::STATUS_VOID)
                <form method="POST" action="{{ route('invoices.mark-paid', $invoice) }}" onsubmit="return confirm('Mark this invoice as fully paid? This will also record a sale.')">
                    @csrf
                    <button class="px-3 py-2 rounded-md bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Mark paid</button>
                </form>
            @endunless
            @if (auth()->user()->hasAnyRole(['owner', 'admin']))
                <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('{{ $invoice->status === 'draft' ? 'Delete this draft?' : 'Void this invoice?' }}')">
                    @csrf @method('DELETE')
                    <button class="px-3 py-2 rounded-md border border-rose-200 text-rose-600 bg-white text-sm hover:bg-rose-50">{{ $invoice->status === 'draft' ? 'Delete' : 'Void' }}</button>
                </form>
            @endif
        </div>
    </div>

    @if ($invoice->isOverdue())
        <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm mb-4">
            <strong>{{ $invoice->daysOverdue() }} days overdue.</strong> Send a reminder to {{ $invoice->customer->name }}.
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-4">
        <section class="lg:col-span-2 bg-white rounded-lg border border-slate-200 p-6">
            <div class="flex justify-between items-start mb-6 gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase text-slate-500">From</div>
                    <div class="text-sm font-medium">{{ tenant('name') }}</div>
                    <div class="text-xs text-slate-500">Issued by {{ $invoice->creator?->name ?? '—' }}</div>
                </div>
                <div class="text-right">
                    <div class="text-xs font-semibold uppercase text-slate-500">Bill to</div>
                    <div class="text-sm font-medium">{{ $invoice->customer->name }}</div>
                    @if ($invoice->customer->company)<div class="text-xs text-slate-500">{{ $invoice->customer->company }}</div>@endif
                    @if ($invoice->customer->email)<div class="text-xs text-slate-500">{{ $invoice->customer->email }}</div>@endif
                </div>
            </div>

            <table class="w-full text-sm mb-4">
                <thead class="text-xs uppercase text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="text-left pb-2">Description</th>
                        <th class="text-right pb-2">Qty</th>
                        <th class="text-right pb-2">Price</th>
                        <th class="text-right pb-2">Tax</th>
                        <th class="text-right pb-2">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr class="border-b border-slate-100">
                            <td class="py-2">{{ $item->description }}</td>
                            <td class="py-2 text-right">{{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }}</td>
                            <td class="py-2 text-right font-mono">{{ $fmt($item->unit_price) }}</td>
                            <td class="py-2 text-right text-slate-500">{{ (float) $item->tax_rate > 0 ? number_format($item->tax_rate, 2) . '%' : '—' }}</td>
                            <td class="py-2 text-right font-mono">{{ $fmt($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right py-1 text-slate-600">Subtotal</td>
                        <td class="text-right py-1 font-mono">{{ $fmt($invoice->subtotal) }}</td>
                    </tr>
                    @if ((float) $invoice->tax_total > 0)
                        <tr>
                            <td colspan="4" class="text-right py-1 text-slate-600">Tax</td>
                            <td class="text-right py-1 font-mono">{{ $fmt($invoice->tax_total) }}</td>
                        </tr>
                    @endif
                    <tr class="border-t border-slate-200">
                        <td colspan="4" class="text-right py-2 font-semibold">Total</td>
                        <td class="text-right py-2 font-bold font-mono text-base">{{ $fmt($invoice->total) }} {{ $invoice->currency }}</td>
                    </tr>
                    @if ((float) $invoice->amount_paid > 0)
                        <tr>
                            <td colspan="4" class="text-right py-1 text-slate-600">Paid</td>
                            <td class="text-right py-1 font-mono text-emerald-700">−{{ $fmt($invoice->amount_paid) }}</td>
                        </tr>
                        <tr class="border-t border-slate-200">
                            <td colspan="4" class="text-right py-2 font-semibold">Balance due</td>
                            <td class="text-right py-2 font-bold font-mono">{{ $fmt($invoice->balanceDue()) }}</td>
                        </tr>
                    @endif
                </tfoot>
            </table>

            @if ($invoice->notes)
                <div class="mt-4 bg-slate-50 border border-slate-200 rounded p-3 text-sm">
                    <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Notes</div>
                    {{ $invoice->notes }}
                </div>
            @endif
            @if ($invoice->payment_terms)
                <div class="mt-3 text-xs text-slate-500">Payment terms: {{ $invoice->payment_terms }}</div>
            @endif
        </section>

        <aside class="space-y-4">
            <section class="bg-white rounded-lg border border-slate-200 p-5">
                <div class="text-xs font-semibold uppercase text-slate-500 mb-2">Customer pay link</div>
                <div class="flex items-center gap-2">
                    <input id="paylink" type="text" readonly value="{{ $publicUrl }}" class="flex-1 rounded-md border border-slate-300 px-2 py-1.5 text-xs font-mono bg-slate-50">
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('paylink').value); this.textContent='Copied'; setTimeout(()=>this.textContent='Copy', 1500)" class="text-xs px-2 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">Copy</button>
                </div>
                <p class="text-xs text-slate-500 mt-2">Anyone with this link can view &amp; pay. It's embedded in the invoice email.</p>
                <a href="{{ $publicUrl }}" target="_blank" class="block mt-3 text-center text-xs text-indigo-600 hover:underline">Preview customer view ↗</a>
            </section>

            <section class="bg-white rounded-lg border border-slate-200 p-5">
                <div class="text-xs font-semibold uppercase text-slate-500 mb-3">Timeline</div>
                <ol class="space-y-3 text-sm">
                    <li class="flex gap-3">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mt-1.5 shrink-0"></span>
                        <div><div class="font-medium">Created</div><div class="text-xs text-slate-500">{{ $invoice->created_at->format('M j, Y g:i A') }}</div></div>
                    </li>
                    @if ($invoice->sent_at)
                        <li class="flex gap-3">
                            <span class="w-2 h-2 rounded-full bg-sky-500 mt-1.5 shrink-0"></span>
                            <div><div class="font-medium">Sent</div><div class="text-xs text-slate-500">{{ $invoice->sent_at->format('M j, Y g:i A') }}</div></div>
                        </li>
                    @endif
                    @if ($invoice->viewed_at)
                        <li class="flex gap-3">
                            <span class="w-2 h-2 rounded-full bg-indigo-500 mt-1.5 shrink-0"></span>
                            <div><div class="font-medium">Viewed by customer</div><div class="text-xs text-slate-500">{{ $invoice->viewed_at->format('M j, Y g:i A') }}</div></div>
                        </li>
                    @endif
                    @if ($invoice->paid_at)
                        <li class="flex gap-3">
                            <span class="w-2 h-2 rounded-full bg-emerald-600 mt-1.5 shrink-0"></span>
                            <div><div class="font-medium">Paid</div><div class="text-xs text-slate-500">{{ $invoice->paid_at->format('M j, Y g:i A') }}</div></div>
                        </li>
                    @endif
                </ol>
            </section>

            @unless ($invoice->isPaid() || $invoice->status === \App\Models\Invoice::STATUS_VOID)
                <section class="bg-white rounded-lg border border-slate-200 p-5">
                    <div class="text-xs font-semibold uppercase text-slate-500 mb-2">Record a partial payment</div>
                    <form method="POST" action="{{ route('invoices.mark-paid', $invoice) }}" class="flex gap-2">
                        @csrf
                        <input type="number" step="0.01" min="0.01" max="{{ $invoice->balanceDue() }}" name="amount" placeholder="Amount" class="flex-1 rounded-md border border-slate-300 px-2 py-1.5 text-sm font-mono">
                        <button class="px-3 py-1.5 rounded-md bg-emerald-600 text-white text-sm hover:bg-emerald-700">Apply</button>
                    </form>
                    <p class="text-xs text-slate-500 mt-2">Leave blank in the Mark paid button to settle the full balance.</p>
                </section>
            @endunless
        </aside>
    </div>
@endsection
