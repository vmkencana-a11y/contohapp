<?php

namespace App\Actions\Admin;

use App\Models\Admin;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Create Admin Action
 * 
 * Creates a new admin user with optional role assignment.
 */
class CreateAdminAction
{
    /**
     * Execute the action.
     */
    public function execute(
        string $name,
        string $email,
        string $password,
        ?string $roleName = null
    ): Admin {
        return DB::transaction(function () use ($name, $email, $password, $roleName): Admin {
            $admin = Admin::create([
                'name' => $name,
                'email' => $email,
                'password' => $password, // Model mutator handles hashing
                'status' => 'active',
            ]);

            // Assign role if specified, fail-fast if role invalid
            if ($roleName) {
                $role = Role::where('name', $roleName)->first();
                if (!$role) {
                    throw new RuntimeException("Role '{$roleName}' not found.");
                }

                $admin->roles()->syncWithoutDetaching([
                    $role->id => ['assigned_at' => now()],
                ]);

                $hasRole = $admin->roles()->where('roles.id', $role->id)->exists();
                if (!$hasRole) {
                    throw new RuntimeException("Failed to attach role '{$roleName}' to admin.");
                }
            }

            return $admin;
        });
    }
}
