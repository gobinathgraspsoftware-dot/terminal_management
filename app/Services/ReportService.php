<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\ClaimLine;
use App\Models\InventoryItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\SupervisorJobPricing;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportService
{
    // ══════════════════════════════════════════════════════════════
    // 1. TICKET SUMMARY REPORT
    // ══════════════════════════════════════════════════════════════

    public function ticketSummaryQuery(array $filters, ?User $user = null): Builder
    {
        $query = Ticket::with([
            'vendor', 'state', 'city', 'supervisor', 'technician',
            'jobCategory', 'jobType', 'creator',
        ]);

        // Role-based scoping
        if ($user) {
            $query->visibleTo($user);
        }

        $this->applyTicketFilters($query, $filters);

        return $query->orderBy('created_at', 'desc');
    }

    public function ticketSummaryData(Request $request, ?User $user = null): array
    {
        $filters = $this->extractFilters($request);
        $query   = $this->ticketSummaryQuery($filters, $user);

        return $this->paginateForDataTable($request, $query);
    }

    // ══════════════════════════════════════════════════════════════
    // 2. STATUS REPORT
    // ══════════════════════════════════════════════════════════════

    public function statusReportQuery(array $filters, ?User $user = null): Builder
    {
        $query = Ticket::with([
            'vendor', 'state', 'city', 'supervisor', 'technician',
            'jobCategory', 'jobType',
        ]);

        if ($user) {
            $query->visibleTo($user);
        }

        // Primary filter: status
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (!empty($filters['date_from'])) $query->whereDate('created_at', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))   $query->whereDate('created_at', '<=', $filters['date_to']);

        $this->applyAssigneeFilter($query, $filters);

        if (!empty($filters['job_type_id'])) {
            $ids = is_array($filters['job_type_id']) ? $filters['job_type_id'] : [$filters['job_type_id']];
            $query->whereIn('job_type_id', $ids);
        }

        // SLA Breach filter
        if (isset($filters['sla_breach']) && $filters['sla_breach'] !== '') {
            if ($filters['sla_breach'] === 'yes') {
                $query->where('sla_status', 'breached');
            } else {
                $query->where(function ($q) {
                    $q->where('sla_status', '!=', 'breached')->orWhereNull('sla_status');
                });
            }
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function statusReportData(Request $request, ?User $user = null): array
    {
        $filters = $this->extractFilters($request);
        $query   = $this->statusReportQuery($filters, $user);

        return $this->paginateForDataTable($request, $query);
    }

    // ══════════════════════════════════════════════════════════════
    // 3. SLA REPORT
    // ══════════════════════════════════════════════════════════════

    public function slaReportQuery(array $filters, ?User $user = null): Builder
    {
        $query = Ticket::with([
            'vendor', 'state', 'city', 'supervisor', 'technician',
            'jobCategory', 'jobType',
        ]);

        if ($user) {
            $query->visibleTo($user);
        }

        if (!empty($filters['date_from'])) $query->whereDate('created_at', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))   $query->whereDate('created_at', '<=', $filters['date_to']);

        // SLA Status filter
        if (!empty($filters['sla_status'])) {
            if (is_array($filters['sla_status'])) {
                $query->whereIn('sla_status', $filters['sla_status']);
            } else {
                $query->where('sla_status', $filters['sla_status']);
            }
        }

        $this->applyAssigneeFilter($query, $filters);

        if (!empty($filters['job_type_id'])) {
            $ids = is_array($filters['job_type_id']) ? $filters['job_type_id'] : [$filters['job_type_id']];
            $query->whereIn('job_type_id', $ids);
        }

        if (!empty($filters['vendor_id'])) {
            $ids = is_array($filters['vendor_id']) ? $filters['vendor_id'] : [$filters['vendor_id']];
            $query->whereIn('vendor_id', $ids);
        }

        // Rescheduled filter
        if (isset($filters['rescheduled']) && $filters['rescheduled'] !== '') {
            if ($filters['rescheduled'] === 'yes') {
                $query->whereNotNull('rescheduled_at');
            } else {
                $query->whereNull('rescheduled_at');
            }
        }

        // SLA Time Range
        if (!empty($filters['sla_time_range'])) {
            if ($filters['sla_time_range'] === 'lt24') {
                $query->where('sla_hours', '<', 24);
            } elseif ($filters['sla_time_range'] === 'gt24') {
                $query->where('sla_hours', '>=', 24);
            }
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function slaReportData(Request $request, ?User $user = null): array
    {
        $filters = $this->extractFilters($request);
        $query   = $this->slaReportQuery($filters, $user);

        return $this->paginateForDataTable($request, $query);
    }

    // ══════════════════════════════════════════════════════════════
    // 4. SUPERVISOR PRICING REPORT
    // ══════════════════════════════════════════════════════════════

    public function supervisorPricingQuery(array $filters): Builder
    {
        $query = SupervisorJobPricing::with(['supervisor', 'jobCategory', 'jobType']);

        if (!empty($filters['supervisor_id'])) {
            $ids = is_array($filters['supervisor_id']) ? $filters['supervisor_id'] : [$filters['supervisor_id']];
            $query->whereIn('supervisor_id', $ids);
        }

        if (!empty($filters['job_type_id'])) {
            $ids = is_array($filters['job_type_id']) ? $filters['job_type_id'] : [$filters['job_type_id']];
            $query->whereIn('job_type_id', $ids);
        }

        if (!empty($filters['job_category_id'])) {
            $ids = is_array($filters['job_category_id']) ? $filters['job_category_id'] : [$filters['job_category_id']];
            $query->whereIn('job_category_id', $ids);
        }

        return $query->orderBy('supervisor_id');
    }

    public function supervisorPricingData(Request $request): array
    {
        $filters = $this->extractFilters($request);
        $query   = $this->supervisorPricingQuery($filters);

        return $this->paginateForDataTable($request, $query);
    }

    // ══════════════════════════════════════════════════════════════
    // 5. CLAIM REPORT
    // ══════════════════════════════════════════════════════════════

    public function claimReportQuery(array $filters, ?User $user = null): Builder
    {
        $query = Claim::with(['technician', 'ticket', 'ticket.vendor', 'ticket.jobType', 'lines']);

        // Role-based scoping
        if ($user) {
            if ($user->hasRole('technician')) {
                $query->where('technician_id', $user->id);
            } elseif ($user->hasRole('supervisor')) {
                $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
                $teamIds[] = $user->id;
                $query->whereIn('technician_id', $teamIds);
            }
            // Admin: no scope restriction
        }

        if (!empty($filters['date_from'])) $query->whereDate('claim_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))   $query->whereDate('claim_date', '<=', $filters['date_to']);

        // Assignee (technician/supervisor)
        if (!empty($filters['technician_id'])) {
            $ids = is_array($filters['technician_id']) ? $filters['technician_id'] : [$filters['technician_id']];
            $query->whereIn('technician_id', $ids);
        }

        // Claim type (from claim_lines: mileage, toll, meal, other)
        if (!empty($filters['claim_type'])) {
            $types = is_array($filters['claim_type']) ? $filters['claim_type'] : [$filters['claim_type']];
            $query->whereHas('lines', function ($q) use ($types) {
                $q->whereIn('claim_type', $types);
            });
        }

        // Claim status
        if (!empty($filters['claim_status'])) {
            if (is_array($filters['claim_status'])) {
                $query->whereIn('status', $filters['claim_status']);
            } else {
                $query->where('status', $filters['claim_status']);
            }
        }

        // Claim category
        if (!empty($filters['claim_category'])) {
            $query->where('claim_category', $filters['claim_category']);
        }

        // Job type (via ticket)
        if (!empty($filters['job_type_id'])) {
            $ids = is_array($filters['job_type_id']) ? $filters['job_type_id'] : [$filters['job_type_id']];
            $query->whereHas('ticket', function ($q) use ($ids) {
                $q->whereIn('job_type_id', $ids);
            });
        }

        // Merchant (via ticket)
        if (!empty($filters['merchant_name'])) {
            $query->whereHas('ticket', function ($q) use ($filters) {
                $q->where('merchant_name', 'LIKE', '%' . $filters['merchant_name'] . '%');
            });
        }

        return $query->orderBy('claim_date', 'desc');
    }

    public function claimReportData(Request $request, ?User $user = null): array
    {
        $filters = $this->extractFilters($request);
        $query   = $this->claimReportQuery($filters, $user);

        return $this->paginateForDataTable($request, $query);
    }

    // ══════════════════════════════════════════════════════════════
    // 6. PAYMENT REPORT
    // ══════════════════════════════════════════════════════════════

    public function paymentReportQuery(array $filters): Builder
    {
        $query = Claim::with(['technician', 'ticket', 'ticket.vendor', 'payoutBatch'])
            ->whereIn('status', [
                Claim::STATUS_PENDING_PAYMENT,
                Claim::STATUS_PAID,
                Claim::STATUS_VERIFIED,
            ]);

        // Payment status
        if (!empty($filters['payment_status'])) {
            if ($filters['payment_status'] === 'pending') {
                $query->where('status', Claim::STATUS_PENDING_PAYMENT);
            } elseif ($filters['payment_status'] === 'processed') {
                $query->where('status', Claim::STATUS_PAID);
            }
        }

        if (!empty($filters['date_from'])) $query->whereDate('claim_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))   $query->whereDate('claim_date', '<=', $filters['date_to']);

        if (!empty($filters['technician_id'])) {
            $ids = is_array($filters['technician_id']) ? $filters['technician_id'] : [$filters['technician_id']];
            $query->whereIn('technician_id', $ids);
        }

        if (!empty($filters['job_type_id'])) {
            $ids = is_array($filters['job_type_id']) ? $filters['job_type_id'] : [$filters['job_type_id']];
            $query->whereHas('ticket', function ($q) use ($ids) {
                $q->whereIn('job_type_id', $ids);
            });
        }

        // Amount range
        if (!empty($filters['amount_min'])) {
            $query->where('total_amount', '>=', $filters['amount_min']);
        }
        if (!empty($filters['amount_max'])) {
            $query->where('total_amount', '<=', $filters['amount_max']);
        }

        return $query->orderBy('claim_date', 'desc');
    }

    public function paymentReportData(Request $request): array
    {
        $filters = $this->extractFilters($request);
        $query   = $this->paymentReportQuery($filters);

        return $this->paginateForDataTable($request, $query);
    }

    // ══════════════════════════════════════════════════════════════
    // 7. INVENTORY BALANCE REPORT
    // ══════════════════════════════════════════════════════════════

    public function inventoryBalanceQuery(array $filters, ?User $user = null): Builder
    {
        $query = InventoryItem::with(['jobCategory', 'stockBalances', 'stockBalances.holder'])
            ->where('status', 'active');

        // Item type
        if (!empty($filters['item_type'])) {
            if (is_array($filters['item_type'])) {
                $query->whereIn('item_type', $filters['item_type']);
            } else {
                $query->where('item_type', $filters['item_type']);
            }
        }

        // Low stock
        if (isset($filters['low_stock']) && $filters['low_stock'] === 'yes') {
            $query->whereHas('stockBalances', function ($sq) {
                $sq->where('holder_type', 'warehouse')
                   ->whereColumn('stock_balances.quantity', '<=', 'inventory_items.reorder_level');
            });
        }

        // Item name/model
        if (!empty($filters['item_search'])) {
            $search = $filters['item_search'];
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'LIKE', "%{$search}%")
                  ->orWhere('item_code', 'LIKE', "%{$search}%")
                  ->orWhere('model', 'LIKE', "%{$search}%")
                  ->orWhere('serial_number', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('item_name');
    }

    public function inventoryBalanceData(Request $request, ?User $user = null): array
    {
        $filters = $this->extractFilters($request);
        $query   = $this->inventoryBalanceQuery($filters, $user);

        return $this->paginateForDataTable($request, $query);
    }

    // ══════════════════════════════════════════════════════════════
    // 8. ROUTER MOVEMENT REPORT
    // ══════════════════════════════════════════════════════════════

    public function routerMovementQuery(array $filters, ?User $user = null): Builder
    {
        $query = StockMovement::with([
            'inventoryItem', 'inventoryItem.jobCategory',
            'fromHolder', 'toHolder', 'ticket', 'performer',
        ])->whereHas('inventoryItem', function ($q) {
            $q->where('item_type', 'router');
        });

        if (!empty($filters['date_from'])) $query->whereDate('movement_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))   $query->whereDate('movement_date', '<=', $filters['date_to']);

        // Terminal ID (serial number)
        if (!empty($filters['terminal_id'])) {
            $query->whereHas('inventoryItem', function ($q) use ($filters) {
                $q->where('serial_number', 'LIKE', '%' . $filters['terminal_id'] . '%');
            });
        }

        // Movement type
        if (!empty($filters['movement_type'])) {
            if (is_array($filters['movement_type'])) {
                $query->whereIn('movement_type', $filters['movement_type']);
            } else {
                $query->where('movement_type', $filters['movement_type']);
            }
        }

        // Merchant (via ticket)
        if (!empty($filters['merchant_name'])) {
            $query->whereHas('ticket', function ($q) use ($filters) {
                $q->where('merchant_name', 'LIKE', '%' . $filters['merchant_name'] . '%');
            });
        }

        // Ticket ID
        if (!empty($filters['ticket_id'])) {
            $query->where('ticket_id', $filters['ticket_id']);
        }

        return $query->orderBy('movement_date', 'desc');
    }

    public function routerMovementData(Request $request, ?User $user = null): array
    {
        $filters = $this->extractFilters($request);
        $query   = $this->routerMovementQuery($filters, $user);

        return $this->paginateForDataTable($request, $query);
    }

    // ══════════════════════════════════════════════════════════════
    // 9. ACCESSORIES USAGE REPORT
    // ══════════════════════════════════════════════════════════════

    public function accessoriesUsageQuery(array $filters, ?User $user = null): Builder
    {
        $query = StockMovement::with([
            'inventoryItem', 'inventoryItem.jobCategory',
            'fromHolder', 'toHolder', 'ticket', 'performer',
        ])->whereHas('inventoryItem', function ($q) {
            $q->where('item_type', 'accessory');
        });

        if (!empty($filters['date_from'])) $query->whereDate('movement_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))   $query->whereDate('movement_date', '<=', $filters['date_to']);

        // Accessory type filter
        if (!empty($filters['accessory_type'])) {
            $query->whereHas('inventoryItem', function ($q) use ($filters) {
                if (is_array($filters['accessory_type'])) {
                    $q->whereIn('accessory_type', $filters['accessory_type']);
                } else {
                    $q->where('accessory_type', $filters['accessory_type']);
                }
            });
        }

        // Usage type (stock_out = used, stock_return = returned)
        if (!empty($filters['usage_type'])) {
            if ($filters['usage_type'] === 'used') {
                $query->where('movement_type', 'stock_out');
            } elseif ($filters['usage_type'] === 'returned') {
                $query->where('movement_type', 'stock_return');
            }
        }

        // Ticket ID
        if (!empty($filters['ticket_id'])) {
            $query->where('ticket_id', $filters['ticket_id']);
        }

        // Technician
        if (!empty($filters['technician_id'])) {
            $ids = is_array($filters['technician_id']) ? $filters['technician_id'] : [$filters['technician_id']];
            $query->where(function ($q) use ($ids) {
                $q->whereIn('from_holder_id', $ids)->orWhereIn('to_holder_id', $ids);
            });
        }

        return $query->orderBy('movement_date', 'desc');
    }

    public function accessoriesUsageData(Request $request, ?User $user = null): array
    {
        $filters = $this->extractFilters($request);
        $query   = $this->accessoriesUsageQuery($filters, $user);

        return $this->paginateForDataTable($request, $query);
    }

    // ══════════════════════════════════════════════════════════════
    // 10. REJECTED / RESCHEDULED REPORT
    // ══════════════════════════════════════════════════════════════

    public function rejectedRescheduledQuery(array $filters, ?User $user = null): Builder
    {
        $query = Ticket::with([
            'vendor', 'state', 'city', 'supervisor', 'technician',
            'jobCategory', 'jobType',
        ]);

        if ($user) {
            $query->visibleTo($user);
        }

        // Type filter — rejected or rescheduled
        if (!empty($filters['report_type'])) {
            if ($filters['report_type'] === 'rejected') {
                $query->where('status', Ticket::STATUS_REJECTED);
            } elseif ($filters['report_type'] === 'rescheduled') {
                $query->whereNotNull('rescheduled_at');
            }
        } else {
            // Default: show both rejected and rescheduled
            $query->where(function ($q) {
                $q->where('status', Ticket::STATUS_REJECTED)
                  ->orWhereNotNull('rescheduled_at');
            });
        }

        if (!empty($filters['date_from'])) $query->whereDate('created_at', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))   $query->whereDate('created_at', '<=', $filters['date_to']);

        // Reason filter
        if (!empty($filters['reason'])) {
            $query->where('reschedule_reason', 'LIKE', '%' . $filters['reason'] . '%');
        }

        $this->applyAssigneeFilter($query, $filters);

        if (!empty($filters['vendor_id'])) {
            $ids = is_array($filters['vendor_id']) ? $filters['vendor_id'] : [$filters['vendor_id']];
            $query->whereIn('vendor_id', $ids);
        }

        if (!empty($filters['job_type_id'])) {
            $ids = is_array($filters['job_type_id']) ? $filters['job_type_id'] : [$filters['job_type_id']];
            $query->whereIn('job_type_id', $ids);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function rejectedRescheduledData(Request $request, ?User $user = null): array
    {
        $filters = $this->extractFilters($request);
        $query   = $this->rejectedRescheduledQuery($filters, $user);

        return $this->paginateForDataTable($request, $query);
    }

    // ══════════════════════════════════════════════════════════════
    // SUMMARY STATISTICS (used by report views for top cards)
    // ══════════════════════════════════════════════════════════════

    public function getTicketSummaryStats(array $filters, ?User $user = null): array
    {
        $baseQuery = Ticket::query();
        if ($user) $baseQuery->visibleTo($user);

        if (!empty($filters['date_from'])) $baseQuery->whereDate('created_at', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))   $baseQuery->whereDate('created_at', '<=', $filters['date_to']);

        return [
            'total'       => (clone $baseQuery)->count(),
            'open'        => (clone $baseQuery)->where('status', 'open')->count(),
            'in_progress' => (clone $baseQuery)->whereIn('status', ['assigned', 'accepted', 'in_progress', 'scheduled'])->count(),
            'completed'   => (clone $baseQuery)->whereIn('status', ['done_success', 'completed', 'closed'])->count(),
            'failed'      => (clone $baseQuery)->where('status', 'done_fail')->count(),
            'rejected'    => (clone $baseQuery)->where('status', 'rejected')->count(),
            'sla_breached'=> (clone $baseQuery)->where('sla_status', 'breached')->count(),
        ];
    }

    public function getClaimSummaryStats(array $filters, ?User $user = null): array
    {
        $baseQuery = Claim::query();
        if ($user) {
            if ($user->hasRole('technician')) {
                $baseQuery->where('technician_id', $user->id);
            } elseif ($user->hasRole('supervisor')) {
                $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
                $teamIds[] = $user->id;
                $baseQuery->whereIn('technician_id', $teamIds);
            }
        }

        if (!empty($filters['date_from'])) $baseQuery->whereDate('claim_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))   $baseQuery->whereDate('claim_date', '<=', $filters['date_to']);

        return [
            'total'           => (clone $baseQuery)->count(),
            'total_amount'    => (clone $baseQuery)->sum('total_amount'),
            'pending'         => (clone $baseQuery)->whereIn('status', ['submitted', 'verified'])->count(),
            'approved'        => (clone $baseQuery)->where('status', 'pending_payment')->count(),
            'paid'            => (clone $baseQuery)->where('status', 'paid')->count(),
            'rejected'        => (clone $baseQuery)->where('status', 'non_claimable')->count(),
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════

    public function extractFilters(Request $request): array
    {
        return [
            'date_from'        => $request->input('date_from'),
            'date_to'          => $request->input('date_to'),
            'state_id'         => $request->input('state_id'),
            'city_id'          => $request->input('city_id'),
            'vendor_id'        => $request->input('vendor_id'),
            'job_category_id'  => $request->input('job_category_id'),
            'job_type_id'      => $request->input('job_type_id'),
            'supervisor_id'    => $request->input('supervisor_id'),
            'technician_id'    => $request->input('technician_id'),
            'status'           => $request->input('status'),
            'merchant_name'    => $request->input('merchant_name'),
            'ticket_no'        => $request->input('ticket_no'),
            'created_by'       => $request->input('created_by'),
            'sla_status'       => $request->input('sla_status'),
            'sla_breach'       => $request->input('sla_breach'),
            'sla_time_range'   => $request->input('sla_time_range'),
            'rescheduled'      => $request->input('rescheduled'),
            'claim_type'       => $request->input('claim_type'),
            'claim_status'     => $request->input('claim_status'),
            'claim_category'   => $request->input('claim_category'),
            'payment_status'   => $request->input('payment_status'),
            'amount_min'       => $request->input('amount_min'),
            'amount_max'       => $request->input('amount_max'),
            'item_type'        => $request->input('item_type'),
            'low_stock'        => $request->input('low_stock'),
            'item_search'      => $request->input('item_search'),
            'terminal_id'      => $request->input('terminal_id'),
            'movement_type'    => $request->input('movement_type'),
            'ticket_id'        => $request->input('ticket_id'),
            'accessory_type'   => $request->input('accessory_type'),
            'usage_type'       => $request->input('usage_type'),
            'report_type'      => $request->input('report_type'),
            'reason'           => $request->input('reason'),
        ];
    }

    protected function applyTicketFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['date_from'])) $query->whereDate('created_at', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))   $query->whereDate('created_at', '<=', $filters['date_to']);

        if (!empty($filters['state_id'])) {
            $ids = is_array($filters['state_id']) ? $filters['state_id'] : [$filters['state_id']];
            $query->whereIn('state_id', $ids);
        }

        if (!empty($filters['city_id'])) {
            $ids = is_array($filters['city_id']) ? $filters['city_id'] : [$filters['city_id']];
            $query->whereIn('city_id', $ids);
        }

        if (!empty($filters['vendor_id'])) {
            $ids = is_array($filters['vendor_id']) ? $filters['vendor_id'] : [$filters['vendor_id']];
            $query->whereIn('vendor_id', $ids);
        }

        if (!empty($filters['job_category_id'])) {
            $ids = is_array($filters['job_category_id']) ? $filters['job_category_id'] : [$filters['job_category_id']];
            $query->whereIn('job_category_id', $ids);
        }

        if (!empty($filters['job_type_id'])) {
            $ids = is_array($filters['job_type_id']) ? $filters['job_type_id'] : [$filters['job_type_id']];
            $query->whereIn('job_type_id', $ids);
        }

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        $this->applyAssigneeFilter($query, $filters);

        if (!empty($filters['merchant_name'])) {
            $query->where('merchant_name', 'LIKE', '%' . $filters['merchant_name'] . '%');
        }

        if (!empty($filters['ticket_no'])) {
            $query->where('ticket_no', 'LIKE', '%' . $filters['ticket_no'] . '%');
        }

        if (!empty($filters['created_by'])) {
            $ids = is_array($filters['created_by']) ? $filters['created_by'] : [$filters['created_by']];
            $query->whereIn('created_by', $ids);
        }
    }

    protected function applyAssigneeFilter(Builder $query, array $filters): void
    {
        if (!empty($filters['supervisor_id'])) {
            $ids = is_array($filters['supervisor_id']) ? $filters['supervisor_id'] : [$filters['supervisor_id']];
            $query->whereIn('supervisor_id', $ids);
        }

        if (!empty($filters['technician_id'])) {
            $ids = is_array($filters['technician_id']) ? $filters['technician_id'] : [$filters['technician_id']];
            $query->whereIn('technician_id', $ids);
        }
    }

    protected function paginateForDataTable(Request $request, Builder $query): array
    {
        $draw    = intval($request->input('draw', 1));
        $start   = intval($request->input('start', 0));
        $length  = intval($request->input('length', 25));

        $totalFiltered = $query->count();

        $data = $query->skip($start)->take($length)->get();

        return [
            'draw'            => $draw,
            'recordsTotal'    => $totalFiltered,
            'recordsFiltered' => $totalFiltered,
            'data'            => $data,
        ];
    }

    /**
     * Get filter dropdown options for views.
     */
    public function getFilterOptions(): array
    {
        return [
            'states'         => \App\Models\State::orderBy('name')->get(['id', 'name']),
            'vendors'        => \App\Models\Vendor::where('status', 'active')->orderBy('vendor_name')->get(['id', 'vendor_name']),
            'job_categories' => \App\Models\JobCategory::where('status', 'active')->orderBy('category_name')->get(['id', 'category_name']),
            'job_types'      => \App\Models\JobType::where('status', 'active')->orderBy('job_title')->get(['id', 'job_title']),
            'supervisors'    => User::role('supervisor')->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'technicians'    => User::role('technician')->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'admins'         => User::role('admin')->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ];
    }
}
