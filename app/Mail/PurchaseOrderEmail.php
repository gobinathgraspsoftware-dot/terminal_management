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

    public function __construct(PurchaseOrder $purchaseOrder, $pdfContent, $customMessage = null)
    {
        $this->purchaseOrder = $purchaseOrder;
        $this->pdfContent = $pdfContent;
        $this->customMessage = $customMessage;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Purchase Order: ' . $this->purchaseOrder->po_no,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order',
            with: [
                'po' => $this->purchaseOrder,
                'message' => $this->customMessage,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, 'PO-' . $this->purchaseOrder->po_no . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
