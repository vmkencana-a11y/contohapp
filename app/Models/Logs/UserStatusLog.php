<?php

namespace App\Models\Logs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Admin;

class UserStatusLog extends Model
{
    use HasFactory;

    protected $table = 'user_status_logs';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'changed_by_type',
        'changed_by_id',
        'old_status',
        'new_status',
        'reason',
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

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'changed_by_id');
    }
}
