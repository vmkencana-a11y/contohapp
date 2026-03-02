<?php

namespace App\Models\Logs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserLoginLog extends Model
{
    use HasFactory;

    protected $table = 'user_login_logs';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'metadata',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
