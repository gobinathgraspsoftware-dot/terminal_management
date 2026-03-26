<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Exports\Reports\TicketSummaryReportExport;
use App\Exports\Reports\ClaimReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Http\Controllers\Traits\ReportPrintable;

class ReportController extends Controller
{
    use ReportPrintable;

    protected ReportService $service;

    public function __construct(ReportService $service)
    {
        $this->service = $service;
    }

    // ══════════════════════════════════════════════
    // Report Hub
    // ══════════════════════════════════════════════

    public function index()
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        return view('technician.reports.index');
    }

    // ══════════════════════════════════════════════
    // 1. My Ticket Summary
    // ══════════════════════════════════════════════

    public function ticketSummary(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();
        $filters = $this->service->extractFilters($request);
        $stats   = $this->service->getTicketSummaryStats($filters, Auth::user());

        return view('technician.reports.ticket-summary', compact('filterOptions', 'stats'));
    }

    public function ticketSummaryData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $result = $this->service->ticketSummaryData($request, Auth::user());
        $result['data'] = $result['data']->map(fn($t) => [
            'ticket_no'     => $t->ticket_no,
            'vendor'        => $t->vendor->vendor_name ?? '-',
            'merchant_name' => $t->merchant_name ?? '-',
            'state'         => $t->state->name ?? '-',
            'city'          => $t->city->name ?? '-',
            'job_category'  => $t->jobCategory?->category_name ?? '-',
            'job_type'      => $t->jobType->job_title ?? '-',
            'status'        => $this->statusBadge($t->status),
            'priority'      => ucfirst($t->priority),
            'created_at'    => $t->created_at?->format('d/m/Y'),
        ]);

        return response()->json($result);
    }

    public function ticketSummaryExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('My Ticket Summary', $this->service->ticketSummaryQuery($filters, Auth::user())->get(), 'ticket-summary');
        }

        return Excel::download(new TicketSummaryReportExport($filters, Auth::user()), 'my_ticket_summary_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 2. My Claim Report
    // ══════════════════════════════════════════════

    public function claimReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();
        $filters = $this->service->extractFilters($request);
        $stats   = $this->service->getClaimSummaryStats($filters, Auth::user());

        return view('technician.reports.claim', compact('filterOptions', 'stats'));
    }

    public function claimReportData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $result = $this->service->claimReportData($request, Auth::user());
        $result['data'] = $result['data']->map(fn($c) => [
            'claim_no'       => $c->claim_no,
            'claim_category' => ucfirst($c->claim_category),
            'ticket_no'      => $c->ticket->ticket_no ?? '-',
            'vendor'         => $c->ticket->vendor->vendor_name ?? '-',
            'claim_date'     => $c->claim_date?->format('d/m/Y'),
            'total_mileage'  => number_format((float) $c->total_mileage_amount, 2),
            'total_allowance'=> number_format((float) $c->total_allowance_amount, 2),
            'total_amount'   => number_format((float) $c->total_amount, 2),
            'status'         => $this->claimBadge($c->status),
        ]);

        return response()->json($result);
    }

    public function claimReportExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('My Claim Report', $this->service->claimReportQuery($filters, Auth::user())->get(), 'claim');
        }

        return Excel::download(new ClaimReportExport($filters, Auth::user()), 'my_claim_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════

    protected function statusBadge(string $s): string
    {
        $m = ['open'=>'secondary','assigned'=>'info','accepted'=>'primary','rejected'=>'danger','in_progress'=>'warning','scheduled'=>'info','done_success'=>'success','done_fail'=>'danger','rescheduled'=>'warning','completed'=>'success','closed'=>'dark'];
        return '<span class="badge bg-' . ($m[$s] ?? 'secondary') . '">' . ucfirst(str_replace('_', ' ', $s)) . '</span>';
    }

    protected function claimBadge(string $s): string
    {
        $m = ['draft'=>'secondary','submitted'=>'info','verified'=>'primary','non_claimable'=>'danger','pending_payment'=>'warning','paid'=>'success','cancelled'=>'dark'];
        return '<span class="badge bg-' . ($m[$s] ?? 'secondary') . '">' . ucfirst(str_replace('_', ' ', $s)) . '</span>';
    }

    protected function exportPdf(string $title, $data, string $reportType)
    {
        $pdf = Pdf::loadView('exports.reports.report-pdf', [
            'title'      => $title,
            'data'       => $data,
            'reportType' => $reportType,
            'generatedAt'=> now()->format('d/m/Y H:i:s'),
            'generatedBy'=> auth()->user()->name,
        ])->setPaper('a4', 'landscape');

        return $pdf->download(strtolower(str_replace(' ', '_', $title)) . '_' . now()->format('Ymd_His') . '.pdf');
    }
}
