<?php

namespace App\Console\Commands;

use App\Actions\Admin\CreateAdminAction;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Console\Command;

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
        
        // Check if admin exists using deterministic email hash lookup.
        if (Admin::findByEmail($email)) {
            $this->error("Admin with email {$email} already exists!");
            return self::FAILURE;
        }

        $roleName = (string) $this->option('role');
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            $availableRoles = Role::query()->pluck('name')->implode(', ');
            $this->error("Role '{$roleName}' not found. Available roles: {$availableRoles}");
            return self::FAILURE;
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
        
        $admin = app(CreateAdminAction::class)->execute(
            $name,
            $email,
            $password,
            $roleName
        );
        
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
