<?php

namespace App\Exports\Reports;

use App\Models\User;
use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClaimReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    protected array $filters;
    protected ?User $user;

    public function __construct(array $filters = [], ?User $user = null)
    {
        $this->filters = $filters;
        $this->user    = $user;
    }

    public function query()
    {
        return (new ReportService())->claimReportQuery($this->filters, $this->user);
    }

    public function headings(): array
    {
        return [
            'Claim No', 'Category', 'Ticket No', 'Technician',
            'Vendor', 'Claim Date', 'Mileage (RM)', 'Allowance (RM)',
            'Total Amount (RM)', 'Status',
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
            number_format((float) $c->total_mileage_amount, 2),
            number_format((float) $c->total_allowance_amount, 2),
            number_format((float) $c->total_amount, 2),
            ucfirst(str_replace('_', ' ', $c->status)),
        ];
    }

    public function title(): string { return 'Claim Report'; }
}
