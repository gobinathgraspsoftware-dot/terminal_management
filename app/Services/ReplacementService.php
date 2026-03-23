<?php

namespace App\Services;

use App\Models\Replacement;
use App\Models\InventorySerial;
use App\Models\StockLedger;
use App\Models\StockBalance;
use App\Models\NumberSeries;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class ReplacementService
{
    /**
     * Create a replacement record.
     *
     * Flow:
     * 1. Admin assigns new unit
     * 2. Technician updates old unit
     * 3. Update Ticket + Inventory
     */
    public function createReplacement(array $data): Replacement
    {
        return DB::transaction(function () use ($data) {
            // Generate replacement number
            if (empty($data['replacement_no'])) {
                $data['replacement_no'] = NumberSeries::getNextNumber('replacement');
            }

            // Validate new serial is available
            if (!empty($data['new_serial_id'])) {
                $newSerial = InventorySerial::findOrFail($data['new_serial_id']);
                if (!in_array($newSerial->current_status, [
                    InventorySerial::STATUS_IN_STOCK,
                    InventorySerial::STATUS_ISSUED_TO_TECH,
                ])) {
                    throw new Exception(
                        "New serial [{$newSerial->serial_no}] is not available for replacement. " .
                        "Current status: {$newSerial->current_status}"
                    );
                }
            }

            $replacement = Replacement::create([
                'replacement_no'        => $data['replacement_no'],
                'replacement_date'      => $data['replacement_date'],
                'ticket_id'             => $data['ticket_id'] ?? null,
                'technician_id'         => $data['technician_id'] ?? null,
                'site_id'               => $data['site_id'] ?? null,
                'old_serial_id'         => $data['old_serial_id'] ?? null,
                'old_serial_no'         => $data['old_serial_no'] ?? null,
                'old_model_id'          => $data['old_model_id'] ?? null,
                'new_serial_id'         => $data['new_serial_id'] ?? null,
                'new_serial_no'         => $data['new_serial_no'] ?? null,
                'new_model_id'          => $data['new_model_id'] ?? null,
                'old_device_condition'  => $data['old_device_condition'] ?? 'damaged',
                'old_device_destination' => $data['old_device_destination'] ?? 'return_to_depot',
                'return_depot_id'       => $data['return_depot_id'] ?? null,
                'reason'                => $data['reason'] ?? null,
                'remarks'               => $data['remarks'] ?? null,
                'status'                => Replacement::STATUS_DRAFT,
                'created_by'            => Auth::id(),
                'updated_by'            => Auth::id(),
            ]);

            Log::info('Replacement created', [
                'replacement_no' => $replacement->replacement_no,
                'old_serial'     => $replacement->old_serial_no,
                'new_serial'     => $replacement->new_serial_no,
            ]);

            return $replacement->fresh([
                'ticket', 'technician', 'oldSerial', 'newSerial',
                'oldModel', 'newModel', 'returnDepot',
            ]);
        });
    }

    /**
     * Complete a replacement - execute the swap.
     *
     * Steps:
     * 1. New device → installed at site (or issued to tech)
     * 2. Old device → returned to depot / vendor / wasted
     * 3. Update ticket with new device IDs
     * 4. Create stock ledger entries for both movements
     */
    public function completeReplacement(Replacement $replacement): Replacement
    {
        if (!$replacement->isDraft()) {
            throw new Exception('Only draft replacements can be completed.');
        }

        return DB::transaction(function () use ($replacement) {
            $replacement->load(['ticket', 'oldSerial', 'newSerial']);

            // ── Step 1: Install new device ──
            if ($replacement->new_serial_id) {
                $newSerial = $replacement->newSerial;
                $previousLocationType = $newSerial->current_location_type;
                $previousLocationId   = $newSerial->current_location_id;

                // Determine destination (site if available, else technician)
                $destType = $replacement->site_id ? 'site' : 'technician';
                $destId   = $replacement->site_id ?? $replacement->technician_id;
                $newStatus = $replacement->site_id
                    ? InventorySerial::STATUS_INSTALLED
                    : InventorySerial::STATUS_ISSUED_TO_TECH;

                $newSerial->update([
                    'current_status'        => $newStatus,
                    'current_location_type'  => $destType,
                    'current_location_id'    => $destId,
                    'updated_by'             => Auth::id(),
                ]);

                // Ledger: replacement_in (new device going to site/tech)
                StockLedger::create([
                    'transaction_date'   => $replacement->replacement_date,
                    'transaction_no'     => $replacement->replacement_no,
                    'transaction_type'   => 'replacement_in',
                    'reference_type'     => 'replacement',
                    'reference_id'       => $replacement->id,
                    'serial_id'          => $newSerial->id,
                    'serial_no'          => $newSerial->serial_no,
                    'model_id'           => $replacement->new_model_id,
                    'quantity'           => -1,
                    'from_location_type' => $previousLocationType,
                    'from_location_id'   => $previousLocationId,
                    'to_location_type'   => $destType,
                    'to_location_id'     => $destId,
                    'remarks'            => 'Replacement: new device installed',
                    'created_by'         => Auth::id(),
                ]);

                // Decrease stock from previous location
                $this->adjustStockBalance($replacement->new_model_id, $previousLocationType, $previousLocationId, -1);
            }

            // ── Step 2: Handle old device ──
            if ($replacement->old_serial_id) {
                $oldSerial = $replacement->oldSerial;
                $oldPrevLocType = $oldSerial->current_location_type;
                $oldPrevLocId   = $oldSerial->current_location_id;

                // Determine old device destination
                $oldDestLocType = 'depot';
                $oldDestLocId   = $replacement->return_depot_id;
                $oldNewStatus   = InventorySerial::STATUS_IN_STOCK;

                if ($replacement->old_device_destination === 'return_to_vendor') {
                    $oldNewStatus = InventorySerial::STATUS_RETURNED_TO_VENDOR;
                    $oldDestLocType = 'vendor';
                    $oldDestLocId   = null;
                } elseif ($replacement->old_device_destination === 'wastage') {
                    $oldNewStatus = InventorySerial::STATUS_WASTED;
                }

                $oldSerial->update([
                    'current_status'        => $oldNewStatus,
                    'current_location_type'  => $oldDestLocType,
                    'current_location_id'    => $oldDestLocId,
                    'updated_by'             => Auth::id(),
                ]);

                // Ledger: replacement_out (old device leaving site/tech)
                StockLedger::create([
                    'transaction_date'   => $replacement->replacement_date,
                    'transaction_no'     => $replacement->replacement_no,
                    'transaction_type'   => 'replacement_out',
                    'reference_type'     => 'replacement',
                    'reference_id'       => $replacement->id,
                    'serial_id'          => $oldSerial->id,
                    'serial_no'          => $oldSerial->serial_no,
                    'model_id'           => $replacement->old_model_id,
                    'quantity'           => 1,
                    'from_location_type' => $oldPrevLocType,
                    'from_location_id'   => $oldPrevLocId,
                    'to_location_type'   => $oldDestLocType,
                    'to_location_id'     => $oldDestLocId,
                    'remarks'            => 'Replacement: old device removed (' . $replacement->old_device_condition . ')',
                    'created_by'         => Auth::id(),
                ]);

                // If returning to depot, increase depot stock
                if ($replacement->old_device_destination === 'return_to_depot' && $oldDestLocId) {
                    $this->adjustStockBalance($replacement->old_model_id, 'depot', $oldDestLocId, 1);
                }
            }

            // ── Step 3: Update ticket with new device reference ──
            if ($replacement->ticket_id && $replacement->ticket) {
                $ticketUpdate = [];
                // Determine if it's a router or terminal based on the model's category
                $newModel = $replacement->newModel ?? TerminalModel::find($replacement->new_model_id);
                if ($newModel && $newModel->category) {
                    $catType = $newModel->category->category_type;
                    if ($catType === 'router') {
                        $ticketUpdate['router_id'] = $replacement->new_serial_no;
                        if ($replacement->old_serial_no) {
                            // Keep reference to old terminal
                        }
                    } elseif ($catType === 'terminal') {
                        $ticketUpdate['old_terminal_id'] = $replacement->old_serial_no;
                        $ticketUpdate['terminal_id'] = $replacement->new_serial_no;
                    }
                }
                if (!empty($ticketUpdate)) {
                    $replacement->ticket->update($ticketUpdate);
                }
            }

            // ── Step 4: Mark replacement as completed ──
            $replacement->update([
                'status'       => Replacement::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => Auth::id(),
                'updated_by'   => Auth::id(),
            ]);

            Log::info('Replacement completed', [
                'replacement_no' => $replacement->replacement_no,
                'old_serial'     => $replacement->old_serial_no,
                'new_serial'     => $replacement->new_serial_no,
            ]);

            return $replacement->fresh();
        });
    }

    /**
     * Cancel a replacement.
     */
    public function cancelReplacement(Replacement $replacement): Replacement
    {
        if (!$replacement->isDraft()) {
            throw new Exception('Only draft replacements can be cancelled.');
        }

        $replacement->update([
            'status'     => Replacement::STATUS_CANCELLED,
            'updated_by' => Auth::id(),
        ]);

        return $replacement->fresh();
    }

    /**
     * Get available serials for replacement (technician's stock).
     */
    public function getAvailableSerialsForReplacement(int $technicianId, ?int $modelId = null)
    {
        $query = InventorySerial::with('model')
            ->where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->where('current_status', InventorySerial::STATUS_ISSUED_TO_TECH);

        if ($modelId) {
            $query->where('model_id', $modelId);
        }

        return $query->orderBy('serial_no')->get();
    }

    /**
     * Adjust stock balance helper.
     */
    protected function adjustStockBalance(int $modelId, string $locationType, ?int $locationId, float $qty): void
    {
        if (!$locationId) return;

        $balance = StockBalance::firstOrCreate(
            [
                'model_id'      => $modelId,
                'location_type' => $locationType,
                'location_id'   => $locationId,
            ],
            ['quantity_on_hand' => 0, 'quantity_reserved' => 0]
        );

        $balance->increment('quantity_on_hand', $qty);
        $balance->update(['last_movement_date' => now()->toDateString()]);
    }
}
