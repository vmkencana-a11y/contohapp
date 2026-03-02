<?php

namespace App\Mail;

use App\Models\UserKyc;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewKycSubmission extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public UserKyc $kyc;

    /**
     * Create a new message instance.
     */
    public function __construct(UserKyc $kyc)
    {
        $this->kyc = $kyc;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Pengajuan Verifikasi Identitas (KYC) Baru - {$this->kyc->user->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.new_kyc_submission',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
