<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;

class RefreshToken extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'token_hash',
        'user_agent',
        'ip_address',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($token) {
            $token->id ??= Str::uuid()->toString();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hash(string $token): self
    {
        $this->token_hash = Hash::make($token);
        $this->save();

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    public function scopeActive($query)
    {
        return $query
            ->unexpired()
            ->whereNull('revoked_at');
    }

    public function scopeUnexpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function matchesRawToken(string $rawToken): bool
    {
        return Hash::check($rawToken, $this->token_hash);
    }
}
