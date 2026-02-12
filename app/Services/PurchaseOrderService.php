<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\NumberSeries;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderService
{
    /**
     * Generate next PO number
     */
    protected function generatePONumber(): string
    {
        $series = NumberSeries::where('series_type', 'PO')
            ->where('is_active', 1)
            ->lockForUpdate()
            ->first();

        if (!$series) {
            $series = NumberSeries::create([
                'series_type' => 'PO',
                'prefix' => 'PO',
                'suffix' => null,
                'current_number' => 0,
                'number_length' => 6,
                'reset_frequency' => 'yearly',
                'is_active' => 1
            ]);
        }

        if ($series->reset_frequency === 'yearly' && $series->last_reset_date) {
            $lastResetYear = date('Y', strtotime($series->last_reset_date));
            $currentYear = date('Y');
            if ($lastResetYear < $currentYear) {
                $series->current_number = 0;
                $series->last_reset_date = now();
            }
        } elseif ($series->reset_frequency === 'monthly' && $series->last_reset_date) {
            $lastResetMonth = date('Y-m', strtotime($series->last_reset_date));
            $currentMonth = date('Y-m');
            if ($lastResetMonth < $currentMonth) {
                $series->current_number = 0;
                $series->last_reset_date = now();
            }
        }

        $series->current_number++;
        $series->save();

        $number = str_pad($series->current_number, $series->number_length, '0', STR_PAD_LEFT);
        $poNumber = $series->prefix . $number;
        
        if ($series->suffix) {
            $poNumber .= $series->suffix;
        }

        return $poNumber;
    }

    /**
     * Create new purchase order
     */
    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $poNumber = $this->generatePONumber();

            $subtotal = 0;
            $taxAmount = 0;

            foreach ($data['lines'] as $line) {
                $lineSubtotal = $line['quantity_ordered'] * $line['unit_price'];
                $lineTax = $lineSubtotal * (($line['tax_rate'] ?? 0) / 100);
                $subtotal += $lineSubtotal;
                $taxAmount += $lineTax;
            }

            $totalAmount = $subtotal + $taxAmount;

            $po = PurchaseOrder::create([
                'po_no' => $poNumber,
                'vendor_id' => $data['vendor_id'],
                'quotation_id' => $data['quotation_id'] ?? null,
                'po_date' => $data['po_date'],
                'delivery_date' => $data['delivery_date'],
                'delivery_address' => $data['delivery_address'],
                'receiving_depot_id' => $data['receiving_depot_id'],
                'currency' => $data['currency'],
                'reference' => $data['reference'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? 'Net 30 days',
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            // FIXED: Add line_no for each line
            $lineNo = 1;
            foreach ($data['lines'] as $lineData) {
                $lineSubtotal = $lineData['quantity_ordered'] * $lineData['unit_price'];
                $discountAmount = $lineSubtotal * (($lineData['discount_percent'] ?? 0) / 100);
                $afterDiscount = $lineSubtotal - $discountAmount;
                $lineTax = $afterDiscount * (($lineData['tax_rate'] ?? 0) / 100);
                $lineTotal = $afterDiscount + $lineTax;

                PurchaseOrderLine::create([
                    'purchase_order_id' => $po->id,
                    'line_no' => $lineNo,  // ADDED THIS!
                    'model_id' => $lineData['model_id'],
                    'description' => $lineData['description'] ?? '',
                    'quantity_ordered' => $lineData['quantity_ordered'],
                    'quantity_received' => 0,
                    'quantity_cancelled' => 0,
                    'unit' => $lineData['unit'],
                    'unit_price' => $lineData['unit_price'],
                    'discount_percent' => $lineData['discount_percent'] ?? 0,
                    'discount_amount' => $discountAmount,
                    'tax_rate' => $lineData['tax_rate'] ?? 0,
                    'tax_amount' => $lineTax,
                    'line_total' => $lineTotal,
                    'remarks' => $lineData['remarks'] ?? null,
                ]);
                
                $lineNo++;
            }

            return $po->fresh(['lines', 'vendor']);
        });
    }

    /**
     * Update purchase order
     */
    public function updatePurchaseOrder(PurchaseOrder $po, array $data): PurchaseOrder
    {
        if (!in_array($po->status, ['draft', 'pending_approval'])) {
            throw new \Exception('Only draft or pending approval POs can be updated');
        }

        return DB::transaction(function () use ($po, $data) {
            $subtotal = 0;
            $taxAmount = 0;

            foreach ($data['lines'] as $line) {
                $lineSubtotal = $line['quantity_ordered'] * $line['unit_price'];
                $lineTax = $lineSubtotal * (($line['tax_rate'] ?? 0) / 100);
                $subtotal += $lineSubtotal;
                $taxAmount += $lineTax;
            }

            $totalAmount = $subtotal + $taxAmount;

            $po->update([
                'vendor_id' => $data['vendor_id'],
                'po_date' => $data['po_date'],
                'delivery_date' => $data['delivery_date'],
                'delivery_address' => $data['delivery_address'],
                'receiving_depot_id' => $data['receiving_depot_id'],
                'currency' => $data['currency'],
                'reference' => $data['reference'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? 'Net 30 days',
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'updated_by' => Auth::id(),
            ]);

            $po->lines()->delete();

            // FIXED: Add line_no
            $lineNo = 1;
            foreach ($data['lines'] as $lineData) {
                $lineSubtotal = $lineData['quantity_ordered'] * $lineData['unit_price'];
                $discountAmount = $lineSubtotal * (($lineData['discount_percent'] ?? 0) / 100);
                $afterDiscount = $lineSubtotal - $discountAmount;
                $lineTax = $afterDiscount * (($lineData['tax_rate'] ?? 0) / 100);
                $lineTotal = $afterDiscount + $lineTax;

                PurchaseOrderLine::create([
                    'purchase_order_id' => $po->id,
                    'line_no' => $lineNo,  // ADDED THIS!
                    'model_id' => $lineData['model_id'],
                    'description' => $lineData['description'] ?? '',
                    'quantity_ordered' => $lineData['quantity_ordered'],
                    'quantity_received' => 0,
                    'quantity_cancelled' => 0,
                    'unit' => $lineData['unit'],
                    'unit_price' => $lineData['unit_price'],
                    'discount_percent' => $lineData['discount_percent'] ?? 0,
                    'discount_amount' => $discountAmount,
                    'tax_rate' => $lineData['tax_rate'] ?? 0,
                    'tax_amount' => $lineTax,
                    'line_total' => $lineTotal,
                    'remarks' => $lineData['remarks'] ?? null,
                ]);
                
                $lineNo++;
            }

            return $po->fresh(['lines', 'vendor']);
        });
    }

    public function submitForApproval(PurchaseOrder $po): void
    {
        if ($po->status !== 'draft') {
            throw new \Exception('Only draft POs can be submitted for approval');
        }

        $po->update([
            'status' => 'pending_approval',
            'submitted_at' => now(),
            'submitted_by' => Auth::id(),
        ]);
    }

    public function approvePurchaseOrder(PurchaseOrder $po, array $data): void
    {
        if ($po->status !== 'pending_approval') {
            throw new \Exception('Only pending POs can be approved');
        }

        $po->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
            'approval_notes' => $data['approval_notes'] ?? null,
        ]);
    }

    public function rejectPurchaseOrder(PurchaseOrder $po, string $reason): void
    {
        if ($po->status !== 'pending_approval') {
            throw new \Exception('Only pending POs can be rejected');
        }

        $po->update([
            'status' => 'draft',
            'rejection_reason' => $reason,
            'rejected_at' => now(),
            'rejected_by' => Auth::id(),
        ]);
    }

    public function sendToVendor(PurchaseOrder $po): void
    {
        if (!in_array($po->status, ['approved', 'sent'])) {
            throw new \Exception('Only approved POs can be sent to vendor');
        }

        $po->update([
            'status' => 'sent',
            'sent_at' => now(),
            'sent_by' => Auth::id(),
        ]);
    }

    public function closePurchaseOrder(PurchaseOrder $po, string $reason): void
    {
        if (!in_array($po->status, ['open', 'partially_received', 'fully_received'])) {
            throw new \Exception('Only open/received POs can be closed');
        }

        $po->update([
            'status' => 'closed',
            'closure_reason' => $reason,
            'closed_at' => now(),
            'closed_by' => Auth::id(),
        ]);
    }

    public function cancelPurchaseOrder(PurchaseOrder $po, string $reason): void
    {
        if (in_array($po->status, ['closed', 'cancelled'])) {
            throw new \Exception('Closed or cancelled POs cannot be cancelled again');
        }

        $po->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
            'cancelled_by' => Auth::id(),
        ]);
    }

    public function getFilteredPurchaseOrders(array $filters, ?User $user = null)
    {
        $query = PurchaseOrder::with(['vendor', 'lines', 'createdBy']);

        if ($user) {
            if ($user->hasRole('Technician')) {
                $query->where('created_by', $user->id);
            } elseif ($user->hasRole('Supervisor')) {
                $teamMemberIds = User::where('supervisor_id', $user->id)
                    ->orWhere('id', $user->id)
                    ->pluck('id');
                $query->whereIn('created_by', $teamMemberIds);
            }
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['vendor_id']) && $filters['vendor_id']) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->whereDate('po_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->whereDate('po_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    public function getStatistics(?User $user = null): array
    {
        $query = PurchaseOrder::query();

        if ($user) {
            if ($user->hasRole('Technician')) {
                $query->where('created_by', $user->id);
            } elseif ($user->hasRole('Supervisor')) {
                $teamMemberIds = User::where('supervisor_id', $user->id)
                    ->orWhere('id', $user->id)
                    ->pluck('id');
                $query->whereIn('created_by', $teamMemberIds);
            }
        }

        return [
            'total' => $query->count(),
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'pending' => (clone $query)->where('status', 'pending_approval')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'sent' => (clone $query)->where('status', 'sent')->count(),
            'open' => (clone $query)->where('status', 'open')->count(),
            'partially_received' => (clone $query)->where('status', 'partially_received')->count(),
            'fully_received' => (clone $query)->where('status', 'fully_received')->count(),
            'closed' => (clone $query)->where('status', 'closed')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
        ];
    }
}
