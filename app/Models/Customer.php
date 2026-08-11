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

class Customer extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'company', 'phone'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $e) => match ($e) {
                'created' => "added customer {$this->name}",
                'updated' => "updated customer {$this->name}",
                'deleted' => "deleted customer {$this->name}",
                default   => "{$e} customer {$this->name}",
            });
    }

    protected $fillable = ['name', 'email', 'company', 'phone', 'notes', 'created_by'];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lifetimeValue(): float
    {
        return (float) $this->sales()->sum('total_revenue');
    }
}
