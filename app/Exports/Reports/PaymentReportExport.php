<?php

namespace App\Exports\Reports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use App\Exports\Reports\ReportExportStyles;

class PaymentReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithStyles
{
    use ReportExportStyles;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return (new ReportService())->paymentReportQuery($this->filters);
    }

    public function headings(): array
    {
        return [
            'Claim No', 'Category', 'Ticket No', 'Technician',
            'Vendor', 'Claim Date', 'Total Amount (RM)',
            'Status', 'Paid Date', 'Batch No',
        ];
    }

    public function map($c): array
    {
        return [
            $c->claim_no,
            ucfirst($c->claim_category),
            $c->ticket?->ticket_no ?? '',
            $c->technician?->name ?? '',
            $c->ticket?->vendor?->vendor_name ?? '',
            $c->claim_date?->format('d/m/Y'),
            number_format((float) $c->total_amount, 2),
            ucfirst(str_replace('_', ' ', $c->status)),
            $c->paid_at?->format('d/m/Y') ?? '',
            $c->payoutBatch?->batch_no ?? '',
        ];
    }

    public function title(): string { return 'Payment Report'; }
}
