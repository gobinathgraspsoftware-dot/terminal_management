<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * PasswordResetMail - Send password reset link with branded template.
 *
 * Follows the same Mailable pattern as PurchaseOrderEmail & GrnPostedEmail.
 * Uses blade view at: components.emails.password-reset
 */
class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $resetUrl;
    public int $expirationMinutes;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $resetUrl, int $expirationMinutes = 60)
    {
        $this->user = $user;
        $this->resetUrl = $resetUrl;
        $this->expirationMinutes = $expirationMinutes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your Password - ' . config('app.name', 'TMS'),
        );
    }

    /**
     * Get the message content definition.
     *
     * IMPORTANT: Using 'customMessage' NOT 'message' because 'message' is reserved by Laravel!
     */
    public function content(): Content
    {
        return new Content(
            view: 'components.emails.password-reset',
            with: [
                'user'              => $this->user,
                'resetUrl'          => $this->resetUrl,
                'expirationMinutes' => $this->expirationMinutes,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
