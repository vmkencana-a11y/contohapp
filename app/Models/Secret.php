<?php

namespace App\Models;

use App\Services\SecretManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Secret extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service',
        'secret_key',
        'encrypted_value',
        'iv',
        'is_active',
        'updated_by',
    ];

    protected $hidden = [
        'encrypted_value',
        'iv',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        $flushCache = function (self $secret): void {
            SecretManager::forgetCacheFor($secret->service);
        };

        static::saved($flushCache);
        static::deleted($flushCache);
    }

    public function getMaskedValueAttribute(): string
    {
        return str_repeat('*', 12);
    }
}
