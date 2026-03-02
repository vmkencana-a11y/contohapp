<?php

namespace App\Models\Logs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin;

class AdminActivityLog extends Model
{
    use HasFactory;

    protected $table = 'admin_activity_logs';
    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'action',
        'subject_type',
        'subject_id',
        'reason',
        'metadata',
        'ip_address',
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

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
