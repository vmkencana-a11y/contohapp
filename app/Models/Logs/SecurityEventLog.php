<?php

namespace App\Models\Logs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityEventLog extends Model
{
    use HasFactory;

    protected $table = 'security_event_logs';
    public $timestamps = false;

    protected $fillable = [
        'event_type',
        'actor_identifier',
        'ip_address',
        'metadata',
        'severity',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($log) => $log->created_at = $log->created_at ?? now());
    }
}
