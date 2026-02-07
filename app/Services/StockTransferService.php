<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\StockTransferLine;
use App\Models\InventorySerial;
use App\Models\StockLedger;
use App\Models\StockBalance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockTransferService
{
    /**
     * Generate next transfer number
     */
    public function generateTransferNumber(): string
    {
        $prefix = 'ST';
        $date = date('Ymd');
        
        $lastTransfer = StockTransfer::where('transfer_no', 'LIKE', $prefix . $date . '%')
            ->orderBy('transfer_no', 'desc')
            ->lockForUpdate()
            ->first();

        if ($lastTransfer) {
            $lastNumber = (int)substr($lastTransfer->transfer_no, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new stock transfer
     */
    public function createTransfer(array $data, int $userId): StockTransfer
    {
        $transfer = new StockTransfer();
        $transfer->transfer_no = $this->generateTransferNumber();
        $transfer->transfer_date = $data['transfer_date'];
        $transfer->from_depot_id = $data['from_depot_id'];
        $transfer->to_depot_id = $data['to_depot_id'];
        $transfer->remarks = $data['remarks'] ?? null;
        $transfer->status = StockTransfer::STATUS_DRAFT;
        $transfer->created_by = $userId;
        $transfer->save();

        // Create transfer lines
        $lineNo = 1;
        foreach ($data['lines'] as $lineData) {
            $this->createTransferLine($transfer, $lineData, $lineNo++);
        }

        // Update total items
        $transfer->total_items = $transfer->lines()->sum('quantity_requested');
        $transfer->save();

        return $transfer->fresh('lines.model', 'lines.serial');
    }

    /**
     * Create a transfer line
     */
    private function createTransferLine(StockTransfer $transfer, array $data, int $lineNo): StockTransferLine
    {
        $line = new StockTransferLine();
        $line->stock_transfer_id = $transfer->id;
        $line->line_no = $lineNo;
        $line->model_id = $data['model_id'];
        $line->serial_id = $data['serial_id'] ?? null;
        $line->serial_no = $data['serial_no'] ?? null;
        $line->quantity_requested = $data['quantity_requested'] ?? 1;
        $line->quantity_dispatched = 0;
        $line->quantity_received = 0;
        $line->remarks = $data['remarks'] ?? null;
        $line->save();

        return $line;
    }

    /**
     * Update an existing transfer (only draft status)
     */
    public function updateTransfer(StockTransfer $transfer, array $data, int $userId): bool
    {
        if ($transfer->status !== StockTransfer::STATUS_DRAFT) {
            throw new \Exception('Only draft transfers can be updated');
        }

        $transfer->transfer_date = $data['transfer_date'];
        $transfer->from_depot_id = $data['from_depot_id'];
        $transfer->to_depot_id = $data['to_depot_id'];
        $transfer->remarks = $data['remarks'] ?? null;
        $transfer->updated_by = $userId;
        $transfer->save();

        // Delete existing lines
        $transfer->lines()->delete();

        // Create new lines
        $lineNo = 1;
        foreach ($data['lines'] as $lineData) {
            $this->createTransferLine($transfer, $lineData, $lineNo++);
        }

        // Update total items
        $transfer->total_items = $transfer->lines()->sum('quantity_requested');
        $transfer->save();

        return true;
    }

    /**
     * Submit transfer for approval
     */
    public function submitForApproval(StockTransfer $transfer): bool
    {
        if ($transfer->status !== StockTransfer::STATUS_DRAFT) {
            throw new \Exception('Only draft transfers can be submitted for approval');
        }

        if ($transfer->lines()->count() === 0) {
            throw new \Exception('Cannot submit transfer without items');
        }

        // Validate stock availability
        $this->validateStockAvailability($transfer);

        $transfer->status = StockTransfer::STATUS_PENDING_APPROVAL;
        $transfer->save();

        return true;
    }

    /**
     * Approve transfer
     */
    public function approveTransfer(StockTransfer $transfer, int $approverId, ?string $remarks = null): bool
    {
        if ($transfer->status !== StockTransfer::STATUS_PENDING_APPROVAL) {
            throw new \Exception('Only pending transfers can be approved');
        }

        // Re-validate stock availability
        $this->validateStockAvailability($transfer);

        $transfer->status = StockTransfer::STATUS_APPROVED;
        $transfer->approved_at = now();
        $transfer->approved_by = $approverId;
        if ($remarks) {
            $transfer->remarks = ($transfer->remarks ? $transfer->remarks . "\n\n" : '') . 
                                 "Approval Note: " . $remarks;
        }
        $transfer->save();

        return true;
    }

    /**
     * Reject transfer
     */
    public function rejectTransfer(StockTransfer $transfer, string $reason): bool
    {
        if ($transfer->status !== StockTransfer::STATUS_PENDING_APPROVAL) {
            throw new \Exception('Only pending transfers can be rejected');
        }

        $transfer->status = StockTransfer::STATUS_CANCELLED;
        $transfer->remarks = ($transfer->remarks ? $transfer->remarks . "\n\n" : '') . 
                             "Rejection Reason: " . $reason;
        $transfer->save();

        return true;
    }

    /**
     * Dispatch transfer (mark as in transit and update stock ledger)
     */
    public function dispatchTransfer(StockTransfer $transfer, array $data, int $dispatcherId): bool
    {
        if ($transfer->status !== StockTransfer::STATUS_APPROVED) {
            throw new \Exception('Only approved transfers can be dispatched');
        }

        DB::beginTransaction();

        try {
            // Update quantities dispatched
            foreach ($data['lines'] as $lineId => $lineData) {
                $line = $transfer->lines()->find($lineId);
                if ($line) {
                    $line->quantity_dispatched = $lineData['quantity_dispatched'];
                    $line->save();

                    // Update serial status if applicable
                    if ($line->serial_id) {
                        $this->updateSerialOnDispatch($line);
                    }

                    // Create stock ledger entry (OUT from source depot)
                    $this->createStockLedgerEntry(
                        $transfer,
                        $line,
                        'out',
                        $lineData['quantity_dispatched']
                    );
                }
            }

            $transfer->status = StockTransfer::STATUS_IN_TRANSIT;
            $transfer->dispatched_at = now();
            $transfer->dispatched_by = $dispatcherId;
            $transfer->save();

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Receive transfer (complete the transfer)
     */
    public function receiveTransfer(StockTransfer $transfer, array $data, int $receiverId): bool
    {
        if (!in_array($transfer->status, [
            StockTransfer::STATUS_IN_TRANSIT,
            StockTransfer::STATUS_APPROVED
        ])) {
            throw new \Exception('Transfer cannot be received in current status');
        }

        DB::beginTransaction();

        try {
            $hasVariance = false;

            // Update quantities received
            foreach ($data['lines'] as $lineId => $lineData) {
                $line = $transfer->lines()->find($lineId);
                if ($line) {
                    $qtyReceived = $lineData['quantity_received'];
                    $qtyDispatched = $line->quantity_dispatched ?: $line->quantity_requested;
                    
                    $line->quantity_received = $qtyReceived;
                    
                    // Record variance if any
                    if ($qtyReceived != $qtyDispatched) {
                        $hasVariance = true;
                        $varianceNote = "Variance: Expected {$qtyDispatched}, Received {$qtyReceived}";
                        $line->remarks = ($line->remarks ? $line->remarks . "\n" : '') . $varianceNote;
                    }
                    
                    $line->save();

                    // Update serial status if applicable
                    if ($line->serial_id && $qtyReceived > 0) {
                        $this->updateSerialOnReceive($line, $transfer->to_depot_id);
                    }

                    // Create stock ledger entry (IN to destination depot)
                    if ($qtyReceived > 0) {
                        $this->createStockLedgerEntry(
                            $transfer,
                            $line,
                            'in',
                            $qtyReceived
                        );
                    }
                }
            }

            $transfer->status = StockTransfer::STATUS_RECEIVED;
            $transfer->received_at = now();
            $transfer->received_by = $receiverId;
            
            if ($hasVariance) {
                $transfer->remarks = ($transfer->remarks ? $transfer->remarks . "\n\n" : '') . 
                                     "Note: Quantity variance detected during receiving";
            }
            
            $transfer->save();

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel transfer
     */
    public function cancelTransfer(StockTransfer $transfer, string $reason): bool
    {
        if ($transfer->status === StockTransfer::STATUS_RECEIVED) {
            throw new \Exception('Received transfers cannot be cancelled');
        }

        if ($transfer->status === StockTransfer::STATUS_IN_TRANSIT) {
            // If already dispatched, need to reverse stock movements
            DB::beginTransaction();
            
            try {
                foreach ($transfer->lines as $line) {
                    if ($line->quantity_dispatched > 0) {
                        // Reverse the dispatch entry
                        $this->reverseStockLedgerEntry($transfer, $line);
                        
                        // Reset serial status
                        if ($line->serial_id) {
                            $this->resetSerialStatus($line, $transfer->from_depot_id);
                        }
                    }
                }

                $transfer->status = StockTransfer::STATUS_CANCELLED;
                $transfer->remarks = ($transfer->remarks ? $transfer->remarks . "\n\n" : '') . 
                                     "Cancellation Reason: " . $reason;
                $transfer->save();

                DB::commit();
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } else {
            // Not yet dispatched, simple cancellation
            $transfer->status = StockTransfer::STATUS_CANCELLED;
            $transfer->remarks = ($transfer->remarks ? $transfer->remarks . "\n\n" : '') . 
                                 "Cancellation Reason: " . $reason;
            $transfer->save();
        }

        return true;
    }

    /**
     * Validate stock availability at source depot
     */
    private function validateStockAvailability(StockTransfer $transfer): void
    {
        foreach ($transfer->lines as $line) {
            if ($line->serial_id) {
                // Validate serial exists and is at source depot
                $serial = InventorySerial::find($line->serial_id);
                
                if (!$serial || 
                    $serial->current_location_type !== 'depot' || 
                    $serial->current_location_id != $transfer->from_depot_id ||
                    $serial->current_status !== 'in_stock') {
                    throw new \Exception("Serial {$line->serial_no} is not available at source depot");
                }
            } else {
                // Check stock balance
                $balance = StockBalance::where('model_id', $line->model_id)
                    ->where('location_type', 'depot')
                    ->where('location_id', $transfer->from_depot_id)
                    ->first();

                $available = $balance ? $balance->quantity_available : 0;
                
                if ($available < $line->quantity_requested) {
                    throw new \Exception("Insufficient stock for model ID {$line->model_id}");
                }
            }
        }
    }

    /**
     * Update serial status on dispatch
     */
    private function updateSerialOnDispatch(StockTransferLine $line): void
    {
        if ($serial = InventorySerial::find($line->serial_id)) {
            $serial->current_status = 'in_transit';
            $serial->save();
        }
    }

    /**
     * Update serial status and location on receive
     */
    private function updateSerialOnReceive(StockTransferLine $line, int $toDepotId): void
    {
        if ($serial = InventorySerial::find($line->serial_id)) {
            $serial->current_location_type = 'depot';
            $serial->current_location_id = $toDepotId;
            $serial->current_status = 'in_stock';
            $serial->save();
        }
    }

    /**
     * Reset serial status on cancellation
     */
    private function resetSerialStatus(StockTransferLine $line, int $fromDepotId): void
    {
        if ($serial = InventorySerial::find($line->serial_id)) {
            $serial->current_location_type = 'depot';
            $serial->current_location_id = $fromDepotId;
            $serial->current_status = 'in_stock';
            $serial->save();
        }
    }

    /**
     * Create stock ledger entry
     */
    private function createStockLedgerEntry(
        StockTransfer $transfer,
        StockTransferLine $line,
        string $direction,
        float $quantity
    ): void {
        $ledger = new StockLedger();
        $ledger->transaction_date = now()->toDateString();
        $ledger->transaction_no = $transfer->transfer_no;
        $ledger->transaction_type = 'transfer';
        $ledger->reference_type = 'stock_transfer';
        $ledger->reference_id = $transfer->id;
        $ledger->serial_id = $line->serial_id;
        $ledger->serial_no = $line->serial_no;
        $ledger->model_id = $line->model_id;

        if ($direction === 'out') {
            $ledger->quantity = -$quantity;
            $ledger->from_location_type = 'depot';
            $ledger->from_location_id = $transfer->from_depot_id;
            $ledger->to_location_type = 'depot';
            $ledger->to_location_id = $transfer->to_depot_id;
        } else {
            $ledger->quantity = $quantity;
            $ledger->from_location_type = 'depot';
            $ledger->from_location_id = $transfer->from_depot_id;
            $ledger->to_location_type = 'depot';
            $ledger->to_location_id = $transfer->to_depot_id;
        }

        $ledger->remarks = "Transfer {$direction}: {$transfer->transfer_no}";
        $ledger->created_by = $transfer->created_by;
        $ledger->save();

        // Update stock balance
        $this->updateStockBalance($ledger);
    }

    /**
     * Reverse stock ledger entry
     */
    private function reverseStockLedgerEntry(StockTransfer $transfer, StockTransferLine $line): void
    {
        // Find the original entry
        $originalEntry = StockLedger::where('reference_type', 'stock_transfer')
            ->where('reference_id', $transfer->id)
            ->where('model_id', $line->model_id)
            ->where('serial_id', $line->serial_id)
            ->first();

        if ($originalEntry) {
            // Create reversal entry
            $reversal = new StockLedger();
            $reversal->transaction_date = now()->toDateString();
            $reversal->transaction_no = $transfer->transfer_no . '-REV';
            $reversal->transaction_type = 'transfer';
            $reversal->reference_type = 'stock_transfer';
            $reversal->reference_id = $transfer->id;
            $reversal->serial_id = $line->serial_id;
            $reversal->serial_no = $line->serial_no;
            $reversal->model_id = $line->model_id;
            $reversal->quantity = -$originalEntry->quantity; // Opposite sign
            $reversal->from_location_type = $originalEntry->from_location_type;
            $reversal->from_location_id = $originalEntry->from_location_id;
            $reversal->to_location_type = $originalEntry->to_location_type;
            $reversal->to_location_id = $originalEntry->to_location_id;
            $reversal->remarks = "Reversal: Transfer cancelled";
            $reversal->reversal_of_id = $originalEntry->id;
            $reversal->created_by = auth()->id();
            $reversal->save();

            // Mark original as reversed
            $originalEntry->is_reversed = true;
            $originalEntry->reversed_by_id = $reversal->id;
            $originalEntry->reversed_at = now();
            $originalEntry->reversed_by_user_id = auth()->id();
            $originalEntry->save();

            // Update stock balance
            $this->updateStockBalance($reversal);
        }
    }

    /**
     * Update stock balance after ledger entry
     */
    private function updateStockBalance(StockLedger $ledger): void
    {
        // Update source location
        if ($ledger->from_location_type && $ledger->from_location_id) {
            $this->adjustStockBalance(
                $ledger->model_id,
                $ledger->from_location_type,
                $ledger->from_location_id,
                $ledger->quantity < 0 ? abs($ledger->quantity) : -abs($ledger->quantity)
            );
        }

        // Update destination location
        if ($ledger->to_location_type && $ledger->to_location_id) {
            $this->adjustStockBalance(
                $ledger->model_id,
                $ledger->to_location_type,
                $ledger->to_location_id,
                $ledger->quantity > 0 ? $ledger->quantity : -$ledger->quantity
            );
        }
    }

    /**
     * Adjust stock balance
     */
    private function adjustStockBalance(int $modelId, string $locationType, int $locationId, float $adjustment): void
    {
        $balance = StockBalance::firstOrNew([
            'model_id' => $modelId,
            'location_type' => $locationType,
            'location_id' => $locationId,
        ]);

        $balance->quantity_on_hand = ($balance->quantity_on_hand ?? 0) + $adjustment;
        $balance->last_movement_date = now()->toDateString();
        $balance->save();
    }
}
