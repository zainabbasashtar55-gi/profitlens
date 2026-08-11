<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'sku', 'cost_price', 'sell_price', 'active'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $e) => match ($e) {
                'created' => "added product {$this->name}",
                'updated' => "updated product {$this->name}",
                'deleted' => "deleted product {$this->name}",
                default   => "{$e} product {$this->name}",
            });
    }

    protected $fillable = ['name', 'sku', 'description', 'cost_price', 'sell_price', 'active'];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'active'     => 'boolean',
    ];

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function margin(): float
    {
        if ((float) $this->sell_price === 0.0) {
            return 0.0;
        }

        return (float) (($this->sell_price - $this->cost_price) / $this->sell_price * 100);
    }
}
