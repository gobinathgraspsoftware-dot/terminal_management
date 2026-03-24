<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\NumberSeries;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class InventoryService
{
    // =========================================================
    // Inventory Item CRUD
    // =========================================================

    public function createItem(array $data): InventoryItem
    {
        $data['item_code'] = NumberSeries::getNextNumber('inventory_item');
        $data['created_by'] = Auth::id();

        $item = InventoryItem::create($data);

        // Create initial warehouse balance record
        StockBalance::getOrCreate($item->id, 'warehouse');

        return $item;
    }

    public function updateItem(InventoryItem $item, array $data): InventoryItem
    {
        $data['updated_by'] = Auth::id();
        $item->update($data);
        return $item->fresh();
    }

    public function deleteItem(InventoryItem $item): void
    {
        $totalStock = $item->stockBalances()->sum('quantity');
        if ($totalStock > 0) {
            throw new Exception('Cannot delete item with existing stock balance. Current total: ' . $totalStock);
        }

        $item->delete();
    }

    public function toggleStatus(InventoryItem $item): InventoryItem
    {
        $item->update([
            'status' => $item->status === InventoryItem::STATUS_ACTIVE
                ? InventoryItem::STATUS_INACTIVE
                : InventoryItem::STATUS_ACTIVE,
        ]);
        return $item;
    }

    // =========================================================
    // Stock In (Add stock to warehouse)
    // =========================================================

    public function stockIn(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $movementNo = NumberSeries::getNextNumber('stock_in');

            // Update warehouse balance
            $balance = StockBalance::getOrCreate($data['inventory_item_id'], 'warehouse');
            $balance->increment('quantity', $data['quantity']);

            // Create movement record
            return StockMovement::create([
                'movement_no'       => $movementNo,
                'inventory_item_id' => $data['inventory_item_id'],
                'movement_type'     => StockMovement::TYPE_STOCK_IN,
                'quantity'          => $data['quantity'],
                'from_holder_type'  => null,
                'from_holder_id'    => null,
                'to_holder_type'    => 'warehouse',
                'to_holder_id'      => null,
                'reason'            => $data['reason'] ?? null,
                'remarks'           => $data['remarks'] ?? null,
                'movement_date'     => $data['movement_date'] ?? now()->toDateString(),
                'performed_by'      => Auth::id(),
            ]);
        });
    }

    // =========================================================
    // Stock Out (Issue from warehouse to technician)
    // =========================================================

    public function stockOut(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $movementNo = NumberSeries::getNextNumber('stock_out');

            // Validate warehouse stock
            $warehouseBalance = StockBalance::getOrCreate($data['inventory_item_id'], 'warehouse');
            if ($warehouseBalance->quantity < $data['quantity']) {
                throw new Exception('Insufficient warehouse stock. Available: ' . $warehouseBalance->quantity);
            }

            // Decrease warehouse
            $warehouseBalance->decrement('quantity', $data['quantity']);

            // Increase technician
            $techBalance = StockBalance::getOrCreate(
                $data['inventory_item_id'],
                'technician',
                $data['technician_id']
            );
            $techBalance->increment('quantity', $data['quantity']);

            return StockMovement::create([
                'movement_no'       => $movementNo,
                'inventory_item_id' => $data['inventory_item_id'],
                'movement_type'     => StockMovement::TYPE_STOCK_OUT,
                'quantity'          => $data['quantity'],
                'from_holder_type'  => 'warehouse',
                'from_holder_id'    => null,
                'to_holder_type'    => 'technician',
                'to_holder_id'      => $data['technician_id'],
                'ticket_id'         => $data['ticket_id'] ?? null,
                'reference_type'    => $data['reference_type'] ?? 'manual',
                'reference_id'      => $data['reference_id'] ?? null,
                'reason'            => $data['reason'] ?? null,
                'remarks'           => $data['remarks'] ?? null,
                'movement_date'     => $data['movement_date'] ?? now()->toDateString(),
                'performed_by'      => Auth::id(),
            ]);
        });
    }

    // =========================================================
    // Stock Return (Technician returns to warehouse)
    // =========================================================

    public function stockReturn(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $movementNo = NumberSeries::getNextNumber('stock_return');

            // Validate technician stock
            $techBalance = StockBalance::getOrCreate(
                $data['inventory_item_id'],
                'technician',
                $data['technician_id']
            );
            if ($techBalance->quantity < $data['quantity']) {
                throw new Exception('Insufficient technician stock. Available: ' . $techBalance->quantity);
            }

            // Decrease technician
            $techBalance->decrement('quantity', $data['quantity']);

            // Increase warehouse
            $warehouseBalance = StockBalance::getOrCreate($data['inventory_item_id'], 'warehouse');
            $warehouseBalance->increment('quantity', $data['quantity']);

            return StockMovement::create([
                'movement_no'       => $movementNo,
                'inventory_item_id' => $data['inventory_item_id'],
                'movement_type'     => StockMovement::TYPE_STOCK_RETURN,
                'quantity'          => $data['quantity'],
                'from_holder_type'  => 'technician',
                'from_holder_id'    => $data['technician_id'],
                'to_holder_type'    => 'warehouse',
                'to_holder_id'      => null,
                'ticket_id'         => $data['ticket_id'] ?? null,
                'reason'            => $data['reason'] ?? null,
                'remarks'           => $data['remarks'] ?? null,
                'movement_date'     => $data['movement_date'] ?? now()->toDateString(),
                'performed_by'      => Auth::id(),
            ]);
        });
    }

    // =========================================================
    // Stock Adjustment (+ or -)
    // =========================================================

    public function stockAdjustment(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $movementNo = NumberSeries::getNextNumber('stock_adjust');
            $quantity = (int) $data['quantity']; // Can be negative

            $holderType = $data['holder_type'] ?? 'warehouse';
            $holderId = $holderType === 'warehouse' ? null : ($data['holder_id'] ?? null);

            $balance = StockBalance::getOrCreate(
                $data['inventory_item_id'],
                $holderType,
                $holderId
            );

            $newQty = $balance->quantity + $quantity;
            if ($newQty < 0) {
                throw new Exception('Adjustment would result in negative stock. Current: ' . $balance->quantity);
            }

            $balance->update(['quantity' => $newQty]);

            return StockMovement::create([
                'movement_no'       => $movementNo,
                'inventory_item_id' => $data['inventory_item_id'],
                'movement_type'     => StockMovement::TYPE_STOCK_ADJUSTMENT,
                'quantity'          => $quantity,
                'from_holder_type'  => $quantity < 0 ? $holderType : null,
                'from_holder_id'    => $quantity < 0 ? $holderId : null,
                'to_holder_type'    => $quantity > 0 ? $holderType : null,
                'to_holder_id'      => $quantity > 0 ? $holderId : null,
                'reason'            => $data['reason'] ?? null,
                'remarks'           => $data['remarks'] ?? null,
                'movement_date'     => $data['movement_date'] ?? now()->toDateString(),
                'performed_by'      => Auth::id(),
            ]);
        });
    }

    // =========================================================
    // Stock Transfer (between warehouse <-> technician)
    // =========================================================

    public function createTransfer(array $data): StockTransfer
    {
        $transferNo = NumberSeries::getNextNumber('stock_transfer');

        return StockTransfer::create([
            'transfer_no'       => $transferNo,
            'from_holder_type'  => $data['from_holder_type'],
            'from_holder_id'    => $data['from_holder_type'] === 'warehouse' ? null : $data['from_holder_id'],
            'to_holder_type'    => $data['to_holder_type'],
            'to_holder_id'      => $data['to_holder_type'] === 'warehouse' ? null : $data['to_holder_id'],
            'inventory_item_id' => $data['inventory_item_id'],
            'quantity'          => $data['quantity'],
            'reason'            => $data['reason'] ?? null,
            'remarks'           => $data['remarks'] ?? null,
            'transfer_date'     => $data['transfer_date'] ?? now()->toDateString(),
            'created_by'        => Auth::id(),
        ]);
    }

    public function approveTransfer(StockTransfer $transfer): StockTransfer
    {
        if (!$transfer->isPending()) {
            throw new Exception('Transfer is not in pending status.');
        }

        return DB::transaction(function () use ($transfer) {
            // Validate source balance
            $fromBalance = StockBalance::getOrCreate(
                $transfer->inventory_item_id,
                $transfer->from_holder_type,
                $transfer->from_holder_id
            );

            if ($fromBalance->quantity < $transfer->quantity) {
                throw new Exception('Insufficient stock at source. Available: ' . $fromBalance->quantity);
            }

            // Decrease source
            $fromBalance->decrement('quantity', $transfer->quantity);

            // Increase destination
            $toBalance = StockBalance::getOrCreate(
                $transfer->inventory_item_id,
                $transfer->to_holder_type,
                $transfer->to_holder_id
            );
            $toBalance->increment('quantity', $transfer->quantity);

            // Update transfer status
            $transfer->update([
                'status'      => StockTransfer::STATUS_COMPLETED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Create movement record
            $movementNo = NumberSeries::getNextNumber('stock_transfer');
            StockMovement::create([
                'movement_no'       => $movementNo,
                'inventory_item_id' => $transfer->inventory_item_id,
                'movement_type'     => StockMovement::TYPE_STOCK_TRANSFER,
                'quantity'          => $transfer->quantity,
                'from_holder_type'  => $transfer->from_holder_type,
                'from_holder_id'    => $transfer->from_holder_id,
                'to_holder_type'    => $transfer->to_holder_type,
                'to_holder_id'      => $transfer->to_holder_id,
                'transfer_id'       => $transfer->id,
                'reason'            => $transfer->reason,
                'remarks'           => 'Transfer #' . $transfer->transfer_no . ' completed',
                'movement_date'     => now()->toDateString(),
                'performed_by'      => Auth::id(),
            ]);

            return $transfer->fresh();
        });
    }

    public function rejectTransfer(StockTransfer $transfer, ?string $reason = null): StockTransfer
    {
        if (!$transfer->isPending()) {
            throw new Exception('Transfer is not in pending status.');
        }

        $transfer->update([
            'status'      => StockTransfer::STATUS_REJECTED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'remarks'     => $reason ?? $transfer->remarks,
        ]);

        return $transfer->fresh();
    }

    // =========================================================
    // Auto-deduction for Ticket/Job execution
    // One ticket = one item / one quantity for accessories
    // =========================================================

    public function autoDeductForTicket(int $ticketId, int $inventoryItemId, int $technicianId, int $quantity = 1): StockMovement
    {
        return DB::transaction(function () use ($ticketId, $inventoryItemId, $technicianId, $quantity) {
            $techBalance = StockBalance::getOrCreate($inventoryItemId, 'technician', $technicianId);

            if ($techBalance->quantity < $quantity) {
                throw new Exception('Technician has insufficient stock for auto-deduction. Available: ' . $techBalance->quantity);
            }

            $techBalance->decrement('quantity', $quantity);

            $movementNo = NumberSeries::getNextNumber('stock_out');

            return StockMovement::create([
                'movement_no'       => $movementNo,
                'inventory_item_id' => $inventoryItemId,
                'movement_type'     => StockMovement::TYPE_STOCK_OUT,
                'quantity'          => $quantity,
                'from_holder_type'  => 'technician',
                'from_holder_id'    => $technicianId,
                'to_holder_type'    => null,
                'to_holder_id'      => null,
                'ticket_id'         => $ticketId,
                'reference_type'    => 'ticket',
                'reference_id'      => $ticketId,
                'reason'            => 'Auto-deduction for ticket execution',
                'movement_date'     => now()->toDateString(),
                'performed_by'      => Auth::id(),
            ]);
        });
    }

    // =========================================================
    // Query Helpers
    // =========================================================

    /**
     * Get technician stock for a specific technician.
     */
    public function getTechnicianStock(int $technicianId)
    {
        return StockBalance::where('holder_type', 'technician')
            ->where('holder_id', $technicianId)
            ->where('quantity', '>', 0)
            ->with('inventoryItem.jobCategory')
            ->get();
    }

    /**
     * Get warehouse stock summary.
     */
    public function getWarehouseStock()
    {
        return StockBalance::where('holder_type', 'warehouse')
            ->whereNull('holder_id')
            ->where('quantity', '>', 0)
            ->with('inventoryItem.jobCategory')
            ->get();
    }

    /**
     * Get low stock items.
     */
    public function getLowStockItems()
    {
        return InventoryItem::active()
            ->whereHas('stockBalances', function ($q) {
                $q->where('holder_type', 'warehouse')->whereNull('holder_id');
            })
            ->get()
            ->filter(fn($item) => $item->isLowStock());
    }
}
