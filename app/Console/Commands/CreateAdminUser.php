<?php

namespace App\Console\Commands;

use App\Actions\Admin\CreateAdminAction;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Console\Command;
use Throwable;

class CreateAdminUser extends Command
{
    protected $signature = 'sekuota:create-admin 
                            {name : Admin name}
                            {email : Admin email}
                            {--password= : Admin password (optional, will prompt if not provided)}
                            {--role=super_admin : Role to assign (default: super_admin)}';
                            
    protected $description = 'Create a new admin user';

    public function handle(): int
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $roleName = (string) $this->option('role');
        
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            $availableRoles = Role::query()->pluck('name')->implode(', ');
            $this->error("Role '{$roleName}' not found. Available roles: {$availableRoles}");
            return self::FAILURE;
        }

        // If admin already exists, use this command to ensure role assignment.
        $existingAdmin = Admin::findByEmail($email);
        if ($existingAdmin) {
            $existingAdmin->roles()->syncWithoutDetaching([
                $role->id => ['assigned_at' => now()],
            ]);

            $this->info("Admin with email {$email} already exists. Role '{$roleName}' ensured.");
            return self::SUCCESS;
        }
        
        // Get password
        $password = $this->option('password');
        if (!$password) {
            $password = $this->secret('Enter password for the admin');
        }
        
        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters!');
            return self::FAILURE;
        }
        
        try {
            $admin = app(CreateAdminAction::class)->execute(
                $name,
                $email,
                $password,
                $roleName
            );
        } catch (Throwable $e) {
            $this->error('Failed to create admin: ' . $e->getMessage());
            return self::FAILURE;
        }
        
        $this->info("Admin created successfully!");
        $this->table(['Field', 'Value'], [
            ['Name', $admin->name],
            ['Email', $admin->email],
            ['ID', $admin->id],
            ['Role', $roleName],
        ]);
        
        return self::SUCCESS;
    }
}
