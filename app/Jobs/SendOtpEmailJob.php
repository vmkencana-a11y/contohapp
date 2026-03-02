<?php

namespace App\Jobs;

use App\Mail\AdminOtpMail;
use App\Mail\OtpMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOtpEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $email;
    public string $otp;
    public ?string $name;
    public bool $isAdmin;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 10;

    public function __construct(string $email, string $otp, ?string $name = null, bool $isAdmin = false)
    {
        $this->email = $email;
        $this->otp = $otp;
        $this->name = $name;
        $this->isAdmin = $isAdmin;
    }

    public function handle(): void
    {
        if ($this->isAdmin) {
            Mail::to($this->email)->send(new AdminOtpMail($this->otp, $this->name ?? 'Admin'));
        } else {
            Mail::to($this->email)->send(new OtpMail($this->otp, $this->name));
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Log the failure for monitoring
        \Log::error('Failed to send OTP email', [
            'email_hash' => hash('sha256', $this->email),
            'is_admin' => $this->isAdmin,
            'error' => $exception->getMessage(),
        ]);
    }
}
