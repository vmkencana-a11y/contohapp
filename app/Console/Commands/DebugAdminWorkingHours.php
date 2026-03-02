<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugAdminWorkingHours extends Command
{
    protected $signature = 'admin:debug-hours {email}';
    protected $description = 'Debug admin working hours configuration';

    public function handle(): int
    {
        $email = $this->argument('email');
        
        // First, let's check database directly
        $this->info("Looking up admin with email: {$email}");
        
        $emailHash = hash('sha256', strtolower($email));
        $this->info("Email hash: {$emailHash}");
        
        // Raw database query
        $dbAdmin = DB::table('admins')->where('email_hash', $emailHash)->first();
        
        if (!$dbAdmin) {
            $this->error("Admin not found in database!");
            
            // List all admins
            $this->info("\nAll admins in database:");
            $allAdmins = DB::table('admins')->select('id', 'email_hash', 'working_days', 'work_start_time', 'work_end_time')->get();
            foreach ($allAdmins as $a) {
                $this->line("ID: {$a->id}, Hash: {$a->email_hash}, Days: {$a->working_days}, Start: {$a->work_start_time}, End: {$a->work_end_time}");
            }
            return Command::FAILURE;
        }

        $this->info("\n=== Raw Database Values ===");
        $this->line("ID: {$dbAdmin->id}");
        $this->line("working_days (raw): " . var_export($dbAdmin->working_days, true));
        $this->line("work_start_time (raw): " . var_export($dbAdmin->work_start_time, true));
        $this->line("work_end_time (raw): " . var_export($dbAdmin->work_end_time, true));

        // Now via Eloquent
        $admin = Admin::findByEmail($email);
        
        if (!$admin) {
            $this->error("Admin not found via Eloquent findByEmail!");
            return Command::FAILURE;
        }

        $this->info("\n=== Eloquent Model Values ===");
        $this->line("working_days: " . json_encode($admin->working_days));
        $this->line("work_start_time: " . var_export($admin->work_start_time, true));
        $this->line("work_end_time: " . var_export($admin->work_end_time, true));

        $this->info("\n=== Time Comparison ===");
        $now = now();
        $currentDay = (int) $now->format('N');
        $currentTime = $now->format('H:i');
        
        $this->line("Current day (N format): {$currentDay}");
        $this->line("Current time (H:i): {$currentTime}");
        
        if (!empty($admin->working_days)) {
            $workingDays = array_map('intval', $admin->working_days);
            $this->line("Working days (as int array): " . json_encode($workingDays));
            $this->line("Today in working_days? " . (in_array($currentDay, $workingDays, true) ? "YES" : "NO"));
        }
        
        if ($admin->work_start_time && $admin->work_end_time) {
            $startTime = substr((string) $admin->work_start_time, 0, 5);
            $endTime = substr((string) $admin->work_end_time, 0, 5);
            
            $this->line("Start time (normalized): {$startTime}");
            $this->line("End time (normalized): {$endTime}");
            $this->line("current >= start: " . ($currentTime >= $startTime ? "TRUE" : "FALSE"));
            $this->line("current <= end: " . ($currentTime <= $endTime ? "TRUE" : "FALSE"));
        }

        $this->info("\n=== Final Result ===");
        $result = $admin->isWithinWorkingHours();
        $this->line("isWithinWorkingHours(): " . ($result ? "TRUE (access allowed)" : "FALSE (access blocked)"));

        return Command::SUCCESS;
    }
}
