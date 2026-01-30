<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ExportService
{
    /**
     * Export data to Excel
     */
    public function toExcel(
        Collection $data,
        array $headers,
        string $filename,
        callable $mapCallback = null
    ) {
        $export = new class($data, $headers, $mapCallback) implements 
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithMapping,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            protected Collection $data;
            protected array $headers;
            protected $mapCallback;

            public function __construct(Collection $data, array $headers, callable $mapCallback = null)
            {
                $this->data = $data;
                $this->headers = $headers;
                $this->mapCallback = $mapCallback;
            }

            public function collection()
            {
                return $this->data;
            }

            public function headings(): array
            {
                return $this->headers;
            }

            public function map($row): array
            {
                if ($this->mapCallback) {
                    return call_user_func($this->mapCallback, $row);
                }

                return (array) $row;
            }
        };

        return Excel::download($export, $filename);
    }

    /**
     * Export data to CSV
     */
    public function toCsv(
        Collection $data,
        array $headers,
        string $filename,
        callable $mapCallback = null
    ) {
        return $this->toExcel($data, $headers, $filename, $mapCallback);
    }

    /**
     * Export data to PDF (requires additional package)
     */
    public function toPdf(
        Collection $data,
        array $headers,
        string $filename,
        string $viewFile = 'exports.default'
    ) {
        // This requires a PDF library like dompdf or snappy
        // Implementation depends on the specific PDF library used
        
        return response()->json([
            'error' => 'PDF export requires additional configuration'
        ], 501);
    }

    /**
     * Format date for export
     */
    public function formatDate($date, string $format = 'Y-m-d H:i:s'): string
    {
        if (!$date) {
            return '';
        }

        if ($date instanceof \Carbon\Carbon) {
            return $date->format($format);
        }

        return \Carbon\Carbon::parse($date)->format($format);
    }

    /**
     * Format currency for export
     */
    public function formatCurrency(float $amount, string $currency = 'MYR'): string
    {
        return number_format($amount, 2);
    }

    /**
     * Format boolean for export
     */
    public function formatBoolean($value, string $trueText = 'Yes', string $falseText = 'No'): string
    {
        return $value ? $trueText : $falseText;
    }

    /**
     * Get export filename with timestamp
     */
    public function getTimestampedFilename(string $prefix, string $extension = 'xlsx'): string
    {
        return $prefix . '_' . now()->format('Y-m-d_His') . '.' . $extension;
    }
}
