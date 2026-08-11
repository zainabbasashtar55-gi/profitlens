<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUS_DRAFT  = 'draft';
    public const STATUS_SENT   = 'sent';
    public const STATUS_VIEWED = 'viewed';
    public const STATUS_PAID   = 'paid';
    public const STATUS_VOID   = 'void';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total', 'amount_paid', 'due_date'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(function (string $e) {
                $label = "{$this->invoice_number} ($" . number_format((float) $this->total, 2) . ')';
                return match ($e) {
                    'created' => "created invoice {$label}",
                    'updated' => "updated invoice {$label}",
                    'deleted' => "deleted invoice {$label}",
                    default   => "{$e} invoice {$label}",
                };
            });
    }

    protected $fillable = [
        'invoice_number', 'public_token', 'customer_id', 'sale_id', 'created_by',
        'status', 'issue_date', 'due_date', 'sent_at', 'viewed_at', 'paid_at',
        'subtotal', 'tax_total', 'total', 'amount_paid',
        'currency', 'notes', 'payment_terms', 'pdf_path',
    ];

    protected $casts = [
        'issue_date'  => 'date',
        'due_date'    => 'date',
        'sent_at'     => 'datetime',
        'viewed_at'   => 'datetime',
        'paid_at'     => 'datetime',
        'subtotal'    => 'decimal:2',
        'tax_total'   => 'decimal:2',
        'total'       => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    // Newly created invoices get an INV-YYYY-NNNN number and a magic-link token.
    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            if (! $invoice->invoice_number) {
                $invoice->invoice_number = self::nextNumber();
            }
            if (! $invoice->public_token) {
                $invoice->public_token = Str::random(48);
            }
            if (! $invoice->status) {
                $invoice->status = self::STATUS_DRAFT;
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    // ── Computed properties ────────────────────────────────────────────────

    public function balanceDue(): float
    {
        return round((float) $this->total - (float) $this->amount_paid, 2);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID || $this->balanceDue() <= 0;
    }

    public function isOverdue(): bool
    {
        return ! $this->isPaid()
            && ! in_array($this->status, [self::STATUS_DRAFT, self::STATUS_VOID], true)
            && $this->due_date->isPast();
    }

    public function daysOverdue(): int
    {
        return $this->isOverdue() ? (int) $this->due_date->diffInDays(now()) : 0;
    }

    public function statusLabel(): string
    {
        if ($this->isOverdue()) return 'overdue';
        return $this->status;
    }

    // ── Mutations ──────────────────────────────────────────────────────────

    public function recomputeTotals(): void
    {
        $items = $this->items()->get();

        $subtotal  = (float) $items->sum('line_total');
        $taxTotal  = (float) $items->sum(fn ($i) => round($i->line_total * ($i->tax_rate / 100), 2));
        $total     = round($subtotal + $taxTotal, 2);

        $this->subtotal  = $subtotal;
        $this->tax_total = $taxTotal;
        $this->total     = $total;
        $this->save();
    }

    public function markAsSent(): void
    {
        $this->update([
            'status'  => self::STATUS_SENT,
            'sent_at' => $this->sent_at ?? now(),
        ]);
    }

    public function markAsViewed(): void
    {
        if (! $this->viewed_at) {
            $this->viewed_at = now();
        }
        // Only advance sent → viewed; never regress from paid/void.
        if ($this->status === self::STATUS_SENT) {
            $this->status = self::STATUS_VIEWED;
        }
        $this->save();
    }

    /**
     * Record a payment. Pass null to settle the full remaining balance.
     * When fully paid, mints a Sale so the invoice flows into the P&L.
     */
    public function recordPayment(?float $amount = null, ?int $actorId = null): void
    {
        $amount = $amount === null ? $this->balanceDue() : round($amount, 2);

        DB::transaction(function () use ($amount, $actorId) {
            $this->amount_paid = round((float) $this->amount_paid + $amount, 2);
            if ($this->amount_paid >= (float) $this->total) {
                $this->status = self::STATUS_PAID;
                $this->paid_at = now();

                // Mirror into Sale so KPIs / P&L pick it up.
                if (! $this->sale_id) {
                    $sale = Sale::create([
                        'customer_id' => $this->customer_id,
                        'created_by'  => $actorId ?? $this->created_by,
                        'sale_date'   => now()->toDateString(),
                        'status'      => 'paid',
                        'notes'       => "From invoice {$this->invoice_number}",
                    ]);
                    foreach ($this->items as $item) {
                        $sale->items()->create([
                            'product_id'   => $item->product_id,
                            'product_name' => $item->description,
                            'quantity'     => (int) max(1, round($item->quantity)),
                            'unit_price'   => $item->unit_price,
                            'unit_cost'    => $item->unit_cost,
                        ]);
                    }
                    $sale->recomputeTotals();
                    $this->sale_id = $sale->id;
                }
            }
            $this->save();
        });
    }

    public function voidInvoice(): void
    {
        $this->update(['status' => self::STATUS_VOID]);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeOpen($q)
    {
        return $q->whereNotIn('status', [self::STATUS_PAID, self::STATUS_VOID]);
    }

    public function scopeOverdue($q)
    {
        return $q->open()->whereDate('due_date', '<', now()->toDateString());
    }

    /**
     * Generate the next sequential number, e.g. INV-2026-0001. Wrapped in a
     * transaction with row-lock so concurrent invoice creations don't collide.
     */
    public static function nextNumber(): string
    {
        $year = now()->year;
        $prefix = "INV-{$year}-";

        return DB::transaction(function () use ($prefix) {
            $last = static::query()
                ->where('invoice_number', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('invoice_number');

            $n = $last
                ? ((int) substr($last, strlen($prefix))) + 1
                : 1;

            return $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
        });
    }
}
