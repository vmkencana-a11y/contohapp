<?php

namespace App\Services;

use App\Jobs\SendOtpEmailJob;
use App\Mail\AdminOtpMail;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

/**
 * Notification Service
 * 
 * Centralized notification handling with queue support.
 */
class NotificationService
{
    /**
     * Send OTP to user via email (queued).
     */
    public function sendOtp(string $email, string $otp, ?string $name = null): void
    {
        if (config('queue.default') !== 'sync') {
            dispatch(new SendOtpEmailJob($email, $otp, $name));
        } else {
            Mail::to($email)->send(new OtpMail($otp, $name));
        }
    }

    /**
     * Send OTP to admin via email (queued).
     */
    public function sendAdminOtp(string $email, string $otp, string $name): void
    {
        if (config('queue.default') !== 'sync') {
            dispatch(new SendOtpEmailJob($email, $otp, $name, true));
        } else {
            Mail::to($email)->send(new AdminOtpMail($otp, $name));
        }
    }

    /**
     * Send generic notification email.
     */
    public function sendEmail(string $email, $mailable): void
    {
        Mail::to($email)->send($mailable);
    }

    /**
     * Queue generic notification email.
     */
    public function queueEmail(string $email, $mailable): void
    {
        Mail::to($email)->queue($mailable);
    }
}
