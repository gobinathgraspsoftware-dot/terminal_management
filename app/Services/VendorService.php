<?php

namespace App\Services;

use App\Models\Vendor;
use App\Models\VendorBranch;
use App\Models\PurchaseOrder;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorService
{
    /**
     * Get vendor statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => Vendor::count(),
            'active' => Vendor::where('status', Vendor::STATUS_ACTIVE)->count(),
            'inactive' => Vendor::where('status', Vendor::STATUS_INACTIVE)->count(),
            'suppliers' => Vendor::where('vendor_type', Vendor::TYPE_SUPPLIER)->count(),
            'subcontractors' => Vendor::where('vendor_type', Vendor::TYPE_SUBCON)->count(),
            'couriers' => Vendor::where('vendor_type', Vendor::TYPE_COURIER)->count(),
            'total_purchase_orders' => PurchaseOrder::count(),
            'active_purchase_orders' => PurchaseOrder::whereNotIn('status', ['closed', 'cancelled'])->count(),
            'total_branches' => VendorBranch::count(),
        ];
    }

    /**
     * Get specific vendor statistics
     */
    public function getVendorStatistics(Vendor $vendor): array
    {
        return [
            'total_pos' => $vendor->purchaseOrders()->count(),
            'active_pos' => $vendor->purchaseOrders()
                ->whereNotIn('status', ['closed', 'cancelled'])
                ->count(),
            'total_grns' => $vendor->grns()->count(),
            'total_invoices' => $vendor->invoices()
                ->where('invoice_type', 'vendor')
                ->count(),
            'pending_invoices' => $vendor->invoices()
                ->where('invoice_type', 'vendor')
                ->where('status', '!=', 'paid')
                ->count(),
            'total_amount_po' => $vendor->purchaseOrders()->sum('total_amount'),
            'outstanding_amount' => $vendor->invoices()
                ->where('invoice_type', 'vendor')
                ->where('status', '!=', 'paid')
                ->sum('total_amount'),
            'total_branches' => $vendor->branches()->count(),
            'active_branches' => $vendor->branches()->where('status', 'active')->count(),
        ];
    }

    /**
     * Get AP aging for vendor
     */
    public function getApAging(Vendor $vendor): array
    {
        $today = now();

        $invoices = $vendor->invoices()
            ->where('invoice_type', 'vendor')
            ->where('status', '!=', 'paid')
            ->get();

        $aging = [
            'current' => 0,
            '1_30' => 0,
            '31_60' => 0,
            '61_90' => 0,
            'over_90' => 0,
            'total' => 0,
        ];

        foreach ($invoices as $invoice) {
            $daysOverdue = $today->diffInDays($invoice->due_date, false);
            $amount = $invoice->total_amount - $invoice->paid_amount;

            if ($daysOverdue >= 0) {
                $aging['current'] += $amount;
            } elseif ($daysOverdue >= -30) {
                $aging['1_30'] += $amount;
            } elseif ($daysOverdue >= -60) {
                $aging['31_60'] += $amount;
            } elseif ($daysOverdue >= -90) {
                $aging['61_90'] += $amount;
            } else {
                $aging['over_90'] += $amount;
            }

            $aging['total'] += $amount;
        }

        return $aging;
    }

    /**
     * Create a new vendor with branches
     */
    public function createVendor(array $data): Vendor
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $branches = $data['branches'] ?? [];
        unset($data['branches']);

        $vendor = Vendor::create($data);

        // Create branches
        $this->syncBranches($vendor, $branches);

        return $vendor->load('branches');
    }

    /**
     * Update a vendor with branches
     */
    public function updateVendor(Vendor $vendor, array $data): Vendor
    {
        $data['updated_by'] = Auth::id();

        $branches = $data['branches'] ?? [];
        unset($data['branches']);

        $vendor->update($data);

        // Sync branches
        $this->syncBranches($vendor, $branches);

        return $vendor->fresh(['branches']);
    }

    /**
     * Sync vendor branches
     * Handles create, update, and delete of branches
     */
    protected function syncBranches(Vendor $vendor, array $branches): void
    {
        $existingIds = $vendor->branches()->pluck('id')->toArray();
        $submittedIds = [];

        foreach ($branches as $index => $branchData) {
            // Clean up branch data
            $branchData = array_intersect_key($branchData, array_flip([
                'id', 'branch_name', 'address', 'city', 'state', 'postcode',
                'country', 'contact_person', 'contact_email', 'contact_phone',
                'is_primary', 'status',
            ]));

            // Set defaults
            $branchData['country'] = $branchData['country'] ?? 'Malaysia';
            $branchData['status'] = $branchData['status'] ?? 'active';
            $branchData['is_primary'] = !empty($branchData['is_primary']) ? true : false;

            if (!empty($branchData['id']) && in_array($branchData['id'], $existingIds)) {
                // Update existing branch
                $branch = VendorBranch::find($branchData['id']);
                if ($branch && $branch->vendor_id === $vendor->id) {
                    unset($branchData['id']);
                    $branch->update($branchData);
                    $submittedIds[] = $branch->id;
                }
            } else {
                // Create new branch
                unset($branchData['id']);
                $branchData['vendor_id'] = $vendor->id;
                $newBranch = VendorBranch::create($branchData);
                $submittedIds[] = $newBranch->id;
            }
        }

        // Delete branches that were removed from the form
        $toDelete = array_diff($existingIds, $submittedIds);
        if (!empty($toDelete)) {
            VendorBranch::whereIn('id', $toDelete)
                ->where('vendor_id', $vendor->id)
                ->delete();
        }

        // Ensure only one primary branch
        $this->ensureSinglePrimary($vendor);
    }

    /**
     * Ensure only one branch is marked as primary
     */
    protected function ensureSinglePrimary(Vendor $vendor): void
    {
        $primaryCount = $vendor->branches()->where('is_primary', true)->count();

        if ($primaryCount === 0 && $vendor->branches()->count() > 0) {
            // Set the first branch as primary
            $vendor->branches()->oldest()->first()->update(['is_primary' => true]);
        } elseif ($primaryCount > 1) {
            // Keep only the latest primary, reset others
            $latestPrimary = $vendor->branches()->where('is_primary', true)->latest()->first();
            $vendor->branches()
                ->where('is_primary', true)
                ->where('id', '!=', $latestPrimary->id)
                ->update(['is_primary' => false]);
        }
    }

    /**
     * Validate bank details
     */
    public function validateBankDetails(array $data): bool
    {
        $bankFields = ['bank_name', 'bank_account_no', 'bank_account_name'];
        $filledFields = array_filter($bankFields, fn($field) => !empty($data[$field]));

        if (count($filledFields) > 0 && count($filledFields) < count($bankFields)) {
            return false;
        }

        return true;
    }

    /**
     * Get vendors by type
     */
    public function getVendorsByType(string $type)
    {
        return Vendor::active()
            ->where('vendor_type', $type)
            ->orderBy('vendor_name')
            ->get();
    }

    /**
     * Get vendor payment history
     */
    public function getPaymentHistory(Vendor $vendor)
    {
        return $vendor->invoices()
            ->where('invoice_type', 'vendor')
            ->where('status', 'paid')
            ->with('payments')
            ->latest()
            ->limit(20)
            ->get();
    }

    /**
     * Calculate vendor performance metrics
     */
    public function getPerformanceMetrics(Vendor $vendor): array
    {
        $totalPOs = $vendor->purchaseOrders()->count();

        if ($totalPOs === 0) {
            return [
                'on_time_delivery_rate' => 0,
                'quality_acceptance_rate' => 0,
                'average_lead_time' => 0,
            ];
        }

        $onTimeDeliveries = $vendor->purchaseOrders()
            ->where('status', 'fully_received')
            ->whereColumn('actual_delivery_date', '<=', 'expected_delivery_date')
            ->count();

        $qualityAcceptance = $vendor->grns()
            ->where('quality_status', 'accepted')
            ->count();

        $totalGRNs = $vendor->grns()->count();

        $avgLeadTime = $vendor->purchaseOrders()
            ->where('status', 'fully_received')
            ->selectRaw('AVG(DATEDIFF(actual_delivery_date, po_date)) as avg_days')
            ->value('avg_days');

        return [
            'on_time_delivery_rate' => $totalPOs > 0 ? round(($onTimeDeliveries / $totalPOs) * 100, 2) : 0,
            'quality_acceptance_rate' => $totalGRNs > 0 ? round(($qualityAcceptance / $totalGRNs) * 100, 2) : 0,
            'average_lead_time' => round($avgLeadTime ?? 0, 1),
        ];
    }

    /**
     * Get vendor's top purchased items
     */
    public function getTopPurchasedItems(Vendor $vendor, int $limit = 10)
    {
        return DB::table('purchase_order_lines as pol')
            ->join('purchase_orders as po', 'pol.purchase_order_id', '=', 'po.id')
            ->join('terminal_models as tm', 'pol.model_id', '=', 'tm.id')
            ->where('po.vendor_id', $vendor->id)
            ->select(
                'tm.id',
                'tm.model_code',
                'tm.model_name',
                DB::raw('SUM(pol.quantity_ordered) as total_quantity'),
                DB::raw('SUM(pol.quantity_ordered * pol.unit_price) as total_value')
            )
            ->groupBy('tm.id', 'tm.model_code', 'tm.model_name')
            ->orderByDesc('total_value')
            ->limit($limit)
            ->get();
    }
}
