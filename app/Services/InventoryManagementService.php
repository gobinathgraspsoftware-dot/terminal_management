<?php

namespace App\Services;

use App\Models\StockIn;
use App\Models\StockInLine;
use App\Models\StockOut;
use App\Models\StockOutLine;
use App\Models\StockLedger;
use App\Models\StockBalance;
use App\Models\InventorySerial;
use App\Models\TerminalModel;
use App\Models\TerminalCategory;
use App\Models\NumberSeries;
use App\Models\Depot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class InventoryManagementService
{
    // =========================================================================
    // STOCK IN
    // =========================================================================

    /**
     * Create a new Stock In document.
     */
    public function createStockIn(array $data): StockIn
    {
        return DB::transaction(function () use ($data) {
            if (empty($data['stock_in_no'])) {
                $data['stock_in_no'] = NumberSeries::getNextNumber('stock_in');
            }

            $stockIn = StockIn::create([
                'stock_in_no'      => $data['stock_in_no'],
                'stock_in_date'    => $data['stock_in_date'],
                'depot_id'         => $data['depot_id'],
                'source_type'      => $data['source_type'] ?? 'manual',
                'source_reference' => $data['source_reference'] ?? null,
                'remarks'          => $data['remarks'] ?? null,
                'status'           => StockIn::STATUS_DRAFT,
                'created_by'       => Auth::id(),
                'updated_by'       => Auth::id(),
            ]);

            $totalItems = 0;
            foreach ($data['lines'] as $index => $lineData) {
                if (empty($lineData['model_id']) || empty($lineData['quantity']) || $lineData['quantity'] <= 0) {
                    continue;
                }

                StockInLine::create([
                    'stock_in_id' => $stockIn->id,
                    'line_no'     => $index + 1,
                    'model_id'    => $lineData['model_id'],
                    'serial_id'   => $lineData['serial_id'] ?? null,
                    'serial_no'   => $lineData['serial_no'] ?? null,
                    'quantity'    => $lineData['quantity'],
                    'unit_cost'   => $lineData['unit_cost'] ?? null,
                    'condition'   => $lineData['condition'] ?? 'good',
                    'remarks'     => $lineData['remarks'] ?? null,
                ]);

                $totalItems += $lineData['quantity'];
            }

            $stockIn->update(['total_items' => $totalItems]);

            Log::info('Stock In created', ['stock_in_no' => $stockIn->stock_in_no, 'user' => Auth::id()]);

            return $stockIn->fresh(['lines.model']);
        });
    }

    /**
     * Post a Stock In - updates inventory and stock ledger.
     */
    public function postStockIn(StockIn $stockIn): StockIn
    {
        if (!$stockIn->isDraft()) {
            throw new Exception('Only draft Stock In documents can be posted.');
        }

        return DB::transaction(function () use ($stockIn) {
            $stockIn->load(['lines.model']);

            foreach ($stockIn->lines as $line) {
                $model = $line->model;

                // For serial-tracked items, create or update inventory_serials
                if ($model && $model->is_serial_tracked && !empty($line->serial_no)) {
                    $serial = InventorySerial::where('serial_no', $line->serial_no)->first();

                    if ($serial) {
                        // Update existing serial back to in_stock
                        $serial->update([
                            'current_status'        => InventorySerial::STATUS_IN_STOCK,
                            'current_location_type'  => InventorySerial::LOCATION_TYPE_DEPOT,
                            'current_location_id'    => $stockIn->depot_id,
                            'updated_by'             => Auth::id(),
                        ]);
                        $line->update(['serial_id' => $serial->id]);
                    } else {
                        // Create new serial
                        $serial = InventorySerial::create([
                            'serial_no'              => $line->serial_no,
                            'model_id'               => $line->model_id,
                            'current_status'         => InventorySerial::STATUS_IN_STOCK,
                            'current_location_type'   => InventorySerial::LOCATION_TYPE_DEPOT,
                            'current_location_id'     => $stockIn->depot_id,
                            'purchase_price'          => $line->unit_cost,
                            'created_by'              => Auth::id(),
                            'updated_by'              => Auth::id(),
                        ]);
                        $line->update(['serial_id' => $serial->id]);
                    }
                }

                // Create stock ledger entry
                StockLedger::create([
                    'transaction_date'   => $stockIn->stock_in_date,
                    'transaction_no'     => $stockIn->stock_in_no,
                    'transaction_type'   => 'grn_in', // Reuse existing type for stock in
                    'reference_type'     => 'stock_in',
                    'reference_id'       => $stockIn->id,
                    'serial_id'          => $line->serial_id,
                    'serial_no'          => $line->serial_no,
                    'model_id'           => $line->model_id,
                    'quantity'           => $line->quantity,
                    'to_location_type'   => 'depot',
                    'to_location_id'     => $stockIn->depot_id,
                    'unit_cost'          => $line->unit_cost,
                    'total_cost'         => ($line->unit_cost ?? 0) * $line->quantity,
                    'remarks'            => 'Stock In: ' . ($stockIn->source_type ?? 'manual'),
                    'created_by'         => Auth::id(),
                ]);

                // Update stock balance
                $this->updateStockBalance(
                    $line->model_id,
                    'depot',
                    $stockIn->depot_id,
                    $line->quantity
                );
            }

            $stockIn->update([
                'status'    => StockIn::STATUS_POSTED,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
            ]);

            Log::info('Stock In posted', ['stock_in_no' => $stockIn->stock_in_no]);

            return $stockIn->fresh();
        });
    }

    // =========================================================================
    // STOCK OUT
    // =========================================================================

    /**
     * Create a new Stock Out document.
     */
    public function createStockOut(array $data): StockOut
    {
        return DB::transaction(function () use ($data) {
            if (empty($data['stock_out_no'])) {
                $data['stock_out_no'] = NumberSeries::getNextNumber('stock_out');
            }

            $stockOut = StockOut::create([
                'stock_out_no'        => $data['stock_out_no'],
                'stock_out_date'      => $data['stock_out_date'],
                'out_type'            => $data['out_type'],
                'from_depot_id'       => $data['from_depot_id'] ?? null,
                'from_technician_id'  => $data['from_technician_id'] ?? null,
                'to_site_id'          => $data['to_site_id'] ?? null,
                'to_vendor_id'        => $data['to_vendor_id'] ?? null,
                'remarks'             => $data['remarks'] ?? null,
                'status'              => StockOut::STATUS_DRAFT,
                'created_by'          => Auth::id(),
                'updated_by'          => Auth::id(),
            ]);

            $totalItems = 0;
            foreach ($data['lines'] as $index => $lineData) {
                if (empty($lineData['model_id']) || empty($lineData['quantity']) || $lineData['quantity'] <= 0) {
                    continue;
                }

                StockOutLine::create([
                    'stock_out_id' => $stockOut->id,
                    'line_no'      => $index + 1,
                    'model_id'     => $lineData['model_id'],
                    'serial_id'    => $lineData['serial_id'] ?? null,
                    'serial_no'    => $lineData['serial_no'] ?? null,
                    'quantity'     => $lineData['quantity'],
                    'condition'    => $lineData['condition'] ?? 'good',
                    'remarks'      => $lineData['remarks'] ?? null,
                ]);

                $totalItems += $lineData['quantity'];
            }

            $stockOut->update(['total_items' => $totalItems]);

            return $stockOut->fresh(['lines.model']);
        });
    }

    /**
     * Post a Stock Out - updates inventory and stock ledger.
     */
    public function postStockOut(StockOut $stockOut): StockOut
    {
        if (!$stockOut->isDraft()) {
            throw new Exception('Only draft Stock Out documents can be posted.');
        }

        return DB::transaction(function () use ($stockOut) {
            $stockOut->load(['lines.model']);

            $fromLocationType = $stockOut->from_depot_id ? 'depot' : 'technician';
            $fromLocationId   = $stockOut->from_depot_id ?? $stockOut->from_technician_id;

            // Determine serial status based on out_type
            $newStatus = match ($stockOut->out_type) {
                StockOut::TYPE_DIRECT_TO_SITE   => InventorySerial::STATUS_INSTALLED,
                StockOut::TYPE_WASTAGE          => InventorySerial::STATUS_WASTED,
                StockOut::TYPE_RETURN_TO_VENDOR  => InventorySerial::STATUS_RETURNED_TO_VENDOR,
                default                          => InventorySerial::STATUS_WASTED,
            };

            $toLocationType = match ($stockOut->out_type) {
                StockOut::TYPE_DIRECT_TO_SITE   => 'site',
                StockOut::TYPE_RETURN_TO_VENDOR  => 'vendor',
                default                          => $fromLocationType,
            };
            $toLocationId = match ($stockOut->out_type) {
                StockOut::TYPE_DIRECT_TO_SITE   => $stockOut->to_site_id,
                StockOut::TYPE_RETURN_TO_VENDOR  => $stockOut->to_vendor_id,
                default                          => $fromLocationId,
            };

            foreach ($stockOut->lines as $line) {
                // Validate stock availability
                $balance = StockBalance::where('model_id', $line->model_id)
                    ->where('location_type', $fromLocationType)
                    ->where('location_id', $fromLocationId)
                    ->first();

                if (!$balance || $balance->quantity_available < $line->quantity) {
                    $modelName = $line->model ? $line->model->model_name : $line->model_id;
                    $availableQty = $balance ? $balance->quantity_available : 0;
                    throw new Exception(
                        "Insufficient stock for model [{$modelName}]. " .
                        "Available: {$availableQty}, Required: {$line->quantity}"
                    );
                }

                // Update serial status
                if ($line->serial_id) {
                    InventorySerial::where('id', $line->serial_id)->update([
                        'current_status'        => $newStatus,
                        'current_location_type'  => $toLocationType,
                        'current_location_id'    => $toLocationId,
                        'updated_by'             => Auth::id(),
                    ]);
                }

                // Create stock ledger entry
                $txnType = match ($stockOut->out_type) {
                    StockOut::TYPE_WASTAGE         => 'wastage',
                    StockOut::TYPE_RETURN_TO_VENDOR => 'return_to_vendor',
                    StockOut::TYPE_DIRECT_TO_SITE  => 'install',
                    default                         => 'wastage',
                };

                StockLedger::create([
                    'transaction_date'    => $stockOut->stock_out_date,
                    'transaction_no'      => $stockOut->stock_out_no,
                    'transaction_type'    => $txnType,
                    'reference_type'      => 'stock_out',
                    'reference_id'        => $stockOut->id,
                    'serial_id'           => $line->serial_id,
                    'serial_no'           => $line->serial_no,
                    'model_id'            => $line->model_id,
                    'quantity'            => -$line->quantity,
                    'from_location_type'  => $fromLocationType,
                    'from_location_id'    => $fromLocationId,
                    'to_location_type'    => $toLocationType,
                    'to_location_id'      => $toLocationId,
                    'remarks'             => 'Stock Out: ' . ($stockOut->out_type ?? 'other'),
                    'created_by'          => Auth::id(),
                ]);

                // Decrease stock balance
                $this->updateStockBalance(
                    $line->model_id,
                    $fromLocationType,
                    $fromLocationId,
                    -$line->quantity
                );
            }

            $stockOut->update([
                'status'    => StockOut::STATUS_POSTED,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
            ]);

            return $stockOut->fresh();
        });
    }

    // =========================================================================
    // CANCEL
    // =========================================================================

    public function cancelStockIn(StockIn $stockIn): StockIn
    {
        if (!$stockIn->isDraft()) {
            throw new Exception('Only draft Stock In documents can be cancelled.');
        }
        $stockIn->update(['status' => StockIn::STATUS_CANCELLED, 'updated_by' => Auth::id()]);
        return $stockIn->fresh();
    }

    public function cancelStockOut(StockOut $stockOut): StockOut
    {
        if (!$stockOut->isDraft()) {
            throw new Exception('Only draft Stock Out documents can be cancelled.');
        }
        $stockOut->update(['status' => StockOut::STATUS_CANCELLED, 'updated_by' => Auth::id()]);
        return $stockOut->fresh();
    }

    // =========================================================================
    // INVENTORY HUB - Summary Data
    // =========================================================================

    /**
     * Get inventory summary grouped by Router vs Accessories.
     */
    public function getInventorySummary(): array
    {
        $categories = TerminalCategory::where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        $routerCategoryIds = $categories->where('category_type', 'router')->pluck('id')->toArray();
        $accessoryCategoryIds = $categories->whereIn('category_type', ['sim', 'accessory'])->pluck('id')->toArray();

        // Router summary (serial-tracked, individual)
        $routerStock = DB::table('inventory_serials')
            ->join('terminal_models', 'inventory_serials.model_id', '=', 'terminal_models.id')
            ->whereIn('terminal_models.category_id', $routerCategoryIds)
            ->whereNull('inventory_serials.deleted_at')
            ->select(
                'inventory_serials.current_status',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('inventory_serials.current_status')
            ->get();

        // Accessories summary (quantity-based)
        $accessoryStock = DB::table('stock_balances')
            ->join('terminal_models', 'stock_balances.model_id', '=', 'terminal_models.id')
            ->whereIn('terminal_models.category_id', $accessoryCategoryIds)
            ->select(
                DB::raw('SUM(stock_balances.quantity_on_hand) as total_on_hand'),
                DB::raw('SUM(stock_balances.quantity_available) as total_available'),
                DB::raw('SUM(stock_balances.quantity_reserved) as total_reserved')
            )
            ->first();

        return [
            'router_stock'    => $routerStock,
            'accessory_stock' => $accessoryStock,
            'router_category_ids'    => $routerCategoryIds,
            'accessory_category_ids' => $accessoryCategoryIds,
        ];
    }

    /**
     * Get router inventory with DataTable format.
     */
    public function getRouterInventory($request)
    {
        $categories = TerminalCategory::where('category_type', 'router')
            ->where('status', 'active')
            ->pluck('id');

        $query = InventorySerial::with(['model.category'])
            ->whereHas('model', function ($q) use ($categories) {
                $q->whereIn('category_id', $categories);
            });

        // Apply filters
        if ($status = $request->input('status')) {
            $query->where('current_status', $status);
        }
        if ($depotId = $request->input('depot_id')) {
            $query->where('current_location_type', 'depot')
                  ->where('current_location_id', $depotId);
        }
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('serial_no', 'like', "%{$search}%")
                  ->orWhereHas('model', fn($mq) => $mq->where('model_name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    /**
     * Get accessories inventory with DataTable format.
     */
    public function getAccessoryInventory($request)
    {
        $categories = TerminalCategory::whereIn('category_type', ['sim', 'accessory'])
            ->where('status', 'active')
            ->pluck('id');

        $query = StockBalance::with(['model.category'])
            ->whereHas('model', function ($q) use ($categories) {
                $q->whereIn('category_id', $categories);
            })
            ->where('quantity_on_hand', '>', 0);

        if ($depotId = $request->input('depot_id')) {
            $query->where('location_type', 'depot')
                  ->where('location_id', $depotId);
        }

        return $query;
    }

    // =========================================================================
    // HELPER: Update Stock Balance
    // =========================================================================

    protected function updateStockBalance(int $modelId, string $locationType, int $locationId, float $quantityChange): void
    {
        $balance = StockBalance::firstOrCreate(
            [
                'model_id'      => $modelId,
                'location_type' => $locationType,
                'location_id'   => $locationId,
            ],
            [
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
            ]
        );

        $balance->increment('quantity_on_hand', $quantityChange);
        $balance->update(['last_movement_date' => now()->toDateString()]);
    }
}
