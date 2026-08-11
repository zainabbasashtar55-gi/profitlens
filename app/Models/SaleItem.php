<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id', 'product_id', 'product_name',
        'quantity', 'unit_price', 'unit_cost',
        'line_total', 'line_profit',
    ];

    protected $casts = [
        'quantity'    => 'integer',
        'unit_price'  => 'decimal:2',
        'unit_cost'   => 'decimal:2',
        'line_total'  => 'decimal:2',
        'line_profit' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (SaleItem $item) {
            $item->line_total  = $item->quantity * $item->unit_price;
            $item->line_profit = $item->quantity * ($item->unit_price - $item->unit_cost);
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
