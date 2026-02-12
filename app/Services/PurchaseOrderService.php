<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Quotation;
use App\Models\NumberSeries;
use App\Models\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class PurchaseOrderService
{
    /**
     * Get all purchase orders with filters
     */
    public function getFilteredPurchaseOrders($filters = [], $user = null)
    {
        $query = PurchaseOrder::with(['vendor', 'quotation', 'receivingDepot']);

        // Role-based scoping
        if ($user && $user->hasRole('supervisor')) {
            // Supervisors see their team's POs
            $query->where('created_by', $user->id)
                  ->orWhereHas('createdBy', function($q) use ($user) {
                      $q->where('supervisor_id', $user->id);
                  });
        } elseif ($user && $user->hasRole('technician')) {
            // Technicians see only their own POs
            $query->where('created_by', $user->id);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Vendor filter
        if (!empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->whereDate('po_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('po_date', '<=', $filters['date_to']);
        }

        return $query->latest('po_date');
    }

    /**
     * Create a new purchase order
     */
    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        DB::beginTransaction();
        try {
            // Generate PO Number
            $data['po_no'] = $this->generatePONumber();
            $data['status'] = PurchaseOrder::STATUS_DRAFT;
            $data['created_by'] = Auth::id();
            $data['currency'] = $data['currency'] ?? 'MYR';

            // Calculate totals
            $totals = $this->calculateTotals($data['lines'] ?? []);
            $data['subtotal'] = $totals['subtotal'];
            $data['tax_amount'] = $totals['tax_amount'];
            $data['total_amount'] = $totals['total_amount'];

            // Create PO
            $po = PurchaseOrder::create($data);

            // Create PO Lines
            if (!empty($data['lines'])) {
                $this->createPOLines($po, $data['lines']);
            }

            // Audit trail
            $this->logAudit($po, 'created', 'Purchase Order created');

            DB::commit();
            return $po->fresh(['lines', 'vendor']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create PO from Quotation
     */
    public function createFromQuotation(Quotation $quotation, array $additionalData = []): PurchaseOrder
    {
        DB::beginTransaction();
        try {
            // Prepare PO data from quotation
            $data = [
                'po_no' => $this->generatePONumber(),
                'po_date' => now(),
                'vendor_id' => $quotation->vendor_id,
                'quotation_id' => $quotation->id,
                'reference' => $quotation->quotation_no,
                'delivery_address' => $additionalData['delivery_address'] ?? '',
                'delivery_date' => $additionalData['delivery_date'] ?? null,
                'receiving_depot_id' => $additionalData['receiving_depot_id'] ?? null,
                'payment_terms' => $additionalData['payment_terms'] ?? $quotation->payment_terms,
                'terms_conditions' => $additionalData['terms_conditions'] ?? $quotation->terms_conditions,
                'notes' => $additionalData['notes'] ?? '',
                'status' => PurchaseOrder::STATUS_DRAFT,
                'currency' => $quotation->currency,
                'created_by' => Auth::id(),
            ];

            // Get quotation lines
            $quotationLines = $quotation->lines()->with('model')->get();
            $lines = [];

            foreach ($quotationLines as $index => $qLine) {
                $lines[] = [
                    'line_no' => $index + 1,
                    'model_id' => $qLine->model_id,
                    'description' => $qLine->description,
                    'quantity_ordered' => $qLine->quantity,
                    'unit' => $qLine->unit ?? 'pcs',
                    'unit_price' => $qLine->unit_price,
                    'discount_percent' => $qLine->discount_percent ?? 0,
                    'discount_amount' => $qLine->discount_amount ?? 0,
                    'tax_rate' => $qLine->tax_rate ?? 0,
                    'tax_amount' => $qLine->tax_amount ?? 0,
                    'line_total' => $qLine->line_total,
                ];
            }

            $data['lines'] = $lines;

            // Calculate totals
            $totals = $this->calculateTotals($lines);
            $data['subtotal'] = $totals['subtotal'];
            $data['tax_amount'] = $totals['tax_amount'];
            $data['total_amount'] = $totals['total_amount'];

            // Create PO
            $po = PurchaseOrder::create($data);

            // Create lines
            $this->createPOLines($po, $lines);

            // Update quotation status
            $quotation->update(['status' => Quotation::STATUS_CONVERTED]);

            // Audit trail
            $this->logAudit($po, 'created', 'PO created from Quotation: ' . $quotation->quotation_no);

            DB::commit();
            return $po->fresh(['lines', 'vendor', 'quotation']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update purchase order
     */
    public function updatePurchaseOrder(PurchaseOrder $po, array $data): PurchaseOrder
    {
        DB::beginTransaction();
        try {
            // Only draft and rejected POs can be edited
            if (!in_array($po->status, [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_PENDING_APPROVAL])) {
                throw new Exception('Only draft or pending approval purchase orders can be edited.');
            }

            $data['updated_by'] = Auth::id();

            // Calculate totals
            if (!empty($data['lines'])) {
                $totals = $this->calculateTotals($data['lines']);
                $data['subtotal'] = $totals['subtotal'];
                $data['tax_amount'] = $totals['tax_amount'];
                $data['total_amount'] = $totals['total_amount'];

                // Delete existing lines and recreate
                $po->lines()->delete();
                $this->createPOLines($po, $data['lines']);
            }

            $po->update($data);

            // Audit trail
            $this->logAudit($po, 'updated', 'Purchase Order updated');

            DB::commit();
            return $po->fresh(['lines', 'vendor']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Submit for approval
     */
    public function submitForApproval(PurchaseOrder $po): PurchaseOrder
    {
        DB::beginTransaction();
        try {
            if ($po->status !== PurchaseOrder::STATUS_DRAFT) {
                throw new Exception('Only draft purchase orders can be submitted for approval.');
            }

            $po->update([
                'status' => PurchaseOrder::STATUS_PENDING_APPROVAL,
                'updated_by' => Auth::id(),
            ]);

            $this->logAudit($po, 'submitted', 'PO submitted for approval');

            DB::commit();
            return $po;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve purchase order
     */
    public function approvePurchaseOrder(PurchaseOrder $po, array $data): PurchaseOrder
    {
        DB::beginTransaction();
        try {
            if ($po->status !== PurchaseOrder::STATUS_PENDING_APPROVAL) {
                throw new Exception('Only pending approval purchase orders can be approved.');
            }

            $po->update([
                'status' => PurchaseOrder::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'notes' => ($po->notes ? $po->notes . "\n" : '') . ($data['approval_notes'] ?? ''),
            ]);

            $this->logAudit($po, 'approved', 'PO approved: ' . ($data['approval_notes'] ?? ''));

            DB::commit();
            return $po;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject purchase order
     */
    public function rejectPurchaseOrder(PurchaseOrder $po, string $reason): PurchaseOrder
    {
        DB::beginTransaction();
        try {
            if ($po->status !== PurchaseOrder::STATUS_PENDING_APPROVAL) {
                throw new Exception('Only pending approval purchase orders can be rejected.');
            }

            $po->update([
                'status' => PurchaseOrder::STATUS_DRAFT,
                'notes' => ($po->notes ? $po->notes . "\n" : '') . 'Rejected: ' . $reason,
                'updated_by' => Auth::id(),
            ]);

            $this->logAudit($po, 'rejected', 'PO rejected: ' . $reason);

            DB::commit();
            return $po;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Send to vendor
     */
    public function sendToVendor(PurchaseOrder $po): PurchaseOrder
    {
        DB::beginTransaction();
        try {
            if ($po->status !== PurchaseOrder::STATUS_APPROVED) {
                throw new Exception('Only approved purchase orders can be sent to vendor.');
            }

            $po->update([
                'status' => PurchaseOrder::STATUS_SENT,
                'sent_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->logAudit($po, 'sent', 'PO sent to vendor');

            DB::commit();
            return $po;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Close purchase order
     */
    public function closePurchaseOrder(PurchaseOrder $po, string $reason): PurchaseOrder
    {
        DB::beginTransaction();
        try {
            if (!in_array($po->status, [
                PurchaseOrder::STATUS_SENT,
                PurchaseOrder::STATUS_OPEN,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ])) {
                throw new Exception('This purchase order cannot be closed.');
            }

            $po->update([
                'status' => PurchaseOrder::STATUS_CLOSED,
                'closed_by' => Auth::id(),
                'closed_at' => now(),
                'notes' => ($po->notes ? $po->notes . "\n" : '') . 'Closed: ' . $reason,
            ]);

            $this->logAudit($po, 'closed', 'PO closed: ' . $reason);

            DB::commit();
            return $po;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel purchase order
     */
    public function cancelPurchaseOrder(PurchaseOrder $po, string $reason): PurchaseOrder
    {
        DB::beginTransaction();
        try {
            if (!in_array($po->status, [
                PurchaseOrder::STATUS_DRAFT,
                PurchaseOrder::STATUS_PENDING_APPROVAL,
                PurchaseOrder::STATUS_APPROVED,
            ])) {
                throw new Exception('This purchase order cannot be cancelled.');
            }

            $po->update([
                'status' => PurchaseOrder::STATUS_CANCELLED,
                'notes' => ($po->notes ? $po->notes . "\n" : '') . 'Cancelled: ' . $reason,
                'updated_by' => Auth::id(),
            ]);

            // Cancel all line quantities
            $po->lines()->update(['quantity_cancelled' => DB::raw('quantity_ordered - quantity_received')]);

            $this->logAudit($po, 'cancelled', 'PO cancelled: ' . $reason);

            DB::commit();
            return $po;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate PO Number
     */
    protected function generatePONumber(): string
    {
        $series = NumberSeries::where('document_type', 'PO')
            ->where('is_active', true)
            ->first();

        if (!$series) {
            // Fallback: generate based on year and sequence
            $year = date('Y');
            $count = PurchaseOrder::whereYear('created_at', $year)->count() + 1;
            return 'PO-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
        }

        return $series->getNextNumber();
    }

    /**
     * Create PO lines
     */
    protected function createPOLines(PurchaseOrder $po, array $lines): void
    {
        foreach ($lines as $lineData) {
            $po->lines()->create([
                'line_no' => $lineData['line_no'],
                'model_id' => $lineData['model_id'],
                'description' => $lineData['description'] ?? '',
                'quantity_ordered' => $lineData['quantity_ordered'],
                'quantity_received' => 0,
                'quantity_cancelled' => 0,
                'unit' => $lineData['unit'] ?? 'pcs',
                'unit_price' => $lineData['unit_price'],
                'discount_percent' => $lineData['discount_percent'] ?? 0,
                'discount_amount' => $lineData['discount_amount'] ?? 0,
                'tax_rate' => $lineData['tax_rate'] ?? 0,
                'tax_amount' => $lineData['tax_amount'] ?? 0,
                'line_total' => $lineData['line_total'],
                'remarks' => $lineData['remarks'] ?? '',
            ]);
        }
    }

    /**
     * Calculate totals from lines
     */
    protected function calculateTotals(array $lines): array
    {
        $subtotal = 0;
        $taxAmount = 0;

        foreach ($lines as $line) {
            $qty = $line['quantity_ordered'] ?? 0;
            $price = $line['unit_price'] ?? 0;
            $discountPercent = $line['discount_percent'] ?? 0;
            $taxRate = $line['tax_rate'] ?? 0;

            $lineSubtotal = $qty * $price;
            $lineDiscount = $lineSubtotal * ($discountPercent / 100);
            $lineAfterDiscount = $lineSubtotal - $lineDiscount;
            $lineTax = $lineAfterDiscount * ($taxRate / 100);

            $subtotal += $lineAfterDiscount;
            $taxAmount += $lineTax;
        }

        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($subtotal + $taxAmount, 2),
        ];
    }

    /**
     * Log audit trail
     */
    protected function logAudit(PurchaseOrder $po, string $action, string $description): void
    {
        AuditTrail::create([
            'user_id' => Auth::id(),
            'model_type' => PurchaseOrder::class,
            'model_id' => $po->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Get PO statistics
     */
    public function getStatistics($user = null): array
    {
        $query = PurchaseOrder::query();

        // Role-based scoping
        if ($user && $user->hasRole('supervisor')) {
            $query->where('created_by', $user->id)
                  ->orWhereHas('createdBy', function($q) use ($user) {
                      $q->where('supervisor_id', $user->id);
                  });
        } elseif ($user && $user->hasRole('technician')) {
            $query->where('created_by', $user->id);
        }

        return [
            'total' => $query->count(),
            'draft' => (clone $query)->where('status', PurchaseOrder::STATUS_DRAFT)->count(),
            'pending' => (clone $query)->where('status', PurchaseOrder::STATUS_PENDING_APPROVAL)->count(),
            'approved' => (clone $query)->where('status', PurchaseOrder::STATUS_APPROVED)->count(),
            'sent' => (clone $query)->where('status', PurchaseOrder::STATUS_SENT)->count(),
            'open' => (clone $query)->where('status', PurchaseOrder::STATUS_OPEN)->count(),
            'partially_received' => (clone $query)->where('status', PurchaseOrder::STATUS_PARTIALLY_RECEIVED)->count(),
            'fully_received' => (clone $query)->where('status', PurchaseOrder::STATUS_FULLY_RECEIVED)->count(),
            'closed' => (clone $query)->where('status', PurchaseOrder::STATUS_CLOSED)->count(),
            'cancelled' => (clone $query)->where('status', PurchaseOrder::STATUS_CANCELLED)->count(),
        ];
    }
}
