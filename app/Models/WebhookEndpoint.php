<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WebhookEndpoint extends Model
{
    public const EVENTS = [
        'sale.recorded',
        'sale.big',
        'expense.logged',
    ];

    protected $fillable = [
        'name',
        'url',
        'events',
        'secret',
        'active',
    ];

    protected $casts = [
        'events' => 'array',
        'active' => 'boolean',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public static function makeSecret(): string
    {
        return 'whsec_' . Str::random(48);
    }
}
