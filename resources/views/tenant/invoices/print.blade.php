<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Inter, sans-serif; color: #0f172a; margin: 0; padding: 32px 40px; background: #f8fafc; }
        .page { max-width: 760px; margin: 0 auto; background: #fff; border: 1px solid #e2e8f0; padding: 48px; }
        h1 { margin: 0; font-size: 28px; letter-spacing: -0.5px; }
        .muted { color: #64748b; font-size: 13px; }
        .label { color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 24px; }
        th { text-align: left; padding: 10px 8px; border-bottom: 2px solid #e2e8f0; color: #64748b; font-size: 11px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.04em; }
        td { padding: 10px 8px; border-bottom: 1px solid #f1f5f9; }
        .right { text-align: right; }
        .totals td { padding: 4px 8px; border: 0; }
        .totals .grand { font-size: 16px; font-weight: 700; border-top: 2px solid #0f172a; padding-top: 10px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }
        .b-paid { background: #d1fae5; color: #047857; }
        .b-sent { background: #dbeafe; color: #1e40af; }
        .b-overdue { background: #fee2e2; color: #b91c1c; }
        .b-draft { background: #fef3c7; color: #92400e; }
        .b-void { background: #f1f5f9; color: #475569; }
        .header-grid { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; }
        .from-to { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 36px; }
        .footer-note { margin-top: 32px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; line-height: 1.5; }
        @media print {
            body { background: #fff; padding: 0; }
            .page { border: 0; padding: 32px; box-shadow: none; }
            .print-hide { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header-grid">
            <div>
                <h1>Invoice</h1>
                <div class="muted">#{{ $invoice->invoice_number }}</div>
            </div>
            <div style="text-align: right;">
                <span class="badge b-{{ $invoice->statusLabel() === 'overdue' ? 'overdue' : ($invoice->statusLabel() === 'paid' ? 'paid' : ($invoice->statusLabel() === 'draft' ? 'draft' : ($invoice->statusLabel() === 'void' ? 'void' : 'sent'))) }}">{{ $invoice->statusLabel() }}</span>
                <div class="muted" style="margin-top: 12px;">Issued {{ $invoice->issue_date->format('M j, Y') }}</div>
                <div class="muted">Due {{ $invoice->due_date->format('M j, Y') }}</div>
            </div>
        </div>

        <div class="from-to">
            <div>
                <div class="label">From</div>
                <div style="font-weight: 600; margin-top: 4px;">{{ tenant('name') }}</div>
                <div class="muted">Issued by {{ $invoice->creator?->name ?? '—' }}</div>
            </div>
            <div>
                <div class="label">Bill to</div>
                <div style="font-weight: 600; margin-top: 4px;">{{ $invoice->customer->name }}</div>
                @if ($invoice->customer->company)<div class="muted">{{ $invoice->customer->company }}</div>@endif
                @if ($invoice->customer->email)<div class="muted">{{ $invoice->customer->email }}</div>@endif
                @if ($invoice->customer->phone)<div class="muted">{{ $invoice->customer->phone }}</div>@endif
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="right" style="width: 70px;">Qty</th>
                    <th class="right" style="width: 110px;">Price</th>
                    <th class="right" style="width: 70px;">Tax</th>
                    <th class="right" style="width: 110px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="right">{{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }}</td>
                        <td class="right">${{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="right">{{ (float) $item->tax_rate > 0 ? number_format($item->tax_rate, 2) . '%' : '—' }}</td>
                        <td class="right">${{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals" style="margin-top: 16px;">
            <tr>
                <td class="right muted" style="width: 80%;">Subtotal</td>
                <td class="right">${{ number_format((float) $invoice->subtotal, 2) }}</td>
            </tr>
            @if ((float) $invoice->tax_total > 0)
                <tr>
                    <td class="right muted">Tax</td>
                    <td class="right">${{ number_format((float) $invoice->tax_total, 2) }}</td>
                </tr>
            @endif
            <tr class="grand">
                <td class="right">Total</td>
                <td class="right">${{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}</td>
            </tr>
            @if ((float) $invoice->amount_paid > 0)
                <tr>
                    <td class="right muted">Paid</td>
                    <td class="right" style="color: #047857;">−${{ number_format((float) $invoice->amount_paid, 2) }}</td>
                </tr>
                <tr style="font-weight: 700; border-top: 1px solid #e2e8f0;">
                    <td class="right">Balance due</td>
                    <td class="right">${{ number_format($invoice->balanceDue(), 2) }}</td>
                </tr>
            @endif
        </table>

        @if ($invoice->notes || $invoice->payment_terms)
            <div class="footer-note">
                @if ($invoice->notes)<div><strong>Note:</strong> {{ $invoice->notes }}</div>@endif
                @if ($invoice->payment_terms)<div style="margin-top: 6px;"><strong>Payment terms:</strong> {{ $invoice->payment_terms }}</div>@endif
            </div>
        @endif

        <div class="print-hide" style="margin-top: 32px; text-align: center;">
            <button onclick="window.print()" style="padding: 10px 22px; background: #4f46e5; color: #fff; border: 0; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">Print / Save as PDF</button>
        </div>
    </div>
</body>
</html>
