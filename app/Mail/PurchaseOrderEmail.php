<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $purchaseOrder;
    public $pdfContent;
    public $customMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(PurchaseOrder $purchaseOrder, $pdfContent, $customMessage = null)
    {
        $this->purchaseOrder = $purchaseOrder;
        $this->pdfContent = $pdfContent;
        $this->customMessage = $customMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Purchase Order: ' . $this->purchaseOrder->po_no,
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
            view: 'components.emails.purchase-order',
            with: [
                'po' => $this->purchaseOrder,
                'customMessage' => $this->customMessage,  // CHANGED from 'message' to 'customMessage'
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, 'PO-' . $this->purchaseOrder->po_no . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
