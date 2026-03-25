<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\NumberSeries;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    // ══════════════════════════════════════════════════════════
    // DATATABLE
    // ══════════════════════════════════════════════════════════

    /**
     * Server-side DataTable for inventory items.
     * Removed: job_category_id, serial_number references
     */
    public function getDatatable(array $params): array
    {
        $query = InventoryItem::with(['creator'])
            ->select('inventory_items.*')
            ->leftJoin('stock_balances', function ($join) {
                $join->on('stock_balances.inventory_item_id', '=', 'inventory_items.id')
                     ->where('stock_balances.holder_type', '=', 'warehouse')
                     ->whereNull('stock_balances.holder_id');
            })
            ->addSelect(DB::raw('COALESCE(stock_balances.quantity, 0) as warehouse_qty'));

        // Filters
        if (!empty($params['item_type'])) {
            $query->where('inventory_items.item_type', $params['item_type']);
        }
        if (!empty($params['accessory_type'])) {
            $query->where('inventory_items.accessory_type', $params['accessory_type']);
        }
        if (!empty($params['status'])) {
            $query->where('inventory_items.status', $params['status']);
        }

        $totalRecords = InventoryItem::count();

        // Search
        $search = $params['search']['value'] ?? '';
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('inventory_items.item_code', 'like', "%{$search}%")
                  ->orWhere('inventory_items.item_name', 'like', "%{$search}%")
                  ->orWhere('inventory_items.brand', 'like', "%{$search}%")
                  ->orWhere('inventory_items.model', 'like', "%{$search}%");
            });
        }

        $filteredRecords = $query->count();

        // Sorting
        $sortableColumns = [
            0 => 'inventory_items.item_code',
            1 => 'inventory_items.item_name',
            2 => 'inventory_items.item_type',
            3 => 'inventory_items.brand',
            4 => 'warehouse_qty',
            5 => 'inventory_items.status',
            6 => 'inventory_items.created_at',
        ];

        $orderColumn = $params['order'][0]['column'] ?? 6;
        $orderDir = $params['order'][0]['dir'] ?? 'desc';
        $orderBy = $sortableColumns[$orderColumn] ?? 'inventory_items.created_at';
        $query->orderBy($orderBy, $orderDir);

        // Pagination
        $start = $params['start'] ?? 0;
        $length = $params['length'] ?? 10;
        $items = $query->skip($start)->take($length)->get();

        $data = $items->map(function ($item, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $item->id,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'item_type' => $item->getTypeBadge(),
                'brand' => $item->brand ?? '-',
                'model' => $item->model ?? '-',
                'warehouse_stock' => (int) $item->warehouse_qty,
                'total_stock' => $item->getTotalStock(),
                'reorder_level' => $item->reorder_level,
                'is_low_stock' => ((int) $item->warehouse_qty) <= $item->reorder_level,
                'status' => $item->getStatusBadge(),
                'raw_status' => $item->status,
                'created_at' => $item->created_at?->format('d M Y'),
            ];
        });

        return [
            'draw' => intval($params['draw'] ?? 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ];
    }

    // ══════════════════════════════════════════════════════════
    // CRUD
    // ══════════════════════════════════════════════════════════

    /**
     * Create a new inventory item.
     * Removed: job_category_id, serial_number
     */
    public function createItem(array $data): InventoryItem
    {
        return DB::transaction(function () use ($data) {
            $data['item_code'] = NumberSeries::getNextNumber('inventory_item');
            $data['created_by'] = Auth::id();

            // Clear accessory_type for routers
            if ($data['item_type'] === InventoryItem::TYPE_ROUTER) {
                $data['accessory_type'] = null;
            }

            $item = InventoryItem::create($data);

            // Initialize warehouse stock balance
            StockBalance::create([
                'inventory_item_id' => $item->id,
                'holder_type' => 'warehouse',
                'holder_id' => null,
                'quantity' => 0,
            ]);

            Log::info('Inventory item created', ['item_id' => $item->id, 'code' => $item->item_code]);

            return $item;
        });
    }

    /**
     * Update an inventory item.
     * Removed: job_category_id, serial_number
     */
    public function updateItem(InventoryItem $item, array $data): InventoryItem
    {
        $data['updated_by'] = Auth::id();

        if (($data['item_type'] ?? $item->item_type) === InventoryItem::TYPE_ROUTER) {
            $data['accessory_type'] = null;
        }

        $item->update($data);
        return $item->fresh();
    }

    /**
     * Delete (soft) an inventory item.
     */
    public function deleteItem(InventoryItem $item): bool
    {
        if ($item->getTotalStock() > 0) {
            throw new \Exception('Cannot delete item with existing stock. Please adjust stock to zero first.');
        }
        return $item->delete();
    }

    /**
     * Toggle item status.
     */
    public function toggleStatus(InventoryItem $item): InventoryItem
    {
        $item->update([
            'status' => $item->isActive() ? InventoryItem::STATUS_INACTIVE : InventoryItem::STATUS_ACTIVE,
            'updated_by' => Auth::id(),
        ]);
        return $item->fresh();
    }

    // ══════════════════════════════════════════════════════════
    // STOCK IN
    // ══════════════════════════════════════════════════════════

    /**
     * Stock In - Add items to warehouse.
     * Changes:
     *   - movement_date renamed to stockin_date (mapped to movement_date column)
     *   - item_condition removed
     *   - quantity + router_ids added (one router_id per quantity unit)
     */
    public function stockIn(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $item = InventoryItem::findOrFail($data['inventory_item_id']);
            $quantity = (int) ($data['quantity'] ?? 1);

            // Validate router_ids count matches quantity
            $routerIds = $data['router_ids'] ?? [];
            if (is_string($routerIds)) {
                $routerIds = json_decode($routerIds, true) ?? [];
            }
            // Filter empty values
            $routerIds = array_values(array_filter($routerIds, fn($v) => !empty(trim($v))));

            if (!empty($routerIds) && count($routerIds) !== $quantity) {
                throw new \Exception('Number of Router IDs (' . count($routerIds) . ') must match the quantity (' . $quantity . ').');
            }

            // Update warehouse balance
            $balance = StockBalance::getOrCreate($item->id, 'warehouse', null);
            $balance->increment('quantity', $quantity);

            // Create movement record (stockin_date maps to movement_date)
            $movement = StockMovement::create([
                'movement_no' => NumberSeries::getNextNumber('stock_movement'),
                'inventory_item_id' => $item->id,
                'movement_type' => StockMovement::TYPE_STOCK_IN,
                'quantity' => $quantity,
                'router_ids' => !empty($routerIds) ? $routerIds : null,
                'from_holder_type' => null,
                'from_holder_id' => null,
                'to_holder_type' => 'warehouse',
                'to_holder_id' => null,
                'ticket_id' => null,
                'reference_type' => $data['reference_type'] ?? 'manual',
                'reference_id' => $data['reference_id'] ?? null,
                'reason' => $data['reason'] ?? 'Stock In',
                'remarks' => $data['remarks'] ?? null,
                'item_condition' => null, // condition removed for stock in
                'movement_date' => $data['stockin_date'] ?? now()->toDateString(),
                'performed_by' => Auth::id(),
            ]);

            Log::info('Stock In processed', [
                'movement_no' => $movement->movement_no,
                'item_id' => $item->id,
                'quantity' => $quantity,
                'router_ids' => $routerIds,
            ]);

            return $movement;
        });
    }

    // ══════════════════════════════════════════════════════════
    // STOCK OUT (Auto-triggered from Ticket)
    // ══════════════════════════════════════════════════════════

    /**
     * Stock Out - Auto-deduct from warehouse when installation ticket is created.
     * This is called automatically from TicketService, NOT manually from a form.
     *
     * Changes:
     *   - No manual form (list-only view)
     *   - Auto-triggered on ticket creation with job_type = installation
     *   - Router IDs based on quantity
     *   - movement_date renamed to stockout_date
     */
    public function stockOut(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $item = InventoryItem::findOrFail($data['inventory_item_id']);
            $quantity = (int) ($data['quantity'] ?? 1);

            $routerIds = $data['router_ids'] ?? [];
            if (is_string($routerIds)) {
                $routerIds = json_decode($routerIds, true) ?? [];
            }
            $routerIds = array_values(array_filter($routerIds, fn($v) => !empty(trim($v))));

            if (!empty($routerIds) && count($routerIds) !== $quantity) {
                throw new \Exception('Number of Router IDs (' . count($routerIds) . ') must match the quantity (' . $quantity . ').');
            }

            // Check availability
            $balance = StockBalance::getOrCreate($item->id, 'warehouse', null);
            if ($balance->quantity < $quantity) {
                throw new \Exception("Insufficient stock. Available: {$balance->quantity}, Requested: {$quantity}");
            }

            // Deduct from warehouse
            $balance->decrement('quantity', $quantity);

            // Create movement record (stockout_date maps to movement_date)
            $movement = StockMovement::create([
                'movement_no' => NumberSeries::getNextNumber('stock_movement'),
                'inventory_item_id' => $item->id,
                'movement_type' => StockMovement::TYPE_STOCK_OUT,
                'quantity' => -$quantity,
                'router_ids' => !empty($routerIds) ? $routerIds : null,
                'from_holder_type' => 'warehouse',
                'from_holder_id' => null,
                'to_holder_type' => null,
                'to_holder_id' => null,
                'ticket_id' => $data['ticket_id'] ?? null,
                'reference_type' => $data['ticket_id'] ? 'ticket' : 'manual',
                'reference_id' => $data['ticket_id'] ?? null,
                'reason' => $data['reason'] ?? 'Stock Out - Installation',
                'remarks' => $data['remarks'] ?? null,
                'item_condition' => 'good',
                'movement_date' => $data['stockout_date'] ?? now()->toDateString(),
                'performed_by' => Auth::id() ?? ($data['performed_by'] ?? 1),
            ]);

            Log::info('Stock Out processed', [
                'movement_no' => $movement->movement_no,
                'item_id' => $item->id,
                'quantity' => $quantity,
                'ticket_id' => $data['ticket_id'] ?? null,
                'router_ids' => $routerIds,
            ]);

            return $movement;
        });
    }

    /**
     * Auto Stock Out triggered when an installation ticket is created.
     * Finds a matching router item in stock and deducts 1 quantity.
     */
    public function autoStockOutForInstallation(Ticket $ticket): ?StockMovement
    {
        // Only auto stock-out for router items when ticket has router_ids
        $routerIds = $ticket->router_ids;
        if (empty($routerIds)) {
            return null;
        }

        // Find a router inventory item with available stock
        $routerItem = InventoryItem::routers()
            ->active()
            ->whereHas('stockBalances', function ($q) {
                $q->warehouse()->where('quantity', '>', 0);
            })
            ->first();

        if (!$routerItem) {
            Log::warning('Auto stock-out failed: No router items in stock', [
                'ticket_id' => $ticket->id,
            ]);
            return null;
        }

        try {
            $quantity = is_array($routerIds) ? count($routerIds) : 1;

            return $this->stockOut([
                'inventory_item_id' => $routerItem->id,
                'quantity' => $quantity,
                'router_ids' => $routerIds,
                'ticket_id' => $ticket->id,
                'reason' => 'Auto Stock Out - Installation Ticket #' . $ticket->ticket_no,
                'stockout_date' => now()->toDateString(),
                'performed_by' => Auth::id() ?? $ticket->created_by,
            ]);
        } catch (\Exception $e) {
            Log::error('Auto stock-out failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ══════════════════════════════════════════════════════════
    // STOCK RETURN
    // ══════════════════════════════════════════════════════════

    /**
     * Stock Return - Return items back to warehouse.
     *
     * Two flows:
     * 1. Auto-triggered when replacement ticket is created (old router IDs returned)
     * 2. Manual creation by user (same fields as stock out)
     *
     * Changes:
     *   - movement_date renamed to stockreturn_date
     *   - router_ids added (one per quantity)
     *   - condition field retained for returns
     */
    public function stockReturn(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $item = InventoryItem::findOrFail($data['inventory_item_id']);
            $quantity = (int) ($data['quantity'] ?? 1);

            $routerIds = $data['router_ids'] ?? [];
            if (is_string($routerIds)) {
                $routerIds = json_decode($routerIds, true) ?? [];
            }
            $routerIds = array_values(array_filter($routerIds, fn($v) => !empty(trim($v))));

            if (!empty($routerIds) && count($routerIds) !== $quantity) {
                throw new \Exception('Number of Router IDs (' . count($routerIds) . ') must match the quantity (' . $quantity . ').');
            }

            // Add to warehouse balance
            $balance = StockBalance::getOrCreate($item->id, 'warehouse', null);
            $balance->increment('quantity', $quantity);

            // Create movement record (stockreturn_date maps to movement_date)
            $movement = StockMovement::create([
                'movement_no' => NumberSeries::getNextNumber('stock_movement'),
                'inventory_item_id' => $item->id,
                'movement_type' => StockMovement::TYPE_STOCK_RETURN,
                'quantity' => $quantity,
                'router_ids' => !empty($routerIds) ? $routerIds : null,
                'from_holder_type' => null,
                'from_holder_id' => null,
                'to_holder_type' => 'warehouse',
                'to_holder_id' => null,
                'ticket_id' => $data['ticket_id'] ?? null,
                'reference_type' => !empty($data['ticket_id']) ? 'ticket' : 'manual',
                'reference_id' => $data['ticket_id'] ?? null,
                'reason' => $data['reason'] ?? 'Stock Return',
                'remarks' => $data['remarks'] ?? null,
                'item_condition' => $data['item_condition'] ?? 'good',
                'movement_date' => $data['stockreturn_date'] ?? now()->toDateString(),
                'performed_by' => Auth::id() ?? ($data['performed_by'] ?? 1),
            ]);

            Log::info('Stock Return processed', [
                'movement_no' => $movement->movement_no,
                'item_id' => $item->id,
                'quantity' => $quantity,
                'router_ids' => $routerIds,
                'ticket_id' => $data['ticket_id'] ?? null,
            ]);

            return $movement;
        });
    }

    /**
     * Auto Stock Return triggered when a replacement ticket is created.
     * The old router IDs are returned to stock.
     */
    public function autoStockReturnForReplacement(Ticket $ticket): ?StockMovement
    {
        $oldRouterIds = $ticket->old_router_ids;
        if (empty($oldRouterIds)) {
            return null;
        }

        // Find a router inventory item
        $routerItem = InventoryItem::routers()->active()->first();
        if (!$routerItem) {
            Log::warning('Auto stock-return failed: No router item found', [
                'ticket_id' => $ticket->id,
            ]);
            return null;
        }

        try {
            $quantity = is_array($oldRouterIds) ? count($oldRouterIds) : 1;

            return $this->stockReturn([
                'inventory_item_id' => $routerItem->id,
                'quantity' => $quantity,
                'router_ids' => $oldRouterIds,
                'ticket_id' => $ticket->id,
                'reason' => 'Auto Stock Return - Replacement Ticket #' . $ticket->ticket_no,
                'item_condition' => 'faulty',
                'stockreturn_date' => now()->toDateString(),
                'performed_by' => Auth::id() ?? $ticket->created_by,
            ]);
        } catch (\Exception $e) {
            Log::error('Auto stock-return failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ══════════════════════════════════════════════════════════
    // STOCK ADJUSTMENT (unchanged)
    // ══════════════════════════════════════════════════════════

    /**
     * Stock Adjustment - Correct inventory discrepancies.
     */
    public function stockAdjustment(array $data): StockAdjustment
    {
        return DB::transaction(function () use ($data) {
            $item = InventoryItem::findOrFail($data['inventory_item_id']);

            $balance = StockBalance::getOrCreate($item->id, 'warehouse', null);
            $oldQuantity = $balance->quantity;
            $newQuantity = (int) $data['new_quantity'];
            $difference = $newQuantity - $oldQuantity;

            if ($difference === 0) {
                throw new \Exception('New quantity is the same as current quantity. No adjustment needed.');
            }

            // Update balance
            $balance->update(['quantity' => $newQuantity]);

            // Create movement record
            $movement = StockMovement::create([
                'movement_no' => NumberSeries::getNextNumber('stock_movement'),
                'inventory_item_id' => $item->id,
                'movement_type' => StockMovement::TYPE_STOCK_ADJUSTMENT,
                'quantity' => $difference,
                'router_ids' => null,
                'from_holder_type' => 'warehouse',
                'from_holder_id' => null,
                'to_holder_type' => 'warehouse',
                'to_holder_id' => null,
                'ticket_id' => null,
                'reference_type' => 'adjustment',
                'reference_id' => null,
                'reason' => $data['reason'],
                'remarks' => $data['remarks'] ?? null,
                'item_condition' => 'good',
                'movement_date' => now()->toDateString(),
                'performed_by' => Auth::id(),
            ]);

            // Create adjustment audit record
            $adjustment = StockAdjustment::create([
                'adjustment_no' => NumberSeries::getNextNumber('stock_adjustment'),
                'inventory_item_id' => $item->id,
                'stock_movement_id' => $movement->id,
                'adjustment_type' => $difference > 0 ? StockAdjustment::TYPE_INCREASE : StockAdjustment::TYPE_DECREASE,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'difference' => $difference,
                'old_value' => $data['old_value'] ?? null,
                'new_value' => $data['new_value'] ?? null,
                'reason' => $data['reason'],
                'remarks' => $data['remarks'] ?? null,
                'adjusted_by' => Auth::id(),
                'adjusted_at' => now(),
            ]);

            $movement->update(['reference_id' => $adjustment->id]);

            Log::info('Stock Adjustment processed', [
                'adjustment_no' => $adjustment->adjustment_no,
                'item_id' => $item->id,
                'old_qty' => $oldQuantity,
                'new_qty' => $newQuantity,
                'difference' => $difference,
                'reason' => $data['reason'],
            ]);

            return $adjustment;
        });
    }

    // ══════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════

    /**
     * Get stock for a specific item (AJAX endpoint).
     */
    public function getItemStock(int $itemId): array
    {
        $item = InventoryItem::findOrFail($itemId);
        $warehouseBalance = StockBalance::warehouseBalance($itemId);

        return [
            'item_id' => $item->id,
            'item_code' => $item->item_code,
            'item_name' => $item->item_name,
            'item_type' => $item->item_type,
            'warehouse_stock' => $warehouseBalance,
            'total_stock' => $item->getTotalStock(),
        ];
    }

    /**
     * Get available routers (in stock at warehouse).
     */
    public function getAvailableRouters(): \Illuminate\Support\Collection
    {
        return InventoryItem::routers()
            ->active()
            ->whereHas('stockBalances', function ($q) {
                $q->warehouse()->where('quantity', '>', 0);
            })
            ->with(['stockBalances' => function ($q) {
                $q->warehouse();
            }])
            ->orderBy('item_name')
            ->get();
    }

    /**
     * Get available accessories (in stock at warehouse).
     */
    public function getAvailableAccessories(): \Illuminate\Support\Collection
    {
        return InventoryItem::accessories()
            ->active()
            ->whereHas('stockBalances', function ($q) {
                $q->warehouse()->where('quantity', '>', 0);
            })
            ->with(['stockBalances' => function ($q) {
                $q->warehouse();
            }])
            ->orderBy('item_name')
            ->get();
    }

    /**
     * Movements datatable.
     */
    public function getMovementsDatatable(array $params): array
    {
        $query = StockMovement::with(['inventoryItem', 'performer', 'ticket']);

        // Filters
        if (!empty($params['movement_type'])) {
            $query->ofType($params['movement_type']);
        }
        if (!empty($params['inventory_item_id'])) {
            $query->forItem($params['inventory_item_id']);
        }
        if (!empty($params['date_from']) || !empty($params['date_to'])) {
            $query->dateRange($params['date_from'] ?? null, $params['date_to'] ?? null);
        }

        $totalRecords = StockMovement::count();

        // Search
        $search = $params['search']['value'] ?? '';
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('movement_no', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%")
                  ->orWhereHas('inventoryItem', fn($q2) => $q2->where('item_name', 'like', "%{$search}%")
                      ->orWhere('item_code', 'like', "%{$search}%"));
            });
        }

        $filteredRecords = $query->count();

        // Sorting
        $orderColumn = $params['order'][0]['column'] ?? 0;
        $orderDir = $params['order'][0]['dir'] ?? 'desc';
        $sortable = [
            0 => 'movement_no',
            1 => 'movement_type',
            2 => 'movement_date',
            3 => 'quantity',
        ];
        $query->orderBy($sortable[$orderColumn] ?? 'created_at', $orderDir);

        $start = $params['start'] ?? 0;
        $length = $params['length'] ?? 10;
        $movements = $query->skip($start)->take($length)->get();

        $data = $movements->map(function ($m, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $m->id,
                'movement_no' => $m->movement_no,
                'item_code' => $m->inventoryItem->item_code ?? 'N/A',
                'item_name' => $m->inventoryItem->item_name ?? 'N/A',
                'movement_type' => $m->getTypeBadge(),
                'quantity' => $m->quantity,
                'router_ids' => $m->getRouterIdsDisplay(),
                'from_location' => $m->getFromLocation(),
                'to_location' => $m->getToLocation(),
                'ticket_no' => $m->ticket->ticket_no ?? '-',
                'condition' => $m->getConditionBadge(),
                'reason' => $m->reason ?? '-',
                'remarks' => $m->remarks ?? '-',
                'movement_date' => $m->movement_date?->format('d M Y'),
                'date_label' => $m->getDateLabel(),
                'performed_by' => $m->performer->name ?? 'N/A',
                'created_at' => $m->created_at?->format('d M Y H:i'),
            ];
        });

        return [
            'draw' => intval($params['draw'] ?? 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ];
    }

    /**
     * Stock Out datatable (list-only view).
     */
    public function getStockOutDatatable(array $params): array
    {
        $query = StockMovement::with(['inventoryItem', 'performer', 'ticket'])
            ->where('movement_type', StockMovement::TYPE_STOCK_OUT);

        if (!empty($params['date_from']) || !empty($params['date_to'])) {
            $query->dateRange($params['date_from'] ?? null, $params['date_to'] ?? null);
        }

        $totalRecords = StockMovement::where('movement_type', StockMovement::TYPE_STOCK_OUT)->count();

        $search = $params['search']['value'] ?? '';
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('movement_no', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('inventoryItem', fn($q2) => $q2->where('item_name', 'like', "%{$search}%")
                      ->orWhere('item_code', 'like', "%{$search}%"))
                  ->orWhereHas('ticket', fn($q2) => $q2->where('ticket_no', 'like', "%{$search}%"));
            });
        }

        $filteredRecords = $query->count();

        $orderColumn = $params['order'][0]['column'] ?? 0;
        $orderDir = $params['order'][0]['dir'] ?? 'desc';
        $sortable = [0 => 'movement_no', 1 => 'movement_date', 2 => 'quantity'];
        $query->orderBy($sortable[$orderColumn] ?? 'created_at', $orderDir);

        $start = $params['start'] ?? 0;
        $length = $params['length'] ?? 10;
        $movements = $query->skip($start)->take($length)->get();

        $data = $movements->map(function ($m, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $m->id,
                'movement_no' => $m->movement_no,
                'item_code' => $m->inventoryItem->item_code ?? 'N/A',
                'item_name' => $m->inventoryItem->item_name ?? 'N/A',
                'quantity' => abs($m->quantity),
                'router_ids' => $m->getRouterIdsDisplay(),
                'ticket_no' => $m->ticket->ticket_no ?? '-',
                'reason' => $m->reason ?? '-',
                'stockout_date' => $m->movement_date?->format('d M Y'),
                'performed_by' => $m->performer->name ?? 'System',
                'created_at' => $m->created_at?->format('d M Y H:i'),
            ];
        });

        return [
            'draw' => intval($params['draw'] ?? 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ];
    }

    /**
     * Stock Return datatable.
     */
    public function getStockReturnDatatable(array $params): array
    {
        $query = StockMovement::with(['inventoryItem', 'performer', 'ticket'])
            ->where('movement_type', StockMovement::TYPE_STOCK_RETURN);

        if (!empty($params['date_from']) || !empty($params['date_to'])) {
            $query->dateRange($params['date_from'] ?? null, $params['date_to'] ?? null);
        }

        $totalRecords = StockMovement::where('movement_type', StockMovement::TYPE_STOCK_RETURN)->count();

        $search = $params['search']['value'] ?? '';
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('movement_no', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('inventoryItem', fn($q2) => $q2->where('item_name', 'like', "%{$search}%")
                      ->orWhere('item_code', 'like', "%{$search}%"))
                  ->orWhereHas('ticket', fn($q2) => $q2->where('ticket_no', 'like', "%{$search}%"));
            });
        }

        $filteredRecords = $query->count();

        $orderColumn = $params['order'][0]['column'] ?? 0;
        $orderDir = $params['order'][0]['dir'] ?? 'desc';
        $sortable = [0 => 'movement_no', 1 => 'movement_date', 2 => 'quantity'];
        $query->orderBy($sortable[$orderColumn] ?? 'created_at', $orderDir);

        $start = $params['start'] ?? 0;
        $length = $params['length'] ?? 10;
        $movements = $query->skip($start)->take($length)->get();

        $data = $movements->map(function ($m, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $m->id,
                'movement_no' => $m->movement_no,
                'item_code' => $m->inventoryItem->item_code ?? 'N/A',
                'item_name' => $m->inventoryItem->item_name ?? 'N/A',
                'quantity' => $m->quantity,
                'router_ids' => $m->getRouterIdsDisplay(),
                'ticket_no' => $m->ticket->ticket_no ?? '-',
                'condition' => $m->getConditionBadge(),
                'reason' => $m->reason ?? '-',
                'stockreturn_date' => $m->movement_date?->format('d M Y'),
                'performed_by' => $m->performer->name ?? 'System',
                'created_at' => $m->created_at?->format('d M Y H:i'),
            ];
        });

        return [
            'draw' => intval($params['draw'] ?? 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ];
    }

    /**
     * Get inventory summary statistics for dashboard.
     */
    public function getSummaryStats(): array
    {
        return [
            'total_items' => InventoryItem::count(),
            'active_items' => InventoryItem::active()->count(),
            'total_routers' => InventoryItem::routers()->count(),
            'total_accessories' => InventoryItem::accessories()->count(),
            'low_stock_count' => $this->getLowStockCount(),
            'total_warehouse_stock' => StockBalance::where('holder_type', 'warehouse')->sum('quantity'),
            'today_movements' => StockMovement::whereDate('movement_date', today())->count(),
        ];
    }

    /**
     * Count items below reorder level in warehouse.
     */
    protected function getLowStockCount(): int
    {
        return InventoryItem::active()
            ->whereHas('stockBalances', function ($q) {
                $q->warehouse()
                  ->whereColumn('stock_balances.quantity', '<=', 'inventory_items.reorder_level');
            })
            ->count();
    }
}
