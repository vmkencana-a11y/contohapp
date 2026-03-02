<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class InitialAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $this->createPermissions();
        
        // Create roles
        $superAdminRole = $this->createRoles();
        
        // Create initial super admin
        $this->createSuperAdmin($superAdminRole);
    }

    /**
     * Create all permissions.
     */
    private function createPermissions(): void
    {
        $permissions = [
            // User Management
            ['name' => 'users.view', 'description' => 'View user list and details', 'module' => 'users'],
            ['name' => 'users.manage', 'description' => 'Manage users (suspend/ban/reactivate)', 'module' => 'users'],
            ['name' => 'users.create', 'description' => 'Create new users', 'module' => 'users'],
            ['name' => 'users.edit', 'description' => 'Edit user information', 'module' => 'users'],
            ['name' => 'users.suspend', 'description' => 'Suspend users', 'module' => 'users'],
            ['name' => 'users.ban', 'description' => 'Ban users permanently', 'module' => 'users'],
            ['name' => 'users.reactivate', 'description' => 'Reactivate suspended users', 'module' => 'users'],
            
            // KYC Management
            ['name' => 'kyc.view', 'description' => 'View KYC submissions', 'module' => 'kyc'],
            ['name' => 'kyc.manage', 'description' => 'Approve or reject KYC submissions', 'module' => 'kyc'],
            ['name' => 'kyc.review', 'description' => 'Review and approve/reject KYC', 'module' => 'kyc'],
            
            // Admin Management
            ['name' => 'admins.view', 'description' => 'View admin list', 'module' => 'admins'],
            ['name' => 'admins.manage', 'description' => 'Manage admins (create/edit/delete/toggle)', 'module' => 'admins'],
            ['name' => 'admins.create', 'description' => 'Create new admins', 'module' => 'admins'],
            ['name' => 'admins.edit', 'description' => 'Edit admin information', 'module' => 'admins'],
            ['name' => 'admins.delete', 'description' => 'Delete admins', 'module' => 'admins'],
            
            // Role Management
            ['name' => 'roles.view', 'description' => 'View roles and permissions', 'module' => 'roles'],
            ['name' => 'roles.manage', 'description' => 'Create/edit/delete roles', 'module' => 'roles'],
            
            // Settings
            ['name' => 'settings.view', 'description' => 'View system settings', 'module' => 'settings'],
            ['name' => 'settings.manage', 'description' => 'Modify system settings', 'module' => 'settings'],
            ['name' => 'settings.edit', 'description' => 'Edit system settings', 'module' => 'settings'],
            
            // Logs & Monitoring
            ['name' => 'logs.view', 'description' => 'View activity logs', 'module' => 'logs'],
            ['name' => 'logs.security', 'description' => 'View security event logs', 'module' => 'logs'],
            
            // Referrals
            ['name' => 'referrals.view', 'description' => 'View referral data', 'module' => 'referrals'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                array_merge($permission, ['created_at' => now()])
            );
        }
    }

    /**
     * Create roles and assign permissions.
     */
    private function createRoles(): Role
    {
        // Super Admin - all permissions
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['description' => 'Super Administrator with full access', 'created_at' => now(), 'updated_at' => now()]
        );
        $superAdmin->permissions()->sync(Permission::all());

        // Admin Manager
        $adminManager = Role::firstOrCreate(
            ['name' => 'admin_manager'],
            ['description' => 'Can manage admins and roles', 'created_at' => now(), 'updated_at' => now()]
        );
        $adminManager->permissions()->sync(
            Permission::whereIn('module', ['admins', 'roles', 'logs'])->pluck('id')
        );

        // User Manager
        $userManager = Role::firstOrCreate(
            ['name' => 'user_manager'],
            ['description' => 'Can manage users and KYC', 'created_at' => now(), 'updated_at' => now()]
        );
        $userManager->permissions()->sync(
            Permission::whereIn('module', ['users', 'kyc', 'logs'])->pluck('id')
        );

        // Viewer
        $viewer = Role::firstOrCreate(
            ['name' => 'viewer'],
            ['description' => 'Read-only access', 'created_at' => now(), 'updated_at' => now()]
        );
        $viewer->permissions()->sync(
            Permission::where('name', 'like', '%.view')->pluck('id')
        );

        return $superAdmin;
    }

    /**
     * Create initial super admin.
     */
    private function createSuperAdmin(Role $role): void
    {
        $email = (string) env('ADMIN_DEFAULT_EMAIL', '');
        $password = (string) env('ADMIN_DEFAULT_PASSWORD', '');

        if (!$this->isValidSeedCredential($email) || !$this->isValidSeedCredential($password)) {
            if (app()->isProduction()) {
                $this->command?->error('Set valid ADMIN_DEFAULT_EMAIL and ADMIN_DEFAULT_PASSWORD in .env for production seeding.');
                return;
            }

            // Local/dev fallback only.
            $email = 'admin@sekuota.test';
            $password = 'password';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->command?->error('ADMIN_DEFAULT_EMAIL is not a valid email format.');
            return;
        }

        // IMPORTANT: email column is encrypted, so lookup must use deterministic email_hash.
        $emailHash = hash('sha256', strtolower($email));
        $admin = Admin::where('email_hash', $emailHash)->first();

        if (!$admin) {
            $admin = Admin::create([
                'name' => 'Super Admin',
                'email' => $email,
                'password' => $password,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $admin->roles()->syncWithoutDetaching([
            $role->id => ['assigned_at' => now()],
        ]);
    }

    /**
     * Validate seeded credentials to avoid placeholder values in production.
     */
    private function isValidSeedCredential(string $value): bool
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return false;
        }

        return !str_contains($trimmed, '[REQUIRED]');
    }
}
