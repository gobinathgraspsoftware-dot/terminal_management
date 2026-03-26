<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Exports\Reports\TicketSummaryReportExport;
use App\Exports\Reports\StatusReportExport;
use App\Exports\Reports\SlaReportExport;
use App\Exports\Reports\ClaimReportExport;
use App\Exports\Reports\InventoryBalanceReportExport;
use App\Exports\Reports\RouterMovementReportExport;
use App\Exports\Reports\AccessoriesUsageReportExport;
use App\Exports\Reports\RejectedRescheduledReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
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

        return view('supervisor.reports.index');
    }

    // ══════════════════════════════════════════════
    // 1. Ticket Summary
    // ══════════════════════════════════════════════

    public function ticketSummary(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();
        $filters = $this->service->extractFilters($request);
        $stats   = $this->service->getTicketSummaryStats($filters, Auth::user());

        return view('supervisor.reports.ticket-summary', compact('filterOptions', 'stats'));
    }

    public function ticketSummaryData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $result = $this->service->ticketSummaryData($request, Auth::user());
        $result['data'] = $result['data']->map(fn($t) => $this->mapTicketRow($t));

        return response()->json($result);
    }

    public function ticketSummaryExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('Ticket Summary Report', $this->service->ticketSummaryQuery($filters, Auth::user())->get(), 'ticket-summary');
        }

        return Excel::download(new TicketSummaryReportExport($filters, Auth::user()), 'ticket_summary_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 2. Status Report
    // ══════════════════════════════════════════════

    public function statusReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();

        return view('supervisor.reports.status', compact('filterOptions'));
    }

    public function statusReportData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $result = $this->service->statusReportData($request, Auth::user());
        $result['data'] = $result['data']->map(fn($t) => $this->mapTicketRow($t));

        return response()->json($result);
    }

    public function statusReportExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('Status Report', $this->service->statusReportQuery($filters, Auth::user())->get(), 'status');
        }

        return Excel::download(new StatusReportExport($filters, Auth::user()), 'status_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 3. SLA Report
    // ══════════════════════════════════════════════

    public function slaReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();

        return view('supervisor.reports.sla', compact('filterOptions'));
    }

    public function slaReportData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $result = $this->service->slaReportData($request, Auth::user());
        $result['data'] = $result['data']->map(fn($t) => $this->mapTicketRow($t, true));

        return response()->json($result);
    }

    public function slaReportExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('SLA Report', $this->service->slaReportQuery($filters, Auth::user())->get(), 'sla');
        }

        return Excel::download(new SlaReportExport($filters, Auth::user()), 'sla_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 4. Claim Report
    // ══════════════════════════════════════════════

    public function claimReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();
        $filters = $this->service->extractFilters($request);
        $stats   = $this->service->getClaimSummaryStats($filters, Auth::user());

        return view('supervisor.reports.claim', compact('filterOptions', 'stats'));
    }

    public function claimReportData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $result = $this->service->claimReportData($request, Auth::user());
        $result['data'] = $result['data']->map(fn($c) => $this->mapClaimRow($c));

        return response()->json($result);
    }

    public function claimReportExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('Claim Report', $this->service->claimReportQuery($filters, Auth::user())->get(), 'claim');
        }

        return Excel::download(new ClaimReportExport($filters, Auth::user()), 'claim_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 5. Inventory Balance
    // ══════════════════════════════════════════════

    public function inventoryBalance(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();

        return view('supervisor.reports.inventory-balance', compact('filterOptions'));
    }

    public function inventoryBalanceData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $result = $this->service->inventoryBalanceData($request, Auth::user());
        $result['data'] = $result['data']->map(fn($item) => $this->mapInventoryRow($item));

        return response()->json($result);
    }

    public function inventoryBalanceExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('Inventory Balance Report', $this->service->inventoryBalanceQuery($filters, Auth::user())->get(), 'inventory-balance');
        }

        return Excel::download(new InventoryBalanceReportExport($filters, Auth::user()), 'inventory_balance_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 6. Router Movement
    // ══════════════════════════════════════════════

    public function routerMovement(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();

        return view('supervisor.reports.router-movement', compact('filterOptions'));
    }

    public function routerMovementData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $result = $this->service->routerMovementData($request, Auth::user());
        $result['data'] = $result['data']->map(fn($m) => $this->mapMovementRow($m));

        return response()->json($result);
    }

    public function routerMovementExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('Router Movement Report', $this->service->routerMovementQuery($filters, Auth::user())->get(), 'router-movement');
        }

        return Excel::download(new RouterMovementReportExport($filters, Auth::user()), 'router_movement_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 7. Accessories Usage
    // ══════════════════════════════════════════════

    public function accessoriesUsage(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();

        return view('supervisor.reports.accessories-usage', compact('filterOptions'));
    }

    public function accessoriesUsageData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $result = $this->service->accessoriesUsageData($request, Auth::user());
        $result['data'] = $result['data']->map(fn($m) => $this->mapMovementRow($m));

        return response()->json($result);
    }

    public function accessoriesUsageExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('Accessories Usage Report', $this->service->accessoriesUsageQuery($filters, Auth::user())->get(), 'accessories-usage');
        }

        return Excel::download(new AccessoriesUsageReportExport($filters, Auth::user()), 'accessories_usage_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 8. Rejected / Rescheduled
    // ══════════════════════════════════════════════

    public function rejectedRescheduled(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();

        return view('supervisor.reports.rejected-rescheduled', compact('filterOptions'));
    }

    public function rejectedRescheduledData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $result = $this->service->rejectedRescheduledData($request, Auth::user());
        $result['data'] = $result['data']->map(fn($t) => $this->mapRejectedRow($t));

        return response()->json($result);
    }

    public function rejectedRescheduledExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('Rejected / Rescheduled Report', $this->service->rejectedRescheduledQuery($filters, Auth::user())->get(), 'rejected-rescheduled');
        }

        return Excel::download(new RejectedRescheduledReportExport($filters, Auth::user()), 'rejected_rescheduled_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // ROW MAPPERS — reuse same logic as Admin
    // ══════════════════════════════════════════════

    protected function mapTicketRow($t, bool $showSla = false): array
    {
        $row = [
            'ticket_no'     => $t->ticket_no,
            'vendor'        => $t->vendor->vendor_name ?? '-',
            'merchant_name' => $t->merchant_name ?? '-',
            'state'         => $t->state->name ?? '-',
            'city'          => $t->city->name ?? '-',
            'job_category'  => $t->jobCategory->category_name ?? '-',
            'job_type'      => $t->jobType->job_title ?? '-',
            'supervisor'    => $t->supervisor->name ?? '-',
            'technician'    => $t->technician->name ?? 'Unassigned',
            'status'        => $this->statusBadge($t->status),
            'priority'      => ucfirst($t->priority),
            'created_at'    => $t->created_at?->format('d/m/Y'),
        ];

        if ($showSla) {
            $row['sla_hours']    = $t->sla_hours . 'h';
            $row['sla_deadline'] = $t->sla_deadline?->format('d/m/Y H:i') ?? '-';
            $row['sla_status']   = $this->slaBadge($t->sla_status);
            $row['rescheduled']  = $t->rescheduled_at ? '<span class="badge bg-warning">Yes</span>' : 'No';
        }

        return $row;
    }

    protected function mapClaimRow($c): array
    {
        return [
            'claim_no'       => $c->claim_no,
            'claim_category' => ucfirst($c->claim_category),
            'ticket_no'      => $c->ticket->ticket_no ?? '-',
            'technician'     => $c->technician->name ?? '-',
            'vendor'         => $c->ticket->vendor->vendor_name ?? '-',
            'claim_date'     => $c->claim_date?->format('d/m/Y'),
            'total_mileage'  => number_format((float) $c->total_mileage_amount, 2),
            'total_allowance'=> number_format((float) $c->total_allowance_amount, 2),
            'total_amount'   => number_format((float) $c->total_amount, 2),
            'status'         => $this->claimBadge($c->status),
        ];
    }

    protected function mapInventoryRow($item): array
    {
        $wQty = 0; $tQty = 0;
        if ($item->relationLoaded('stockBalances')) {
            foreach ($item->stockBalances as $b) {
                if ($b->holder_type === 'warehouse') $wQty += $b->quantity; else $tQty += $b->quantity;
            }
        }
        $isLow = $wQty <= $item->reorder_level;

        return [
            'item_code'      => $item->item_code,
            'item_name'      => $item->item_name,
            'item_type'      => ucfirst($item->item_type),
            'category'       => $item->jobCategory->category_name ?? '-',
            'serial_number'  => $item->serial_number ?? '-',
            'model'          => $item->model ?? '-',
            'warehouse_qty'  => $wQty,
            'technician_qty' => $tQty,
            'total_qty'      => $wQty + $tQty,
            'reorder_level'  => $item->reorder_level,
            'low_stock'      => $isLow ? '<span class="badge bg-danger">Low</span>' : '<span class="badge bg-success">OK</span>',
        ];
    }

    protected function mapMovementRow($m): array
    {
        return [
            'movement_no'    => $m->movement_no,
            'movement_date'  => $m->movement_date,
            'item_code'      => $m->inventoryItem->item_code ?? '-',
            'item_name'      => $m->inventoryItem->item_name ?? '-',
            'serial_number'  => $m->inventoryItem->serial_number ?? '-',
            'movement_type'  => ucfirst(str_replace('_', ' ', $m->movement_type)),
            'quantity'       => $m->quantity,
            'from'           => $m->from_holder_type ? ucfirst($m->from_holder_type) . ($m->fromHolder ? ': ' . $m->fromHolder->name : '') : '-',
            'to'             => $m->to_holder_type ? ucfirst($m->to_holder_type) . ($m->toHolder ? ': ' . $m->toHolder->name : '') : '-',
            'ticket_no'      => $m->ticket->ticket_no ?? '-',
            'condition'      => ucfirst($m->item_condition ?? 'good'),
            'performed_by'   => $m->performer->name ?? '-',
            'remarks'        => $m->remarks ?? '-',
        ];
    }

    protected function mapRejectedRow($t): array
    {
        return [
            'ticket_no'        => $t->ticket_no,
            'vendor'           => $t->vendor->vendor_name ?? '-',
            'merchant_name'    => $t->merchant_name ?? '-',
            'job_type'         => $t->jobType->job_title ?? '-',
            'supervisor'       => $t->supervisor->name ?? '-',
            'technician'       => $t->technician->name ?? 'Unassigned',
            'status'           => $this->statusBadge($t->status),
            'type'             => $t->status === 'rejected' ? '<span class="badge bg-danger">Rejected</span>' : '<span class="badge bg-warning">Rescheduled</span>',
            'reason'           => $t->reschedule_reason ?? '-',
            'rejected_at'      => $t->rejected_at?->format('d/m/Y H:i') ?? '-',
            'rescheduled_at'   => $t->rescheduled_at?->format('d/m/Y H:i') ?? '-',
            'created_at'       => $t->created_at?->format('d/m/Y'),
        ];
    }

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

    protected function slaBadge(?string $s): string
    {
        if (!$s) return '<span class="badge bg-secondary">N/A</span>';
        $m = ['on_track'=>'success','at_risk'=>'warning','breached'=>'danger'];
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
