<?php

namespace App\Exports;

use App\Models\Claim;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClaimsManagementExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected string $category;
    protected ?string $status;

    public function __construct(string $category = 'all', ?string $status = null)
    {
        $this->category = $category;
        $this->status   = $status;
    }

    public function query()
    {
        $query = Claim::query()->with(['technician', 'submitter', 'verifier', 'ticket']);

        if ($this->category === 'ticket') {
            $query->ticketClaims();
        } elseif ($this->category === 'other') {
            $query->otherClaims();
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        return $query->orderBy('submitted_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Claim ID', 'Category', 'Ticket No', 'Claim Type',
            'Submitted By', 'Technician', 'Description',
            'Original Amount (RM)', 'Claim Amount (RM)',
            'Status', 'Admin Remarks', 'Submitted Date',
            'Verified Date', 'Verified By', 'Paid Date',
        ];
    }

    public function map($claim): array
    {
        return [
            $claim->claim_no,
            ucfirst($claim->claim_category),
            $claim->ticket->ticket_no ?? '-',
            $claim->claim_type_label ?? '-',
            $claim->submitter->name ?? '-',
            $claim->technician->name ?? '-',
            $claim->description,
            $claim->original_amount ? number_format((float) $claim->original_amount, 2) : '-',
            number_format((float) $claim->total_amount, 2),
            ucfirst(str_replace('_', ' ', $claim->status)),
            $claim->admin_remarks ?? '-',
            $claim->submitted_at ? $claim->submitted_at->format('d/m/Y H:i') : '-',
            $claim->verified_at ? $claim->verified_at->format('d/m/Y H:i') : '-',
            $claim->verifier->name ?? '-',
            $claim->paid_at ? $claim->paid_at->format('d/m/Y H:i') : '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
