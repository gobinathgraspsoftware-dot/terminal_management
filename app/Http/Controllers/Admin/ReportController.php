<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Exports\Reports\TicketSummaryReportExport;
use App\Exports\Reports\StatusReportExport;
use App\Exports\Reports\SlaReportExport;
use App\Exports\Reports\SupervisorPricingReportExport;
use App\Exports\Reports\ClaimReportExport;
use App\Exports\Reports\PaymentReportExport;
use App\Exports\Reports\InventoryBalanceReportExport;
use App\Exports\Reports\RouterMovementReportExport;
use App\Exports\Reports\AccessoriesUsageReportExport;
use App\Exports\Reports\RejectedRescheduledReportExport;
use Illuminate\Http\Request;
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
        if (!auth()->user()->hasPermissionTo('view_reports')) {
            abort(403, 'Unauthorized');
        }

        return view('admin.reports.index');
    }

    // ══════════════════════════════════════════════
    // 1. Ticket Summary Report
    // ══════════════════════════════════════════════

    public function ticketSummary(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();
        $filters = $this->service->extractFilters($request);
        $stats   = $this->service->getTicketSummaryStats($filters);

        return view('admin.reports.ticket-summary', compact('filterOptions', 'stats'));
    }

    public function ticketSummaryData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) {
            return response()->json(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []], 403);
        }

        try {
            $result = $this->service->ticketSummaryData($request);
            $result['data'] = $result['data']->map(fn($t) => $this->mapTicketRow($t));
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to load data.',
            ]);
        }
    }

    public function ticketSummaryExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('Ticket Summary Report', $this->service->ticketSummaryQuery($filters)->get(), 'ticket-summary');
        }

        return Excel::download(new TicketSummaryReportExport($filters), 'ticket_summary_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 2. Status Report
    // ══════════════════════════════════════════════

    public function statusReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();

        return view('admin.reports.status', compact('filterOptions'));
    }

    public function statusReportData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) {
            return response()->json(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []], 403);
        }

        try {
            $result = $this->service->statusReportData($request);
            $result['data'] = $result['data']->map(fn($t) => $this->mapTicketRow($t));
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to load data.',
            ]);
        }
    }

    public function statusReportExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('Status Report', $this->service->statusReportQuery($filters)->get(), 'status');
        }

        return Excel::download(new StatusReportExport($filters), 'status_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 3. SLA Report
    // ══════════════════════════════════════════════

    public function slaReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();

        return view('admin.reports.sla', compact('filterOptions'));
    }

    public function slaReportData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) {
            return response()->json(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []], 403);
        }

        try {
            $result = $this->service->slaReportData($request);
            $result['data'] = $result['data']->map(fn($t) => $this->mapTicketRow($t, true));
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to load data.',
            ]);
        }
    }

    public function slaReportExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('SLA Report', $this->service->slaReportQuery($filters)->get(), 'sla');
        }

        return Excel::download(new SlaReportExport($filters), 'sla_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 4. Supervisor Pricing Report
    // ══════════════════════════════════════════════

    public function supervisorPricing(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();

        return view('admin.reports.supervisor-pricing', compact('filterOptions'));
    }

    public function supervisorPricingData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) {
            return response()->json(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []], 403);
        }

        try {
            $result = $this->service->supervisorPricingData($request);
            $result['data'] = $result['data']->map(function ($p) {
                return [
                    'id'            => $p->id,
                    'supervisor'    => $p->supervisor?->name ?? '-',
                    'job_category'  => $p->jobCategory?->category_name ?? '-',
                    'job_type'      => $p->jobType?->job_title ?? '-',
                    'price'         => number_format((float) $p->price, 2),
                ];
            });
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to load data.',
            ]);
        }
    }

    public function supervisorPricingExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('Supervisor Pricing Report', $this->service->supervisorPricingQuery($filters)->get(), 'supervisor-pricing');
        }

        return Excel::download(new SupervisorPricingReportExport($filters), 'supervisor_pricing_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 5. Claim Report
    // ══════════════════════════════════════════════

    public function claimReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();
        $filters = $this->service->extractFilters($request);
        $stats   = $this->service->getClaimSummaryStats($filters);

        return view('admin.reports.claim', compact('filterOptions', 'stats'));
    }

    public function claimReportData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) {
            return response()->json(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []], 403);
        }

        try {
            $result = $this->service->claimReportData($request);
            $result['data'] = $result['data']->map(fn($c) => $this->mapClaimRow($c));
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to load data.',
            ]);
        }
    }

    public function claimReportExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('Claim Report', $this->service->claimReportQuery($filters)->get(), 'claim');
        }

        return Excel::download(new ClaimReportExport($filters), 'claim_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 6. Payment Report
    // ══════════════════════════════════════════════

    public function paymentReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();

        return view('admin.reports.payment', compact('filterOptions'));
    }

    public function paymentReportData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) {
            return response()->json(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []], 403);
        }

        try {
            $result = $this->service->paymentReportData($request);
            $result['data'] = $result['data']->map(fn($c) => $this->mapClaimRow($c, true));
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to load data.',
            ]);
        }
    }

    public function paymentReportExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('Payment Report', $this->service->paymentReportQuery($filters)->get(), 'payment');
        }

        return Excel::download(new PaymentReportExport($filters), 'payment_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 7. Inventory Balance Report
    // ══════════════════════════════════════════════

    public function inventoryBalance(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();

        return view('admin.reports.inventory-balance', compact('filterOptions'));
    }

    public function inventoryBalanceData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) {
            return response()->json(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []], 403);
        }

        try {
            $result = $this->service->inventoryBalanceData($request);
            $result['data'] = $result['data']->map(fn($item) => $this->mapInventoryRow($item));
            return response()->json($result);
        } catch (\Throwable $e) {
            \Log::error('Inventory Balance Report Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to load data.',
            ]);
        }
    }

    public function inventoryBalanceExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('Inventory Balance Report', $this->service->inventoryBalanceQuery($filters)->get(), 'inventory-balance');
        }

        return Excel::download(new InventoryBalanceReportExport($filters), 'inventory_balance_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 8. Router Movement Report
    // ══════════════════════════════════════════════

    public function routerMovement(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();

        return view('admin.reports.router-movement', compact('filterOptions'));
    }

    public function routerMovementData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) {
            return response()->json(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []], 403);
        }

        try {
            $result = $this->service->routerMovementData($request);
            $result['data'] = $result['data']->map(fn($m) => $this->mapMovementRow($m));
            return response()->json($result);
        } catch (\Throwable $e) {
            \Log::error('Router Movement Report Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to load data.',
            ]);
        }
    }

    public function routerMovementExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('Router Movement Report', $this->service->routerMovementQuery($filters)->get(), 'router-movement');
        }

        return Excel::download(new RouterMovementReportExport($filters), 'router_movement_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 9. Accessories Usage Report
    // ══════════════════════════════════════════════

    public function accessoriesUsage(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();

        return view('admin.reports.accessories-usage', compact('filterOptions'));
    }

    public function accessoriesUsageData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) {
            return response()->json(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []], 403);
        }

        try {
            $result = $this->service->accessoriesUsageData($request);
            $result['data'] = $result['data']->map(fn($m) => $this->mapMovementRow($m));
            return response()->json($result);
        } catch (\Throwable $e) {
            \Log::error('Accessories Usage Report Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to load data.',
            ]);
        }
    }

    public function accessoriesUsageExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('Accessories Usage Report', $this->service->accessoriesUsageQuery($filters)->get(), 'accessories-usage');
        }

        return Excel::download(new AccessoriesUsageReportExport($filters), 'accessories_usage_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 10. Rejected / Rescheduled Report
    // ══════════════════════════════════════════════

    public function rejectedRescheduled(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $filterOptions = $this->service->getFilterOptions();

        return view('admin.reports.rejected-rescheduled', compact('filterOptions'));
    }

    public function rejectedRescheduledData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) {
            return response()->json(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []], 403);
        }

        try {
            $result = $this->service->rejectedRescheduledData($request);
            $result['data'] = $result['data']->map(fn($t) => $this->mapRejectedRow($t));
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to load data.',
            ]);
        }
    }

    public function rejectedRescheduledExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);

        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');

        if ($format === 'pdf') {
            return $this->exportPdf('Rejected / Rescheduled Report', $this->service->rejectedRescheduledQuery($filters)->get(), 'rejected-rescheduled');
        }

        return Excel::download(new RejectedRescheduledReportExport($filters), 'rejected_rescheduled_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // ROW MAPPERS (DataTable JSON)
    // ══════════════════════════════════════════════

    protected function mapTicketRow($t, bool $showSla = false): array
    {
        $row = [
            'ticket_no'     => $t->ticket_no ?? '-',
            'vendor'        => $t->vendor?->vendor_name ?? '-',
            'merchant_name' => $t->merchant_name ?? '-',
            'state'         => $t->state?->name ?? '-',
            'city'          => $t->city?->name ?? '-',
            'job_category'  => $t->jobCategory?->category_name ?? '-',
            'job_type'      => $t->jobType?->job_title ?? '-',
            'supervisor'    => $t->supervisor?->name ?? '-',
            'technician'    => $t->technician?->name ?? 'Unassigned',
            'status'        => $this->ticketStatusBadge($t->status ?? 'open'),
            'priority'      => ucfirst($t->priority ?? 'normal'),
            'created_at'    => $t->created_at?->format('d/m/Y') ?? '-',
        ];

        if ($showSla) {
            $row['sla_hours']    = ($t->sla_hours ?? 0) . 'h';
            $row['sla_deadline'] = $t->sla_deadline?->format('d/m/Y H:i') ?? '-';
            $row['sla_status']   = $this->slaBadge($t->sla_status);
            $row['rescheduled']  = $t->rescheduled_at ? '<span class="badge bg-warning">Yes</span>' : 'No';
        }

        return $row;
    }

    protected function mapClaimRow($c, bool $showPayment = false): array
    {
        $row = [
            'claim_no'       => $c->claim_no ?? '-',
            'claim_category' => ucfirst($c->claim_category ?? 'other'),
            'ticket_no'      => $c->ticket?->ticket_no ?? '-',
            'technician'     => $c->technician?->name ?? '-',
            'vendor'         => $c->ticket?->vendor?->vendor_name ?? '-',
            'claim_date'     => $c->claim_date?->format('d/m/Y') ?? '-',
            'total_mileage'  => number_format((float) ($c->total_mileage_amount ?? 0), 2),
            'total_allowance'=> number_format((float) ($c->total_allowance_amount ?? 0), 2),
            'total_amount'   => number_format((float) ($c->total_amount ?? 0), 2),
            'status'         => $this->claimStatusBadge($c->status ?? 'draft'),
        ];

        if ($showPayment) {
            $row['paid_at']    = $c->paid_at?->format('d/m/Y') ?? '-';
            $row['batch_no']   = $c->payoutBatch?->batch_no ?? '-';
        }

        return $row;
    }

    protected function mapInventoryRow($item): array
    {
        $warehouseQty = 0;
        $techQty      = 0;

        if ($item->relationLoaded('stockBalances')) {
            foreach ($item->stockBalances as $bal) {
                if ($bal->holder_type === 'warehouse') {
                    $warehouseQty += (int) $bal->quantity;
                } else {
                    $techQty += (int) $bal->quantity;
                }
            }
        }

        $isLow = $warehouseQty <= ($item->reorder_level ?? 0);

        return [
            'item_code'      => $item->item_code ?? '-',
            'item_name'      => $item->item_name ?? '-',
            'item_type'      => ucfirst($item->item_type ?? '-'),
            'category'       => \App\Models\JobCategory::find($item->job_category_id)?->category_name ?? '-',
            'serial_number'  => $item->serial_number ?? '-',
            'model'          => $item->model ?? '-',
            'warehouse_qty'  => $warehouseQty,
            'technician_qty' => $techQty,
            'total_qty'      => $warehouseQty + $techQty,
            'reorder_level'  => $item->reorder_level ?? 0,
            'low_stock'      => $isLow ? '<span class="badge bg-danger">Low</span>' : '<span class="badge bg-success">OK</span>',
        ];
    }

    protected function mapMovementRow($m): array
    {
        // Format movement_date as string explicitly (Carbon → string)
        $movementDate = '-';
        if ($m->movement_date) {
            try {
                $movementDate = $m->movement_date instanceof \Carbon\Carbon
                    ? $m->movement_date->format('d/m/Y')
                    : $m->movement_date;
            } catch (\Throwable $e) {
                $movementDate = (string) $m->movement_date;
            }
        }

        // Build from/to display safely
        $from = '-';
        if ($m->from_holder_type) {
            $from = ucfirst($m->from_holder_type);
            if ($m->from_holder_type === 'technician' && $m->fromHolder) {
                $from .= ': ' . $m->fromHolder->name;
            }
        }

        $to = '-';
        if ($m->to_holder_type) {
            $to = ucfirst($m->to_holder_type);
            if ($m->to_holder_type === 'technician' && $m->toHolder) {
                $to .= ': ' . $m->toHolder->name;
            }
        }

        return [
            'movement_no'    => $m->movement_no ?? '-',
            'movement_date'  => $movementDate,
            'item_code'      => $m->inventoryItem?->item_code ?? '-',
            'item_name'      => $m->inventoryItem?->item_name ?? '-',
            'serial_number'  => $m->inventoryItem?->serial_number ?? '-',
            'accessory_type' => ucfirst(str_replace('_', ' ', $m->inventoryItem?->accessory_type ?? '-')),
            'movement_type'  => ucfirst(str_replace('_', ' ', $m->movement_type ?? '-')),
            'quantity'       => (int) ($m->quantity ?? 0),
            'from'           => $from,
            'to'             => $to,
            'ticket_no'      => $m->ticket?->ticket_no ?? '-',
            'condition'      => ucfirst($m->item_condition ?? 'good'),
            'performed_by'   => $m->performer?->name ?? '-',
            'remarks'        => $m->remarks ?? '-',
        ];
    }

    protected function mapRejectedRow($t): array
    {
        return [
            'ticket_no'        => $t->ticket_no ?? '-',
            'vendor'           => $t->vendor?->vendor_name ?? '-',
            'merchant_name'    => $t->merchant_name ?? '-',
            'job_type'         => $t->jobType?->job_title ?? '-',
            'supervisor'       => $t->supervisor?->name ?? '-',
            'technician'       => $t->technician?->name ?? 'Unassigned',
            'status'           => $this->ticketStatusBadge($t->status ?? 'open'),
            'type'             => ($t->status === 'rejected') ? '<span class="badge bg-danger">Rejected</span>' : '<span class="badge bg-warning">Rescheduled</span>',
            'reason'           => $t->reschedule_reason ?? '-',
            'rejected_at'      => $t->rejected_at?->format('d/m/Y H:i') ?? '-',
            'rescheduled_at'   => $t->rescheduled_at?->format('d/m/Y H:i') ?? '-',
            'created_at'       => $t->created_at?->format('d/m/Y') ?? '-',
        ];
    }

    // ══════════════════════════════════════════════
    // STATUS BADGE HELPERS
    // ══════════════════════════════════════════════

    protected function ticketStatusBadge(string $status): string
    {
        $map = [
            'open'         => 'secondary',
            'assigned'     => 'info',
            'accepted'     => 'primary',
            'rejected'     => 'danger',
            'in_progress'  => 'warning',
            'scheduled'    => 'info',
            'done_success' => 'success',
            'done_fail'    => 'danger',
            'rescheduled'  => 'warning',
            'completed'    => 'success',
            'closed'       => 'dark',
        ];

        $color = $map[$status] ?? 'secondary';
        $label = ucfirst(str_replace('_', ' ', $status));

        return '<span class="badge bg-' . $color . '">' . $label . '</span>';
    }

    protected function claimStatusBadge(string $status): string
    {
        $map = [
            'draft'           => 'secondary',
            'submitted'       => 'info',
            'verified'        => 'primary',
            'non_claimable'   => 'danger',
            'pending_payment' => 'warning',
            'paid'            => 'success',
            'cancelled'       => 'dark',
        ];

        $color = $map[$status] ?? 'secondary';
        $label = ucfirst(str_replace('_', ' ', $status));

        return '<span class="badge bg-' . $color . '">' . $label . '</span>';
    }

    protected function slaBadge(?string $sla): string
    {
        if (!$sla) return '<span class="badge bg-secondary">N/A</span>';

        $map = [
            'on_track'  => 'success',
            'at_risk'   => 'warning',
            'breached'  => 'danger',
        ];

        $color = $map[$sla] ?? 'secondary';
        $label = ucfirst(str_replace('_', ' ', $sla));

        return '<span class="badge bg-' . $color . '">' . $label . '</span>';
    }

    // ══════════════════════════════════════════════
    // PDF EXPORT HELPER
    // ══════════════════════════════════════════════

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
