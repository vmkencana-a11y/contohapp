<?php

namespace App\Actions\Admin;

use App\Models\Admin;
use App\Models\Role;
use App\Services\LoggingService;

/**
 * Assign Role Action
 * 
 * Assigns or removes roles from an admin.
 */
class AssignRoleAction
{
    public function __construct(private LoggingService $logger)
    {
    }

    /**
     * Assign role to admin.
     */
    public function assign(Admin $admin, Role $role, ?string $assignedByAdminId = null): void
    {
        if ($admin->roles()->where('role_id', $role->id)->exists()) {
            return; // Already has role
        }

        $admin->roles()->attach($role->id, ['assigned_at' => now()]);

        $this->logger->logAdminActivity(
            $assignedByAdminId ?? (string)$admin->id,
            'role.assigned',
            'Admin',
            (string)$admin->id,
            null,
            ['role' => $role->name]
        );
    }

    /**
     * Remove role from admin.
     */
    public function remove(Admin $admin, Role $role, ?string $removedByAdminId = null): void
    {
        $admin->roles()->detach($role->id);

        $this->logger->logAdminActivity(
            $removedByAdminId ?? (string)$admin->id,
            'role.removed',
            'Admin',
            (string)$admin->id,
            null,
            ['role' => $role->name]
        );
    }

    /**
     * Sync roles (replace all).
     */
    public function sync(Admin $admin, array $roleIds, ?string $syncedByAdminId = null): void
    {
        $pivotData = [];
        foreach ($roleIds as $roleId) {
            $pivotData[$roleId] = ['assigned_at' => now()];
        }

        $admin->roles()->sync($pivotData);

        $this->logger->logAdminActivity(
            $syncedByAdminId ?? (string)$admin->id,
            'roles.synced',
            'Admin',
            (string)$admin->id,
            null,
            ['role_count' => count($roleIds)]
        );
    }
}
