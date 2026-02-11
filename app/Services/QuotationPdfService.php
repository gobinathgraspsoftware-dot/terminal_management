<?php

namespace App\Services;

use App\Models\Quotation;
use App\Models\SystemSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * QuotationPdfService - Handles PDF generation for quotations
 *
 * Features:
 * - Professional PDF layout with company branding
 * - Quotation details and line items
 * - Client/Vendor information
 * - Terms & conditions
 * - Download and email capabilities
 */
class QuotationPdfService
{
    /**
     * Generate quotation PDF
     *
     * @param Quotation $quotation
     * @param array $options
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generatePdf(Quotation $quotation, array $options = [])
    {
        // Load relationships
        $quotation->load([
            'lines.model.category',
            'lines.charge',
            'client',
            'vendor',
            'createdBy',
            'approvedBy'
        ]);

        // Get company settings
        $companySettings = $this->getCompanySettings();

        // Prepare data for view
        $data = [
            'quotation' => $quotation,
            'company' => $companySettings,
            'showPrices' => $options['show_prices'] ?? true,
            'showTax' => $options['show_tax'] ?? true,
            'watermark' => $options['watermark'] ?? null,
        ];

        // Generate PDF
        $pdf = Pdf::loadView('components.pdfs.quotation', $data);

        // Set paper size and orientation
        $pdf->setPaper($options['paper_size'] ?? 'A4', $options['orientation'] ?? 'portrait');

        // Optional settings
        if (isset($options['margins'])) {
            $pdf->setOption('margin-top', $options['margins']['top'] ?? 10);
            $pdf->setOption('margin-bottom', $options['margins']['bottom'] ?? 10);
            $pdf->setOption('margin-left', $options['margins']['left'] ?? 10);
            $pdf->setOption('margin-right', $options['margins']['right'] ?? 10);
        }

        return $pdf;
    }

    /**
     * Download quotation PDF
     *
     * @param Quotation $quotation
     * @param array $options
     * @return \Illuminate\Http\Response
     */
    public function downloadPdf(Quotation $quotation, array $options = [])
    {
        $pdf = $this->generatePdf($quotation, $options);

        $filename = $this->generateFilename($quotation);

        return $pdf->download($filename);
    }

    /**
     * Stream quotation PDF (for preview)
     *
     * @param Quotation $quotation
     * @param array $options
     * @return \Illuminate\Http\Response
     */
    public function streamPdf(Quotation $quotation, array $options = [])
    {
        $pdf = $this->generatePdf($quotation, $options);

        $filename = $this->generateFilename($quotation);

        return $pdf->stream($filename);
    }

    /**
     * Save PDF to storage
     *
     * @param Quotation $quotation
     * @param string $path
     * @param array $options
     * @return string
     */
    public function savePdf(Quotation $quotation, string $path = null, array $options = [])
    {
        $pdf = $this->generatePdf($quotation, $options);

        $filename = $this->generateFilename($quotation);
        $storagePath = $path ?? 'quotations/' . $quotation->id;
        $fullPath = $storagePath . '/' . $filename;

        // Save to storage
        Storage::put($fullPath, $pdf->output());

        return $fullPath;
    }

    /**
     * Generate filename for PDF
     *
     * @param Quotation $quotation
     * @return string
     */
    protected function generateFilename(Quotation $quotation): string
    {
        $quotationNo = str_replace(['/', '\\'], '-', $quotation->quotation_no);
        $type = ucfirst($quotation->quotation_type);

        return "{$type}_Quotation_{$quotationNo}.pdf";
    }

    /**
     * Get company settings
     *
     * @return array
     */
    protected function getCompanySettings(): array
    {
        return [
            'name' => SystemSetting::get('company_name', 'GRASP SOFTWARE SOLUTIONS'),
            'registration_no' => SystemSetting::get('company_registration_no', 'ROC: 123456789'),
            'tax_id' => SystemSetting::get('company_tax_id', 'Tax ID: MY12345678'),
            'address' => SystemSetting::get('company_address', 'Kuala Lumpur, Malaysia'),
            'phone' => SystemSetting::get('company_phone', '+60 3-1234 5678'),
            'email' => SystemSetting::get('company_email', 'info@grasp.com.my'),
            'website' => SystemSetting::get('company_website', 'www.grasp.com.my'),
            'logo_path' => SystemSetting::get('company_logo_path', null),
            'bank_name' => SystemSetting::get('company_bank_name', null),
            'bank_account_no' => SystemSetting::get('company_bank_account_no', null),
            'bank_account_name' => SystemSetting::get('company_bank_account_name', null),
        ];
    }

    /**
     * Get company logo as base64 for embedding in PDF
     *
     * @return string|null
     */
    protected function getCompanyLogoBase64(): ?string
    {
        $logoPath = SystemSetting::get('company_logo_path');

        if (!$logoPath || !Storage::exists($logoPath)) {
            return null;
        }

        $logoContent = Storage::get($logoPath);
        $mimeType = Storage::mimeType($logoPath);

        return 'data:' . $mimeType . ';base64,' . base64_encode($logoContent);
    }

    /**
     * Generate quotation summary (for email body)
     *
     * @param Quotation $quotation
     * @return array
     */
    public function generateSummary(Quotation $quotation): array
    {
        return [
            'quotation_no' => $quotation->quotation_no,
            'quotation_date' => $quotation->quotation_date->format('d M Y'),
            'valid_until' => $quotation->valid_until ? $quotation->valid_until->format('d M Y') : 'N/A',
            'total_amount' => 'MYR ' . number_format($quotation->total_amount, 2),
            'total_lines' => $quotation->lines->count(),
            'type' => $quotation->getTypeLabel(),
            'party_name' => $quotation->party_name,
            'status' => $quotation->getStatusLabel(),
        ];
    }

    /**
     * Add watermark to PDF (for draft/expired quotations)
     *
     * @param Quotation $quotation
     * @return string|null
     */
    public function getWatermark(Quotation $quotation): ?string
    {
        if ($quotation->isDraft()) {
            return 'DRAFT';
        }

        if ($quotation->status === Quotation::STATUS_EXPIRED) {
            return 'EXPIRED';
        }

        if ($quotation->status === Quotation::STATUS_CANCELLED) {
            return 'CANCELLED';
        }

        return null;
    }

    /**
     * Validate quotation before PDF generation
     *
     * @param Quotation $quotation
     * @return bool
     * @throws \Exception
     */
    public function validateQuotation(Quotation $quotation): bool
    {
        if ($quotation->lines->isEmpty()) {
            throw new \Exception('Cannot generate PDF for quotation without line items.');
        }

        if (!$quotation->client_id && !$quotation->vendor_id) {
            throw new \Exception('Quotation must have either a client or vendor.');
        }

        return true;
    }
}
