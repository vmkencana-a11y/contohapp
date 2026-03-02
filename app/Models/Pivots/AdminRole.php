<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Custom pivot model for admin_role table.
 * Automatically sets assigned_at when creating.
 */
class AdminRole extends Pivot
{
    /**
     * The table name.
     */
    protected $table = 'admin_role';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (AdminRole $pivot) {
            $pivot->assigned_at = $pivot->assigned_at ?? now();
        });
    }
}
