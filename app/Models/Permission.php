<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    /**
     * Disable timestamps, we use custom created_at only.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'module',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (Permission $permission) {
            $permission->created_at = $permission->created_at ?? now();
        });
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get roles with this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
