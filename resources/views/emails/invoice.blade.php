<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Inter, sans-serif; background: #f8fafc; margin: 0; padding: 24px; color: #0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0;">
        <tr>
            <td style="padding: 28px 32px 8px;">
                <p style="margin: 0; font-size: 13px; color: #64748b;">{{ $tenantName }}</p>
                <h1 style="margin: 4px 0 0; font-size: 22px;">Invoice {{ $invoice->invoice_number }}</h1>
            </td>
        </tr>

        <tr>
            <td style="padding: 16px 32px 8px; font-size: 14px; line-height: 1.55;">
                <p style="margin: 0 0 12px;">Hi {{ $invoice->customer->name }},</p>
                <p style="margin: 0 0 12px;">
                    Here is invoice <strong>{{ $invoice->invoice_number }}</strong> for
                    <strong>${{ number_format((float) $invoice->total, 2) }}</strong>, due by
                    <strong>{{ $invoice->due_date->format('M j, Y') }}</strong>.
                </p>
            </td>
        </tr>

        <tr>
            <td style="padding: 0 32px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #f1f5f9;">
                            <th align="left"  style="padding: 8px 10px; color: #64748b; font-weight: 600;">Item</th>
                            <th align="right" style="padding: 8px 10px; color: #64748b; font-weight: 600;">Qty</th>
                            <th align="right" style="padding: 8px 10px; color: #64748b; font-weight: 600;">Price</th>
                            <th align="right" style="padding: 8px 10px; color: #64748b; font-weight: 600;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->items as $item)
                            <tr>
                                <td style="padding: 10px; border-top: 1px solid #e2e8f0;">{{ $item->description }}</td>
                                <td align="right" style="padding: 10px; border-top: 1px solid #e2e8f0;">{{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }}</td>
                                <td align="right" style="padding: 10px; border-top: 1px solid #e2e8f0;">${{ number_format((float) $item->unit_price, 2) }}</td>
                                <td align="right" style="padding: 10px; border-top: 1px solid #e2e8f0;">${{ number_format((float) $item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" align="right" style="padding: 10px; color: #64748b;">Subtotal</td>
                            <td align="right" style="padding: 10px;">${{ number_format((float) $invoice->subtotal, 2) }}</td>
                        </tr>
                        @if ((float) $invoice->tax_total > 0)
                        <tr>
                            <td colspan="3" align="right" style="padding: 4px 10px; color: #64748b;">Tax</td>
                            <td align="right" style="padding: 4px 10px;">${{ number_format((float) $invoice->tax_total, 2) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td colspan="3" align="right" style="padding: 10px; font-weight: 700; border-top: 1px solid #e2e8f0;">Total due</td>
                            <td align="right" style="padding: 10px; font-weight: 700; border-top: 1px solid #e2e8f0; font-size: 16px;">${{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}</td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>

        <tr>
            <td align="center" style="padding: 24px 32px 28px;">
                <a href="{{ $publicUrl }}"
                   style="display: inline-block; background: #4f46e5; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px;">
                    View &amp; pay invoice →
                </a>
                <p style="margin: 14px 0 0; font-size: 12px; color: #64748b;">
                    Or copy this link: <br><a href="{{ $publicUrl }}" style="color: #4f46e5; word-break: break-all;">{{ $publicUrl }}</a>
                </p>
            </td>
        </tr>

        @if ($invoice->notes)
            <tr>
                <td style="padding: 0 32px 24px;">
                    <div style="background: #fef9c3; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 14px; font-size: 13px; color: #713f12;">
                        <strong>Note from {{ $tenantName }}:</strong><br>
                        {{ $invoice->notes }}
                    </div>
                </td>
            </tr>
        @endif

        <tr>
            <td style="padding: 16px 32px 24px; font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9;">
                Sent via {{ $tenantName }} on ProfitLens.
            </td>
        </tr>
    </table>
</body>
</html>
