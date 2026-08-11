<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportedTransaction extends Model
{
    protected $fillable = [
        'integration_connection_id',
        'provider',
        'external_id',
        'kind',
        'amount',
        'transaction_date',
        'name',
        'merchant_name',
        'raw_payload',
        'match_type',
        'matched_sale_id',
        'matched_expense_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'raw_payload' => 'array',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(IntegrationConnection::class, 'integration_connection_id');
    }
}
