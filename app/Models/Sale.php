<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Sale extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['customer_id', 'sale_date', 'status', 'total_revenue', 'total_profit'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(function (string $e) {
                $label = '$' . number_format((float) $this->total_revenue, 2);
                return match ($e) {
                    'created' => "recorded sale #{$this->id} for {$label}",
                    'updated' => "updated sale #{$this->id}",
                    'deleted' => "deleted sale #{$this->id}",
                    default   => "{$e} sale #{$this->id}",
                };
            });
    }

    protected $fillable = [
        'customer_id', 'created_by', 'sale_date', 'status',
        'total_revenue', 'total_cost', 'total_profit', 'notes',
    ];

    protected $casts = [
        'sale_date'     => 'date',
        'total_revenue' => 'decimal:2',
        'total_cost'    => 'decimal:2',
        'total_profit'  => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recomputeTotals(): void
    {
        $this->total_revenue = (float) $this->items()->sum('line_total');
        $this->total_cost    = (float) $this->items()->sum(\DB::raw('quantity * unit_cost'));
        $this->total_profit  = (float) $this->items()->sum('line_profit');
        $this->save();
    }

    public function profitMargin(): float
    {
        if ((float) $this->total_revenue === 0.0) {
            return 0.0;
        }

        return (float) ($this->total_profit / $this->total_revenue * 100);
    }
}
