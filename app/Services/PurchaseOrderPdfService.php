<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\SystemSetting;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseOrderPdfService
{
    /**
     * Generate PO PDF
     */
    public function generatePdf(PurchaseOrder $po)
    {
        $po->load(['vendor', 'lines.model', 'receivingDepot', 'quotation', 'createdBy', 'approvedBy']);

        $companyInfo = $this->getCompanyInfo();

        $pdf = Pdf::loadView('components.pdfs.purchase-order', [
            'po' => $po,
            'company' => $companyInfo,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf;
    }

    /**
     * Get company information from settings
     */
    protected function getCompanyInfo(): array
    {
        return [
            'name' => SystemSetting::get('company_name', 'GRASP SOFTWARE SOLUTIONS'),
            'address' => SystemSetting::get('company_address', 'Kuala Lumpur, Malaysia'),
            'phone' => SystemSetting::get('company_phone', '+60 3-XXXX XXXX'),
            'email' => SystemSetting::get('company_email', 'info@graspsoftware.com'),
            'website' => SystemSetting::get('company_website', 'www.graspsoftware.com'),
            'registration' => SystemSetting::get('company_registration', 'SSM: XXXXXXXX-X'),
            'logo' => SystemSetting::get('company_logo', null),
        ];
    }

    /**
     * Download PDF
     */
    public function download(PurchaseOrder $po)
    {
        $pdf = $this->generatePdf($po);
        return $pdf->download('PO-' . $po->po_no . '.pdf');
    }

    /**
     * Stream PDF (view in browser)
     */
    public function stream(PurchaseOrder $po)
    {
        $pdf = $this->generatePdf($po);
        return $pdf->stream('PO-' . $po->po_no . '.pdf');
    }

    /**
     * Get PDF output (for email attachment)
     */
    public function output(PurchaseOrder $po)
    {
        $pdf = $this->generatePdf($po);
        return $pdf->output();
    }
}
