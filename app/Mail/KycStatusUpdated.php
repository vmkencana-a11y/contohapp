<?php

namespace App\Mail;

use App\Models\UserKyc;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KycStatusUpdated extends Mailable implements ShouldQueue
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
        $statusText = $this->kyc->isVerified() ? 'Diterima' : 'Ditolak';
        
        return new Envelope(
            subject: "Update Status Verifikasi Identitas (KYC) - {$statusText}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = $this->kyc->isVerified() ? 'emails.kyc.approved' : 'emails.kyc.rejected';

        return new Content(
            view: $view,
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
