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

use App\Http\Controllers\Traits\ReportPrintable;

class ReportController extends Controller
{
    use ReportPrintable;

    protected ReportService $service;

    public function __construct(ReportService $service)
    {
        $this->service = $service;
    }

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
        if (!auth()->user()->hasPermissionTo('view_reports')) {
            return response()->json(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []], 403);
        }
        try {
            $result = $this->service->ticketSummaryData($request, Auth::user());
            $result['data'] = $result['data']->map(fn($t) => $this->mapTicketRow($t));
            return response()->json($result);
        } catch (\Throwable $e) {
            return $this->errorJson($request, $e);
        }
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
        if (!auth()->user()->hasPermissionTo('view_reports')) {
            return response()->json(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []], 403);
        }
        try {
            $result = $this->service->statusReportData($request, Auth::user());
            $result['data'] = $result['data']->map(fn($t) => $this->mapTicketRow($t));
            return response()->json($result);
        } catch (\Throwable $e) {
            return $this->errorJson($request, $e);
        }
    }

    public function statusReportExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);
        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');
        if ($format === 'pdf') return $this->exportPdf('Status Report', $this->service->statusReportQuery($filters, Auth::user())->get(), 'status');
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
        if (!auth()->user()->hasPermissionTo('view_reports')) {
            return response()->json(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []], 403);
        }
        try {
            $result = $this->service->slaReportData($request, Auth::user());
            $result['data'] = $result['data']->map(fn($t) => $this->mapTicketRow($t, true));
            return response()->json($result);
        } catch (\Throwable $e) {
            return $this->errorJson($request, $e);
        }
    }

    public function slaReportExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);
        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');
        if ($format === 'pdf') return $this->exportPdf('SLA Report', $this->service->slaReportQuery($filters, Auth::user())->get(), 'sla');
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
        if (!auth()->user()->hasPermissionTo('view_reports')) {
            return response()->json(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []], 403);
        }
        try {
            $result = $this->service->claimReportData($request, Auth::user());
            $result['data'] = $result['data']->map(fn($c) => $this->mapClaimRow($c));
            return response()->json($result);
        } catch (\Throwable $e) {
            return $this->errorJson($request, $e);
        }
    }

    public function claimReportExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);
        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');
        if ($format === 'pdf') return $this->exportPdf('Claim Report', $this->service->claimReportQuery($filters, Auth::user())->get(), 'claim');
        return Excel::download(new ClaimReportExport($filters, Auth::user()), 'claim_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // 5–8. Inventory, Router, Accessories, Rejected
    // ══════════════════════════════════════════════

    public function inventoryBalance(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);
        $filterOptions = $this->service->getFilterOptions();
        return view('supervisor.reports.inventory-balance', compact('filterOptions'));
    }

    public function inventoryBalanceData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) return response()->json(['draw'=>1,'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>[]], 403);
        try {
            $result = $this->service->inventoryBalanceData($request, Auth::user());
            $result['data'] = $result['data']->map(fn($item) => $this->mapInventoryRow($item));
            return response()->json($result);
        } catch (\Throwable $e) {
            \Log::error('Supervisor Inventory Balance Error: ' . $e->getMessage());
            return $this->errorJson($request, $e);
        }
    }

    public function inventoryBalanceExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);
        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');
        if ($format === 'pdf') return $this->exportPdf('Inventory Balance Report', $this->service->inventoryBalanceQuery($filters, Auth::user())->get(), 'inventory-balance');
        return Excel::download(new InventoryBalanceReportExport($filters, Auth::user()), 'inventory_balance_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function routerMovement(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);
        $filterOptions = $this->service->getFilterOptions();
        return view('supervisor.reports.router-movement', compact('filterOptions'));
    }

    public function routerMovementData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) return response()->json(['draw'=>1,'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>[]], 403);
        try {
            $result = $this->service->routerMovementData($request, Auth::user());
            $result['data'] = $result['data']->map(fn($m) => $this->mapMovementRow($m));
            return response()->json($result);
        } catch (\Throwable $e) {
            \Log::error('Supervisor Router Movement Error: ' . $e->getMessage());
            return $this->errorJson($request, $e);
        }
    }

    public function routerMovementExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);
        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');
        if ($format === 'pdf') return $this->exportPdf('Router Movement Report', $this->service->routerMovementQuery($filters, Auth::user())->get(), 'router-movement');
        return Excel::download(new RouterMovementReportExport($filters, Auth::user()), 'router_movement_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function accessoriesUsage(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);
        $filterOptions = $this->service->getFilterOptions();
        return view('supervisor.reports.accessories-usage', compact('filterOptions'));
    }

    public function accessoriesUsageData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) return response()->json(['draw'=>1,'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>[]], 403);
        try {
            $result = $this->service->accessoriesUsageData($request, Auth::user());
            $result['data'] = $result['data']->map(fn($m) => $this->mapMovementRow($m));
            return response()->json($result);
        } catch (\Throwable $e) {
            \Log::error('Supervisor Accessories Usage Error: ' . $e->getMessage());
            return $this->errorJson($request, $e);
        }
    }

    public function accessoriesUsageExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);
        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');
        if ($format === 'pdf') return $this->exportPdf('Accessories Usage Report', $this->service->accessoriesUsageQuery($filters, Auth::user())->get(), 'accessories-usage');
        return Excel::download(new AccessoriesUsageReportExport($filters, Auth::user()), 'accessories_usage_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function rejectedRescheduled(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);
        $filterOptions = $this->service->getFilterOptions();
        return view('supervisor.reports.rejected-rescheduled', compact('filterOptions'));
    }

    public function rejectedRescheduledData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) return response()->json(['draw'=>1,'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>[]], 403);
        try {
            $result = $this->service->rejectedRescheduledData($request, Auth::user());
            $result['data'] = $result['data']->map(fn($t) => $this->mapRejectedRow($t));
            return response()->json($result);
        } catch (\Throwable $e) {
            return $this->errorJson($request, $e);
        }
    }

    public function rejectedRescheduledExport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('export_reports')) abort(403);
        $filters = $this->service->extractFilters($request);
        $format  = $request->input('format', 'xlsx');
        if ($format === 'pdf') return $this->exportPdf('Rejected / Rescheduled Report', $this->service->rejectedRescheduledQuery($filters, Auth::user())->get(), 'rejected-rescheduled');
        return Excel::download(new RejectedRescheduledReportExport($filters, Auth::user()), 'rejected_rescheduled_report_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ══════════════════════════════════════════════
    // ROW MAPPERS — null-safe everywhere
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
            'status'        => $this->statusBadge($t->status ?? 'open'),
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

    protected function mapClaimRow($c): array
    {
        return [
            'claim_no'       => $c->claim_no ?? '-',
            'claim_category' => ucfirst($c->claim_category ?? 'other'),
            'ticket_no'      => $c->ticket?->ticket_no ?? '-',
            'technician'     => $c->technician?->name ?? '-',
            'vendor'         => $c->ticket?->vendor?->vendor_name ?? '-',
            'claim_date'     => $c->claim_date?->format('d/m/Y') ?? '-',
            'total_mileage'  => number_format((float) ($c->total_mileage_amount ?? 0), 2),
            'total_allowance'=> number_format((float) ($c->total_allowance_amount ?? 0), 2),
            'total_amount'   => number_format((float) ($c->total_amount ?? 0), 2),
            'status'         => $this->claimBadge($c->status ?? 'draft'),
        ];
    }

    protected function mapInventoryRow($item): array
    {
        $wQty = 0; $tQty = 0;
        if ($item->relationLoaded('stockBalances')) {
            foreach ($item->stockBalances as $b) {
                if ($b->holder_type === 'warehouse') $wQty += (int) $b->quantity; else $tQty += (int) $b->quantity;
            }
        }
        $isLow = $wQty <= ($item->reorder_level ?? 0);
        return [
            'item_code'      => $item->item_code ?? '-',
            'item_name'      => $item->item_name ?? '-',
            'item_type'      => ucfirst($item->item_type ?? '-'),
            'category'       => \App\Models\JobCategory::find($item->job_category_id)?->category_name ?? '-',
            'serial_number'  => $item->serial_number ?? '-',
            'model'          => $item->model ?? '-',
            'warehouse_qty'  => $wQty,
            'technician_qty' => $tQty,
            'total_qty'      => $wQty + $tQty,
            'reorder_level'  => $item->reorder_level ?? 0,
            'low_stock'      => $isLow ? '<span class="badge bg-danger">Low</span>' : '<span class="badge bg-success">OK</span>',
        ];
    }

    protected function mapMovementRow($m): array
    {
        $movementDate = '-';
        if ($m->movement_date) {
            try {
                $movementDate = $m->movement_date instanceof \Carbon\Carbon ? $m->movement_date->format('d/m/Y') : (string) $m->movement_date;
            } catch (\Throwable $e) { $movementDate = (string) $m->movement_date; }
        }
        $from = '-';
        if ($m->from_holder_type) {
            $from = ucfirst($m->from_holder_type);
            if ($m->from_holder_type === 'technician' && $m->fromHolder) $from .= ': ' . $m->fromHolder->name;
        }
        $to = '-';
        if ($m->to_holder_type) {
            $to = ucfirst($m->to_holder_type);
            if ($m->to_holder_type === 'technician' && $m->toHolder) $to .= ': ' . $m->toHolder->name;
        }
        return [
            'movement_no'    => $m->movement_no ?? '-',
            'movement_date'  => $movementDate,
            'item_code'      => $m->inventoryItem?->item_code ?? '-',
            'item_name'      => $m->inventoryItem?->item_name ?? '-',
            'serial_number'  => (function() use ($m) {
                // Router ID from movement's router_ids JSON, then ticket's router_id/router_ids
                $raw = $m->router_ids ?? null;
                if ($raw) {
                    $decoded = is_array($raw) ? $raw : json_decode($raw, true);
                    if (!empty($decoded) && is_array($decoded)) return implode(', ', $decoded);
                }
                if ($m->ticket) {
                    if (!empty($m->ticket->router_id)) return $m->ticket->router_id;
                    $tIds = $m->ticket->router_ids;
                    if ($tIds) {
                        $arr = is_array($tIds) ? $tIds : json_decode($tIds, true);
                        if (!empty($arr) && is_array($arr)) return implode(', ', $arr);
                    }
                }
                return '-';
            })(),
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
            'ticket_no'      => $t->ticket_no ?? '-',
            'vendor'         => $t->vendor?->vendor_name ?? '-',
            'merchant_name'  => $t->merchant_name ?? '-',
            'job_type'       => $t->jobType?->job_title ?? '-',
            'supervisor'     => $t->supervisor?->name ?? '-',
            'technician'     => $t->technician?->name ?? 'Unassigned',
            'status'         => $this->statusBadge($t->status ?? 'open'),
            'type'           => ($t->status === 'rejected') ? '<span class="badge bg-danger">Rejected</span>' : '<span class="badge bg-warning">Rescheduled</span>',
            'reason'         => $t->reschedule_reason ?? '-',
            'rejected_at'    => $t->rejected_at?->format('d/m/Y H:i') ?? '-',
            'rescheduled_at' => $t->rescheduled_at?->format('d/m/Y H:i') ?? '-',
            'created_at'     => $t->created_at?->format('d/m/Y') ?? '-',
        ];
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

    protected function slaBadge(?string $s): string
    {
        if (!$s) return '<span class="badge bg-secondary">N/A</span>';
        $m = ['on_track'=>'success','at_risk'=>'warning','breached'=>'danger'];
        return '<span class="badge bg-' . ($m[$s] ?? 'secondary') . '">' . ucfirst(str_replace('_', ' ', $s)) . '</span>';
    }

    protected function errorJson(Request $request, \Throwable $e)
    {
        return response()->json([
            'draw' => intval($request->input('draw', 1)),
            'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
            'error' => config('app.debug') ? $e->getMessage() : 'Failed to load data.',
        ]);
    }

    protected function exportPdf(string $title, $data, string $reportType)
    {
        $pdf = Pdf::loadView('exports.reports.report-pdf', [
            'title' => $title, 'data' => $data, 'reportType' => $reportType,
            'generatedAt' => now()->format('d/m/Y H:i:s'), 'generatedBy' => auth()->user()->name,
        ])->setPaper('a4', 'landscape');
        return $pdf->download(strtolower(str_replace(' ', '_', $title)) . '_' . now()->format('Ymd_His') . '.pdf');
    }
}
