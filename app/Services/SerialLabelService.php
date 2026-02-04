<?php

namespace App\Services;

use App\Models\InventorySerial;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SerialLabelService
{
    /**
     * Generate labels for serials
     *
     * @param array $serialIds
     * @param string $format (pdf|html)
     * @param array $options
     * @return mixed
     */
    public function generateLabels(array $serialIds, string $format = 'pdf', array $options = [])
    {
        $serials = InventorySerial::with('terminalModel')
            ->whereIn('id', $serialIds)
            ->get();

        $labelData = [];

        foreach ($serials as $serial) {
            $labelData[] = [
                'serial_no' => $serial->serial_no,
                'model' => $serial->terminalModel->model_name ?? 'Unknown',
                'category' => $serial->terminalModel->category->category_name ?? '',
                'qr_code' => $this->generateQrCode($serial->serial_no),
                'barcode' => $this->generateBarcode($serial->serial_no),
                'status' => $serial->current_status,
                'location' => $serial->location_name
            ];
        }

        if ($format === 'html') {
            return view('labels.serial-labels', compact('labelData', 'options'))->render();
        }

        // Generate PDF
        return $this->generatePdf($labelData, $options);
    }

    /**
     * Generate QR code for serial number
     *
     * @param string $serialNo
     * @return string
     */
    protected function generateQrCode(string $serialNo): string
    {
        // Generate QR code as base64 image
        return base64_encode(
            QrCode::format('png')
                ->size(100)
                ->generate($serialNo)
        );
    }

    /**
     * Generate barcode for serial number
     *
     * @param string $serialNo
     * @return string
     */
    protected function generateBarcode(string $serialNo): string
    {
        // For now, return the serial number
        // You can integrate with a barcode library like picqer/php-barcode-generator
        return $serialNo;
    }

    /**
     * Generate PDF with labels
     *
     * @param array $labelData
     * @param array $options
     * @return mixed
     */
    protected function generatePdf(array $labelData, array $options)
    {
        $labelSize = $options['label_size'] ?? 'standard'; // standard, small, large
        $labelsPerRow = $options['labels_per_row'] ?? 3;

        $pdf = Pdf::loadView('labels.serial-labels-pdf', [
            'labelData' => $labelData,
            'labelSize' => $labelSize,
            'labelsPerRow' => $labelsPerRow
        ]);

        $pdf->setPaper($options['paper_size'] ?? 'A4', $options['orientation'] ?? 'portrait');

        return $pdf;
    }

    /**
     * Download labels as PDF
     *
     * @param array $serialIds
     * @param array $options
     * @return mixed
     */
    public function downloadLabels(array $serialIds, array $options = [])
    {
        $pdf = $this->generateLabels($serialIds, 'pdf', $options);
        
        $filename = 'serial_labels_' . date('Ymd_His') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Print labels (stream to browser)
     *
     * @param array $serialIds
     * @param array $options
     * @return mixed
     */
    public function printLabels(array $serialIds, array $options = [])
    {
        $pdf = $this->generateLabels($serialIds, 'pdf', $options);
        
        return $pdf->stream('serial_labels.pdf');
    }

    /**
     * Generate single label HTML
     *
     * @param InventorySerial $serial
     * @return string
     */
    public function generateSingleLabel(InventorySerial $serial): string
    {
        return $this->generateLabels([$serial->id], 'html');
    }
}
