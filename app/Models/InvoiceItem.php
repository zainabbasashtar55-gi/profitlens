<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'product_id', 'description',
        'quantity', 'unit_price', 'unit_cost', 'tax_rate', 'line_total',
    ];

    protected $casts = [
        'quantity'   => 'decimal:3',
        'unit_price' => 'decimal:2',
        'unit_cost'  => 'decimal:2',
        'tax_rate'   => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Keep line_total in sync with qty * price on save — so callers can
        // omit it and we still store a denormalized total for fast aggregates.
        static::saving(function (InvoiceItem $item): void {
            $item->line_total = round((float) $item->quantity * (float) $item->unit_price, 2);
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function taxAmount(): float
    {
        return round((float) $this->line_total * ((float) $this->tax_rate / 100), 2);
    }
}
