<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    /**
     * Disable timestamps, we use custom datetime fields.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (Role $role) {
            $role->created_at = $role->created_at ?? now();
            $role->updated_at = now();
        });

        static::updating(function (Role $role) {
            $role->updated_at = now();
        });
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get admins with this role.
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'admin_role')
            ->withPivot('assigned_at');
    }

    /**
     * Get permissions for this role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }
}
