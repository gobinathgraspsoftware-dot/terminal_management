<?php

namespace App\Services;

use App\Models\Grn;
use App\Models\GrnLine;
use App\Models\Vendor;
use App\Models\TerminalModel;
use App\Models\Depot;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class GrnReportService
{
    // =========================================================================
    // GRN REGISTER
    // =========================================================================

    /**
     * Get GRN Register data (detailed list of all GRNs with lines)
     */
    public function getGrnRegister(array $filters = [])
    {
        $query = Grn::with(['vendor', 'receivingDepot', 'purchaseOrder', 'createdBy', 'postedBy', 'lines.model'])
            ->select('grns.*');

        $this->applyCommonFilters($query, $filters);
        $this->applyRoleScoping($query, $filters);

        return $query->orderBy('grn_date', 'desc')->orderBy('grn_no', 'desc');
    }

    /**
     * Get GRN Register summary totals
     */
    public function getGrnRegisterSummary(array $filters = []): array
    {
        $query = Grn::query();
        $this->applyCommonFilters($query, $filters);
        $this->applyRoleScoping($query, $filters);

        $totalGrns = (clone $query)->count();
        $draftGrns = (clone $query)->where('status', 'draft')->count();
        $postedGrns = (clone $query)->where('status', 'posted')->count();
        $cancelledGrns = (clone $query)->where('status', 'cancelled')->count();
        $totalItems = (clone $query)->sum('total_items');

        // Total value from lines
        $totalValue = GrnLine::whereIn('grn_id', (clone $query)->pluck('id'))
            ->sum('line_total');

        return [
            'total_grns'     => $totalGrns,
            'draft_grns'     => $draftGrns,
            'posted_grns'    => $postedGrns,
            'cancelled_grns' => $cancelledGrns,
            'total_items'    => $totalItems,
            'total_value'    => $totalValue,
        ];
    }

    // =========================================================================
    // RECEIVING SUMMARY BY VENDOR
    // =========================================================================

    /**
     * Get Receiving Summary grouped by Vendor
     */
    public function getReceivingSummaryByVendor(array $filters = [])
    {
        $query = GrnLine::query()
            ->join('grns', 'grn_lines.grn_id', '=', 'grns.id')
            ->join('vendors', 'grns.vendor_id', '=', 'vendors.id')
            ->whereNull('grns.deleted_at');

        // Default to posted GRNs only for reports
        if (empty($filters['status'])) {
            $query->where('grns.status', 'posted');
        } else {
            $query->where('grns.status', $filters['status']);
        }

        $this->applyDateFilters($query, $filters, 'grns.grn_date');
        $this->applyVendorFilter($query, $filters);
        $this->applyDepotFilter($query, $filters, 'grns.receiving_depot_id');

        // Role-based scoping on GRN
        if (!empty($filters['role']) && $filters['role'] !== 'admin') {
            $this->applyRoleScopingToJoinedQuery($query, $filters, 'grns.created_by');
        }

        return $query->select([
                'vendors.id as vendor_id',
                'vendors.vendor_name',
                'vendors.company_name',
                DB::raw('COUNT(DISTINCT grns.id) as grn_count'),
                DB::raw('SUM(grn_lines.quantity_received) as total_qty_received'),
                DB::raw('SUM(grn_lines.line_total) as total_value'),
                DB::raw('COUNT(DISTINCT grn_lines.model_id) as model_count'),
            ])
            ->groupBy('vendors.id', 'vendors.vendor_name', 'vendors.company_name')
            ->orderBy('total_value', 'desc')
            ->get();
    }

    /**
     * Get detailed breakdown for a specific vendor
     */
    public function getVendorReceivingDetail(int $vendorId, array $filters = [])
    {
        $query = GrnLine::query()
            ->join('grns', 'grn_lines.grn_id', '=', 'grns.id')
            ->join('terminal_models', 'grn_lines.model_id', '=', 'terminal_models.id')
            ->where('grns.vendor_id', $vendorId)
            ->whereNull('grns.deleted_at');

        if (empty($filters['status'])) {
            $query->where('grns.status', 'posted');
        } else {
            $query->where('grns.status', $filters['status']);
        }

        $this->applyDateFilters($query, $filters, 'grns.grn_date');

        return $query->select([
                'grns.grn_no',
                'grns.grn_date',
                'terminal_models.model_name',
                'grn_lines.quantity_received',
                'grn_lines.unit_cost',
                'grn_lines.line_total',
            ])
            ->orderBy('grns.grn_date', 'desc')
            ->get();
    }

    // =========================================================================
    // RECEIVING SUMMARY BY MODEL
    // =========================================================================

    /**
     * Get Receiving Summary grouped by Terminal Model
     */
    public function getReceivingSummaryByModel(array $filters = [])
    {
        $query = GrnLine::query()
            ->join('grns', 'grn_lines.grn_id', '=', 'grns.id')
            ->join('terminal_models', 'grn_lines.model_id', '=', 'terminal_models.id')
            ->leftJoin('terminal_categories', 'terminal_models.category_id', '=', 'terminal_categories.id')
            ->whereNull('grns.deleted_at');

        if (empty($filters['status'])) {
            $query->where('grns.status', 'posted');
        } else {
            $query->where('grns.status', $filters['status']);
        }

        $this->applyDateFilters($query, $filters, 'grns.grn_date');
        $this->applyVendorFilter($query, $filters);
        $this->applyDepotFilter($query, $filters, 'grns.receiving_depot_id');

        if (!empty($filters['category_id'])) {
            $query->where('terminal_models.category_id', $filters['category_id']);
        }

        if (!empty($filters['model_id'])) {
            $query->where('grn_lines.model_id', $filters['model_id']);
        }

        // Role-based scoping
        if (!empty($filters['role']) && $filters['role'] !== 'admin') {
            $this->applyRoleScopingToJoinedQuery($query, $filters, 'grns.created_by');
        }

        return $query->select([
                'terminal_models.id as model_id',
                'terminal_models.model_name',
                'terminal_categories.category_name',
                DB::raw('COUNT(DISTINCT grns.id) as grn_count'),
                DB::raw('COUNT(DISTINCT grns.vendor_id) as vendor_count'),
                DB::raw('SUM(grn_lines.quantity_received) as total_qty_received'),
                DB::raw('AVG(grn_lines.unit_cost) as avg_unit_cost'),
                DB::raw('SUM(grn_lines.line_total) as total_value'),
            ])
            ->groupBy('terminal_models.id', 'terminal_models.model_name', 'terminal_categories.category_name')
            ->orderBy('total_qty_received', 'desc')
            ->get();
    }

    // =========================================================================
    // FILTER HELPERS
    // =========================================================================

    /**
     * Apply common filters to GRN query
     */
    protected function applyCommonFilters($query, array $filters): void
    {
        if (!empty($filters['date_from'])) {
            $query->whereDate('grns.grn_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('grns.grn_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['vendor_id'])) {
            $query->where('grns.vendor_id', $filters['vendor_id']);
        }

        if (!empty($filters['purchase_order_id'])) {
            $query->where('grns.purchase_order_id', $filters['purchase_order_id']);
        }

        if (!empty($filters['receiving_depot_id'])) {
            $query->where('grns.receiving_depot_id', $filters['receiving_depot_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('grns.status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('grns.grn_no', 'like', "%{$search}%")
                  ->orWhere('grns.delivery_note_no', 'like', "%{$search}%");
            });
        }
    }

    /**
     * Apply date filters to joined queries
     */
    protected function applyDateFilters($query, array $filters, string $dateColumn = 'grns.grn_date'): void
    {
        if (!empty($filters['date_from'])) {
            $query->whereDate($dateColumn, '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate($dateColumn, '<=', $filters['date_to']);
        }
    }

    /**
     * Apply vendor filter to joined queries
     */
    protected function applyVendorFilter($query, array $filters): void
    {
        if (!empty($filters['vendor_id'])) {
            $query->where('grns.vendor_id', $filters['vendor_id']);
        }
    }

    /**
     * Apply depot filter
     */
    protected function applyDepotFilter($query, array $filters, string $column = 'grns.receiving_depot_id'): void
    {
        if (!empty($filters['receiving_depot_id'])) {
            $query->where($column, $filters['receiving_depot_id']);
        }
    }

    /**
     * Apply role-based scoping for main GRN queries
     */
    protected function applyRoleScoping($query, array $filters): void
    {
        if (empty($filters['user'])) {
            return;
        }

        $user = $filters['user'];
        $role = $filters['role'] ?? null;

        if ($role === 'supervisor') {
            // Supervisor sees team GRNs
            $teamUserIds = \App\Models\User::where('team_id', $user->team_id)->pluck('id')->toArray();
            $query->whereIn('grns.created_by', $teamUserIds);
        } elseif ($role === 'technician') {
            // Technician sees own GRNs only
            $query->where('grns.created_by', $user->id);
        }
    }

    /**
     * Apply role-based scoping for joined queries
     */
    protected function applyRoleScopingToJoinedQuery($query, array $filters, string $createdByColumn): void
    {
        if (empty($filters['user'])) {
            return;
        }

        $user = $filters['user'];
        $role = $filters['role'] ?? null;

        if ($role === 'supervisor') {
            $teamUserIds = \App\Models\User::where('team_id', $user->team_id)->pluck('id')->toArray();
            $query->whereIn($createdByColumn, $teamUserIds);
        } elseif ($role === 'technician') {
            $query->where($createdByColumn, $user->id);
        }
    }

    // =========================================================================
    // FILTER OPTIONS
    // =========================================================================

    /**
     * Get filter options for GRN reports
     */
    public function getFilterOptions(): array
    {
        return [
            'vendors'         => Vendor::orderBy('vendor_name')->get(['id', 'vendor_name', 'company_name']),
            'depots'          => Depot::where('status', 'active')->orderBy('depot_name')->get(['id', 'depot_name']),
            'purchase_orders' => PurchaseOrder::orderBy('po_no', 'desc')->get(['id', 'po_no']),
            'models'          => TerminalModel::with('category')->orderBy('model_name')->get(['id', 'model_name', 'category_id']),
            'categories'      => \App\Models\TerminalCategory::orderBy('category_name')->get(['id', 'category_name']),
        ];
    }
}
