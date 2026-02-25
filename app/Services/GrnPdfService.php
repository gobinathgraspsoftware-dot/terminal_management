<?php

namespace App\Services;

use App\Models\Grn;
use App\Models\SystemSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * GrnPdfService - Handles PDF generation for Goods Receipt Notes.
 *
 * Features:
 * - Professional PDF layout with company branding
 * - GRN header, vendor, depot information
 * - Line items with serial numbers
 * - Watermark for draft/cancelled GRNs
 * - Download, stream, and save to storage
 */
class GrnPdfService
{
    // =========================================================================
    // GENERATE
    // =========================================================================

    /**
     * Generate GRN PDF.
     *
     * @param  Grn    $grn
     * @param  array  $options  [paper_size, orientation, watermark, margins]
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generatePdf(Grn $grn, array $options = [])
    {
        // Eager-load all needed relationships
        $grn->load([
            'vendor',
            'receivingDepot',
            'purchaseOrder',
            'lines.model.category',
            'lines.serials',
            'createdBy',
            'postedBy',
        ]);

        // Validate basic data
        $this->validateGrn($grn);

        // Company settings
        $companySettings = $this->getCompanySettings();

        // Watermark
        $watermark = $options['watermark'] ?? $this->getWatermark($grn);

        // Prepare view data
        $data = [
            'grn'       => $grn,
            'company'   => $companySettings,
            'watermark' => $watermark,
        ];

        // Render PDF
        $pdf = Pdf::loadView('components.pdfs.grn', $data);
        $pdf->setPaper($options['paper_size'] ?? 'A4', $options['orientation'] ?? 'portrait');

        // Optional margin overrides
        if (isset($options['margins'])) {
            $pdf->setOption('margin-top', $options['margins']['top'] ?? 10);
            $pdf->setOption('margin-bottom', $options['margins']['bottom'] ?? 10);
            $pdf->setOption('margin-left', $options['margins']['left'] ?? 10);
            $pdf->setOption('margin-right', $options['margins']['right'] ?? 10);
        }

        return $pdf;
    }

    // =========================================================================
    // DOWNLOAD / STREAM / SAVE
    // =========================================================================

    /**
     * Download GRN PDF.
     */
    public function download(Grn $grn, array $options = [])
    {
        $pdf      = $this->generatePdf($grn, $options);
        $filename = $this->generateFilename($grn);

        return $pdf->download($filename);
    }

    /**
     * Stream PDF (view in browser).
     */
    public function stream(Grn $grn, array $options = [])
    {
        $pdf      = $this->generatePdf($grn, $options);
        $filename = $this->generateFilename($grn);

        return $pdf->stream($filename);
    }

    /**
     * Save PDF to storage and return path.
     */
    public function savePdf(Grn $grn, string $path = null, array $options = []): string
    {
        $pdf         = $this->generatePdf($grn, $options);
        $filename    = $this->generateFilename($grn);
        $storagePath = $path ?? 'grns/' . $grn->id;
        $fullPath    = $storagePath . '/' . $filename;

        Storage::put($fullPath, $pdf->output());

        return $fullPath;
    }

    /**
     * Get raw PDF content (for email attachment).
     */
    public function getPdfContent(Grn $grn, array $options = []): string
    {
        $pdf = $this->generatePdf($grn, $options);

        return $pdf->output();
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Generate filename for PDF.
     */
    protected function generateFilename(Grn $grn): string
    {
        $grnNo = str_replace(['/', '\\', ' '], '-', $grn->grn_no);

        return "GRN_{$grnNo}.pdf";
    }

    /**
     * Get watermark text based on GRN status.
     */
    public function getWatermark(Grn $grn): ?string
    {
        if ($grn->isDraft()) {
            return 'DRAFT';
        }

        if ($grn->isCancelled()) {
            return 'CANCELLED';
        }

        return null;
    }

    /**
     * Validate GRN has minimum data for PDF.
     *
     * @throws \Exception
     */
    protected function validateGrn(Grn $grn): void
    {
        if ($grn->lines->isEmpty()) {
            throw new \Exception('Cannot generate PDF for GRN without line items.');
        }
    }

    /**
     * Get company settings.
     */
    protected function getCompanySettings(): array
    {
        return [
            'name'            => SystemSetting::get('company_name', 'GRASP SOFTWARE SOLUTIONS'),
            'registration_no' => SystemSetting::get('company_registration_no', 'ROC: 123456789'),
            'tax_id'          => SystemSetting::get('company_tax_id', 'Tax ID: MY12345678'),
            'address'         => SystemSetting::get('company_address', 'Kuala Lumpur, Malaysia'),
            'phone'           => SystemSetting::get('company_phone', '+60 3-XXXX XXXX'),
            'email'           => SystemSetting::get('company_email', 'info@graspsoftware.com'),
            'website'         => SystemSetting::get('company_website', 'www.graspsoftware.com'),
            'logo_path'       => SystemSetting::get('company_logo_path', null),
        ];
    }

    /**
     * Get company logo as base64 for embedding in PDF.
     */
    public function getCompanyLogoBase64(): ?string
    {
        $logoPath = SystemSetting::get('company_logo_path');

        if (!$logoPath || !Storage::exists($logoPath)) {
            return null;
        }

        $logoContent = Storage::get($logoPath);
        $mimeType    = Storage::mimeType($logoPath);

        return 'data:' . $mimeType . ';base64,' . base64_encode($logoContent);
    }

    /**
     * Generate summary for email body.
     */
    public function generateSummary(Grn $grn): array
    {
        $grn->load(['vendor', 'receivingDepot', 'lines']);

        $totalValue = $grn->lines->sum('line_total');

        return [
            'grn_no'        => $grn->grn_no,
            'grn_date'      => $grn->grn_date->format('d M Y'),
            'vendor_name'   => $grn->vendor?->vendor_name ?? $grn->vendor?->company_name ?? '-',
            'depot_name'    => $grn->receivingDepot?->depot_name ?? '-',
            'total_items'   => $grn->total_items,
            'total_lines'   => $grn->lines->count(),
            'total_value'   => 'MYR ' . number_format($totalValue, 2),
            'po_no'         => $grn->purchaseOrder->po_no ?? '-',
            'status'        => ucfirst($grn->status),
        ];
    }
}
