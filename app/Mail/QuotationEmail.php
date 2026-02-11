<?php

namespace App\Mail;

use App\Models\Quotation;
use App\Services\QuotationPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

/**
 * QuotationEmail - Send quotation with PDF attachment
 */
class QuotationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public Quotation $quotation;
    public array $options;

    /**
     * Create a new message instance.
     */
    public function __construct(Quotation $quotation, array $options = [])
    {
        $this->quotation = $quotation;
        $this->options = $options;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->options['subject'] ??
                   'Quotation - ' . $this->quotation->quotation_no;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'components.emails.quotation',
            with: [
                'quotation' => $this->quotation,
                'customMessage' => $this->options['message'] ?? null,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $pdfService = new QuotationPdfService();

        // Generate PDF
        $pdf = $pdfService->generatePdf($this->quotation, $this->options);

        // Generate filename
        $quotationNo = str_replace(['/', '\\'], '-', $this->quotation->quotation_no);
        $type = ucfirst($this->quotation->quotation_type);
        $filename = "{$type}_Quotation_{$quotationNo}.pdf";

        return [
            Attachment::fromData(fn () => $pdf->output(), $filename)
                ->withMime('application/pdf'),
        ];
    }
}
