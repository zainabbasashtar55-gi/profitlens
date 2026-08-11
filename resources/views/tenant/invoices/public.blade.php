<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Invoice {{ $invoice->invoice_number }} — {{ $tenant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    <style>html, body { font-family: 'Inter', system-ui, sans-serif; }</style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-3xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="inline-block w-7 h-7 rounded bg-indigo-600"></span>
                <div class="leading-tight">
                    <div class="font-semibold text-sm">{{ $tenant->name }}</div>
                    <div class="text-xs text-slate-500">Invoice for {{ $invoice->customer->name }}</div>
                </div>
            </div>
            <a href="{{ route('public.invoice.print', $invoice->public_token) }}" target="_blank" class="text-xs text-indigo-600 hover:underline">Download / Print →</a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-6 py-8 space-y-4">
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @php
            $statusCls = match ($invoice->statusLabel()) {
                'paid'    => 'bg-emerald-50 text-emerald-700',
                'sent'    => 'bg-sky-50 text-sky-700',
                'viewed'  => 'bg-indigo-50 text-indigo-700',
                'overdue' => 'bg-rose-50 text-rose-700',
                'void'    => 'bg-slate-100 text-slate-500',
                default   => 'bg-amber-50 text-amber-700',
            };
        @endphp

        {{-- Big "amount due" card --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-8 text-center">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Amount due</div>
                <div class="mt-2 text-4xl font-bold tabular-nums">${{ number_format($invoice->balanceDue(), 2) }} <span class="text-base font-medium text-slate-500">{{ $invoice->currency }}</span></div>
                <div class="mt-3 flex items-center justify-center gap-2 text-sm text-slate-600">
                    <span class="px-2 py-0.5 rounded text-xs capitalize {{ $statusCls }}">{{ $invoice->statusLabel() }}</span>
                    <span>·</span>
                    <span>Due {{ $invoice->due_date->format('M j, Y') }}</span>
                </div>

                @if (! $invoice->isPaid() && $invoice->status !== \App\Models\Invoice::STATUS_VOID)
                    <form method="POST" action="{{ route('public.invoice.pay', $invoice->public_token) }}" class="mt-6">
                        @csrf
                        <button class="px-8 py-3 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-sm">
                            Pay ${{ number_format($invoice->balanceDue(), 2) }} now
                        </button>
                        <div class="text-xs text-slate-500 mt-2">Card payments coming soon — for now your supplier will be notified.</div>
                    </form>
                @elseif ($invoice->isPaid())
                    <div class="mt-6 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium">
                        ✓ Paid {{ $invoice->paid_at?->format('M j, Y') }}
                    </div>
                @endif
            </div>

            {{-- Invoice line items --}}
            <div class="border-t border-slate-200 p-6 bg-slate-50">
                <div class="flex justify-between text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">
                    <span>Invoice {{ $invoice->invoice_number }}</span>
                    <span>Issued {{ $invoice->issue_date->format('M j, Y') }}</span>
                </div>
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="text-left pb-2">Description</th>
                            <th class="text-right pb-2 w-16">Qty</th>
                            <th class="text-right pb-2 w-24">Price</th>
                            <th class="text-right pb-2 w-24">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->items as $item)
                            <tr class="border-b border-slate-100">
                                <td class="py-2">{{ $item->description }}</td>
                                <td class="py-2 text-right">{{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }}</td>
                                <td class="py-2 text-right font-mono">${{ number_format($item->unit_price, 2) }}</td>
                                <td class="py-2 text-right font-mono">${{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="text-sm">
                        <tr>
                            <td colspan="3" class="text-right py-1 text-slate-600">Subtotal</td>
                            <td class="text-right py-1 font-mono">${{ number_format($invoice->subtotal, 2) }}</td>
                        </tr>
                        @if ((float) $invoice->tax_total > 0)
                            <tr>
                                <td colspan="3" class="text-right py-1 text-slate-600">Tax</td>
                                <td class="text-right py-1 font-mono">${{ number_format($invoice->tax_total, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="border-t border-slate-200">
                            <td colspan="3" class="text-right py-2 font-semibold">Total</td>
                            <td class="text-right py-2 font-bold font-mono">${{ number_format($invoice->total, 2) }}</td>
                        </tr>
                        @if ((float) $invoice->amount_paid > 0)
                            <tr>
                                <td colspan="3" class="text-right py-1 text-slate-600">Already paid</td>
                                <td class="text-right py-1 font-mono text-emerald-700">−${{ number_format($invoice->amount_paid, 2) }}</td>
                            </tr>
                            <tr class="border-t border-slate-200">
                                <td colspan="3" class="text-right py-2 font-semibold">Balance</td>
                                <td class="text-right py-2 font-bold font-mono">${{ number_format($invoice->balanceDue(), 2) }}</td>
                            </tr>
                        @endif
                    </tfoot>
                </table>
            </div>

            @if ($invoice->notes)
                <div class="border-t border-slate-200 p-6">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Note from {{ $tenant->name }}</div>
                    <p class="text-sm text-slate-700">{{ $invoice->notes }}</p>
                </div>
            @endif
        </div>

        <div class="text-center text-xs text-slate-500 pt-2">
            Questions? Reply to the invoice email to reach {{ $tenant->name }}.
        </div>
    </main>
</body>
</html>
