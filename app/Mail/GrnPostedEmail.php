<?php

namespace App\Mail;

use App\Models\Grn;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class GrnPostedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $grn;
    public $pdfContent;
    public $customMessage;

    /**
     * Create a new message instance.
     *
     * IMPORTANT: Using 'customMessage' NOT 'message' because 'message' is reserved by Laravel!
     */
    public function __construct(Grn $grn, $pdfContent, $customMessage = null)
    {
        $this->grn = $grn;
        $this->pdfContent = $pdfContent;
        $this->customMessage = $customMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'GRN Posted: ' . $this->grn->grn_no,
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
            view: 'components.emails.grn-posted',
            with: [
                'grn' => $this->grn,
                'customMessage' => $this->customMessage,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, 'GRN-' . $this->grn->grn_no . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
