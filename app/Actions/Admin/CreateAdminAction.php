<?php

namespace App\Actions\Admin;

use App\Models\Admin;
use App\Models\Role;

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
        $admin = Admin::create([
            'name' => $name,
            'email' => $email,
            'password' => $password, // Model mutator handles hashing
            'status' => 'active',
        ]);

        // Assign role if specified
        if ($roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $admin->roles()->attach($role->id, ['assigned_at' => now()]);
            }
        }

        return $admin;
    }
}
