<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class IntegrationConnection extends Model
{
    protected $fillable = [
        'provider',
        'name',
        'external_id',
        'access_token',
        'settings',
        'last_synced_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(ImportedTransaction::class);
    }

    public function setPlainAccessToken(?string $token): void
    {
        $this->access_token = $token ? Crypt::encryptString($token) : null;
    }
}
