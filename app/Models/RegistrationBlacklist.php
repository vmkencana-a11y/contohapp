<?php

namespace App\Models;

use App\Enums\BlacklistTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationBlacklist extends Model
{
    protected $table = 'registration_blacklists';

    protected $fillable = [
        'type',
        'value',
        'reason',
        'created_by',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => BlacklistTypeEnum::class,
            'expires_at' => 'datetime',
        ];
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get the admin who created this entry.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * Scope to active (not expired) entries.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope by type.
     */
    public function scopeByType(Builder $query, BlacklistTypeEnum|string $type): Builder
    {
        $value = $type instanceof BlacklistTypeEnum ? $type->value : $type;
        return $query->where('type', $value);
    }

    /**
     * Scope by value (case-insensitive).
     */
    public function scopeByValue(Builder $query, string $value): Builder
    {
        return $query->whereRaw('LOWER(value) = ?', [strtolower($value)]);
    }

    // ==========================================
    // Helpers
    // ==========================================

    /**
     * Check if this entry is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if this entry is active.
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }
}
