<?php

namespace App\Services;

use App\Models\Grn;
use App\Models\GrnLine;
use App\Models\GrnSerial;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\InventorySerial;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\NumberSeries;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class GrnService
{
    /**
     * Get Purchase Orders with outstanding items
     */
    public function getOutstandingPurchaseOrders()
    {
        return PurchaseOrder::whereIn('status', [
            PurchaseOrder::STATUS_APPROVED,
            PurchaseOrder::STATUS_SENT,
            PurchaseOrder::STATUS_OPEN,
            PurchaseOrder::STATUS_PARTIALLY_RECEIVED
        ])
        ->with(['vendor', 'receivingDepot'])
        ->orderBy('po_date', 'desc')
        ->get();
    }

    /**
     * Get Purchase Order details with outstanding lines
     */
    public function getPurchaseOrderDetails($poId)
    {
        $po = PurchaseOrder::with([
            'vendor',
            'receivingDepot',
            'lines' => function($query) {
                $query->whereRaw('quantity_ordered > (quantity_received + quantity_cancelled)')
                      ->with('model.category');
            }
        ])->findOrFail($poId);

        return $po;
    }

    /**
     * Create new GRN
     */
    public function createGrn(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Generate GRN number
            $grnNo = $this->generateGrnNumber();

            // Get PO details
            $po = PurchaseOrder::with('vendor')->findOrFail($data['purchase_order_id']);

            // Create GRN header
            $grn = Grn::create([
                'grn_no' => $grnNo,
                'grn_date' => $data['grn_date'],
                'purchase_order_id' => $data['purchase_order_id'],
                'vendor_id' => $po->vendor_id,
                'receiving_depot_id' => $data['receiving_depot_id'] ?? $po->receiving_depot_id,
                'delivery_note_no' => $data['delivery_note_no'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'status' => Grn::STATUS_DRAFT,
                'total_items' => 0,
                'created_by' => Auth::id(),
            ]);

            // Create GRN lines
            $totalItems = 0;
            foreach ($data['lines'] as $index => $lineData) {
                if (empty($lineData['quantity_received']) || $lineData['quantity_received'] <= 0) {
                    continue;
                }

                $poLine = PurchaseOrderLine::findOrFail($lineData['po_line_id']);
                
                $grnLine = GrnLine::create([
                    'grn_id' => $grn->id,
                    'line_no' => $index + 1,
                    'po_line_id' => $lineData['po_line_id'],
                    'model_id' => $poLine->model_id,
                    'description' => $poLine->description,
                    'quantity_received' => $lineData['quantity_received'],
                    'unit' => $poLine->unit,
                    'unit_cost' => $poLine->unit_price,
                    'line_total' => $lineData['quantity_received'] * $poLine->unit_price,
                    'remarks' => $lineData['remarks'] ?? null,
                ]);

                $totalItems += $lineData['quantity_received'];

                // Handle serials if provided
                if (!empty($lineData['serials'])) {
                    $this->processSerials($grnLine, $lineData['serials'], $grn->receiving_depot_id);
                }
            }

            // Update total items
            $grn->update(['total_items' => $totalItems]);

            return $grn;
        });
    }

    /**
     * Update GRN (only if status is draft)
     */
    public function updateGrn(Grn $grn, array $data)
    {
        if ($grn->status !== Grn::STATUS_DRAFT) {
            throw new Exception('Only draft GRNs can be updated');
        }

        return DB::transaction(function () use ($grn, $data) {
            // Update header
            $grn->update([
                'grn_date' => $data['grn_date'],
                'receiving_depot_id' => $data['receiving_depot_id'],
                'delivery_note_no' => $data['delivery_note_no'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            // Delete existing lines and serials
            foreach ($grn->lines as $line) {
                $line->serials()->delete();
                $line->delete();
            }

            // Recreate lines
            $totalItems = 0;
            foreach ($data['lines'] as $index => $lineData) {
                if (empty($lineData['quantity_received']) || $lineData['quantity_received'] <= 0) {
                    continue;
                }

                $poLine = PurchaseOrderLine::findOrFail($lineData['po_line_id']);
                
                $grnLine = GrnLine::create([
                    'grn_id' => $grn->id,
                    'line_no' => $index + 1,
                    'po_line_id' => $lineData['po_line_id'],
                    'model_id' => $poLine->model_id,
                    'description' => $poLine->description,
                    'quantity_received' => $lineData['quantity_received'],
                    'unit' => $poLine->unit,
                    'unit_cost' => $poLine->unit_price,
                    'line_total' => $lineData['quantity_received'] * $poLine->unit_price,
                    'remarks' => $lineData['remarks'] ?? null,
                ]);

                $totalItems += $lineData['quantity_received'];

                if (!empty($lineData['serials'])) {
                    $this->processSerials($grnLine, $lineData['serials'], $grn->receiving_depot_id);
                }
            }

            $grn->update(['total_items' => $totalItems]);

            return $grn->fresh();
        });
    }

    /**
     * Post GRN (create inventory and update stock)
     */
    public function postGrn(Grn $grn)
    {
        if ($grn->status !== Grn::STATUS_DRAFT) {
            throw new Exception('Only draft GRNs can be posted');
        }

        return DB::transaction(function () use ($grn) {
            $grn->load(['lines.model', 'lines.serials']);

            foreach ($grn->lines as $line) {
                $poLine = $line->poLine;

                // Update PO line received quantity
                $poLine->increment('quantity_received', $line->quantity_received);

                // Update stock balance
                $this->updateStockBalance(
                    $line->model_id,
                    $grn->receiving_depot_id,
                    $line->quantity_received,
                    $line->unit_cost
                );

                // Create stock ledger entry
                $this->createStockLedgerEntry(
                    $line->model_id,
                    $grn->receiving_depot_id,
                    'grn',
                    $grn->id,
                    $line->quantity_received,
                    0,
                    "GRN Receipt: {$grn->grn_no}"
                );

                // Link serials to inventory
                foreach ($line->serials as $serial) {
                    $inventorySerial = InventorySerial::where('serial_no', $serial->serial_no)->first();
                    
                    if (!$inventorySerial) {
                        $inventorySerial = InventorySerial::create([
                            'serial_no' => $serial->serial_no,
                            'model_id' => $line->model_id,
                            'current_depot_id' => $grn->receiving_depot_id,
                            'current_status' => 'in_stock',
                            'hardware_type' => $serial->hardware_type,
                            'device_type' => $serial->device_type,
                            'telco' => $serial->telco,
                            'sim_quota' => $serial->sim_quota,
                            'warranty_start' => $serial->warranty_start,
                            'warranty_end' => $serial->warranty_end,
                            'created_by' => Auth::id(),
                        ]);
                    }

                    // Link serial to GRN
                    $serial->update(['serial_id' => $inventorySerial->id]);
                }
            }

            // Update PO status
            $po = $grn->purchaseOrder;
            $allFullyReceived = $po->lines->every(function($line) {
                return $line->quantity_ordered <= ($line->quantity_received + $line->quantity_cancelled);
            });

            if ($allFullyReceived) {
                $po->update(['status' => PurchaseOrder::STATUS_FULLY_RECEIVED]);
            } else {
                $po->update(['status' => PurchaseOrder::STATUS_PARTIALLY_RECEIVED]);
            }

            // Update GRN status
            $grn->update([
                'status' => Grn::STATUS_POSTED,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
            ]);

            return $grn;
        });
    }

    /**
     * Cancel GRN
     */
    public function cancelGrn(Grn $grn)
    {
        if ($grn->status !== Grn::STATUS_DRAFT) {
            throw new Exception('Only draft GRNs can be cancelled');
        }

        return DB::transaction(function () use ($grn) {
            $grn->update(['status' => Grn::STATUS_CANCELLED]);
            return $grn;
        });
    }

    /**
     * Process serials for a GRN line
     */
    private function processSerials(GrnLine $grnLine, array $serialsData, $depotId)
    {
        foreach ($serialsData as $index => $serialData) {
            if (empty($serialData['serial_no'])) {
                continue;
            }

            GrnSerial::create([
                'grn_line_id' => $grnLine->id,
                'serial_no' => $serialData['serial_no'],
                'hardware_type' => $serialData['hardware_type'] ?? null,
                'device_type' => $serialData['device_type'] ?? null,
                'telco' => $serialData['telco'] ?? null,
                'sim_quota' => $serialData['sim_quota'] ?? null,
                'warranty_start' => $serialData['warranty_start'] ?? null,
                'warranty_end' => $serialData['warranty_end'] ?? null,
                'remarks' => $serialData['remarks'] ?? null,
            ]);
        }
    }

    /**
     * Update stock balance
     */
    private function updateStockBalance($modelId, $depotId, $quantity, $unitCost)
    {
        $balance = StockBalance::firstOrCreate(
            [
                'model_id' => $modelId,
                'depot_id' => $depotId,
            ],
            [
                'quantity' => 0,
                'average_cost' => 0,
                'total_value' => 0,
            ]
        );

        $oldQuantity = $balance->quantity;
        $oldValue = $balance->total_value;

        $newQuantity = $oldQuantity + $quantity;
        $newValue = $oldValue + ($quantity * $unitCost);
        $newAvgCost = $newQuantity > 0 ? $newValue / $newQuantity : 0;

        $balance->update([
            'quantity' => $newQuantity,
            'average_cost' => $newAvgCost,
            'total_value' => $newValue,
            'last_purchase_cost' => $unitCost,
            'last_received_at' => now(),
        ]);
    }

    /**
     * Create stock ledger entry
     */
    private function createStockLedgerEntry($modelId, $depotId, $transactionType, $transactionId, $qtyIn, $qtyOut, $reference)
    {
        StockLedger::create([
            'model_id' => $modelId,
            'depot_id' => $depotId,
            'transaction_type' => $transactionType,
            'transaction_id' => $transactionId,
            'transaction_date' => now(),
            'quantity_in' => $qtyIn,
            'quantity_out' => $qtyOut,
            'reference' => $reference,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Generate GRN number
     */
    private function generateGrnNumber()
    {
        return DB::transaction(function () {
            $series = NumberSeries::where('series_type', 'grn')
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$series) {
                throw new Exception('No active number series found for GRN');
            }

            $number = $series->current_number + 1;
            $grnNo = $series->prefix . str_pad($number, $series->number_length, '0', STR_PAD_LEFT);
            if ($series->suffix) {
                $grnNo .= $series->suffix;
            }

            $series->update(['current_number' => $number]);

            return $grnNo;
        });
    }

    /**
     * Validate serial uniqueness
     */
    public function validateSerialUniqueness($serialNo, $excludeGrnLineId = null)
    {
        // Check in inventory_serials
        $existsInInventory = InventorySerial::where('serial_no', $serialNo)->exists();
        
        if ($existsInInventory) {
            return [
                'valid' => false,
                'message' => 'Serial number already exists in inventory'
            ];
        }

        // Check in unposted GRN serials
        $query = GrnSerial::where('serial_no', $serialNo);
        
        if ($excludeGrnLineId) {
            $query->where('grn_line_id', '!=', $excludeGrnLineId);
        }

        $existsInGrn = $query->exists();

        if ($existsInGrn) {
            return [
                'valid' => false,
                'message' => 'Serial number already exists in another GRN'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Serial number is available'
        ];
    }

    /**
     * Parse multiple serials (comma or line separated)
     */
    public function parseMultipleSerials($input)
    {
        // Split by comma or newline
        $serials = preg_split('/[,\n\r]+/', $input);
        
        // Clean and filter
        $serials = array_map('trim', $serials);
        $serials = array_filter($serials);
        $serials = array_unique($serials);
        
        return array_values($serials);
    }
}
