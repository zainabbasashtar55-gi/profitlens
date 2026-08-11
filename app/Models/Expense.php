<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Expense extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['description', 'vendor', 'amount', 'expense_date', 'expense_category_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(function (string $e) {
                $label = '$' . number_format((float) $this->amount, 2) . ' — ' . $this->description;
                return match ($e) {
                    'created' => "logged expense {$label}",
                    'updated' => "updated expense {$this->description}",
                    'deleted' => "deleted expense {$this->description}",
                    default   => "{$e} expense {$this->description}",
                };
            });
    }

    protected $fillable = [
        'expense_category_id', 'created_by', 'vendor', 'description',
        'amount', 'expense_date', 'receipt_path', 'receipt_original_name',
        'recurring', 'recurring_period',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'expense_date' => 'date',
        'recurring'    => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiptUrl(): ?string
    {
        return $this->receipt_path
            ? Storage::disk(config('filesystems.receipts_disk', 'public'))->url($this->receipt_path)
            : null;
    }
}
