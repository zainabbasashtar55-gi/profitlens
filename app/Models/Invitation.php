<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invitation extends Model
{
    protected $fillable = [
        'email',
        'role',
        'token',
        'invited_by',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'accepted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Invitation $invitation) {
            $invitation->token ??= Str::random(48);
            $invitation->expires_at ??= now()->addDays(7);
        });
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function acceptUrl(): string
    {
        $domain = tenant()?->domains()->first()?->domain ?? request()->getHost();
        $scheme = request()->getScheme() ?: 'http';
        $port   = request()->getPort();
        $port   = $port && ! in_array($port, [80, 443], true) ? ':' . $port : '';

        return "{$scheme}://{$domain}{$port}/invitations/{$this->token}";
    }
}
