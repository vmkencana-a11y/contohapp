<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;

class CreateAdminUser extends Command
{
    protected $signature = 'sekuota:create-admin 
                            {name : Admin name}
                            {email : Admin email}
                            {--password= : Admin password (optional, will prompt if not provided)}';
                            
    protected $description = 'Create a new admin user';

    public function handle(): int
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        
        // Check if admin exists
        if (Admin::where('email', $email)->exists()) {
            $this->error("Admin with email {$email} already exists!");
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
        
        // Create admin (password is hashed by model mutator)
        $admin = Admin::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
        
        $this->info("Admin created successfully!");
        $this->table(['Field', 'Value'], [
            ['Name', $admin->name],
            ['Email', $admin->email],
            ['ID', $admin->id],
        ]);
        
        return self::SUCCESS;
    }
}
