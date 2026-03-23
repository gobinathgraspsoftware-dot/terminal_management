<?php

namespace App\Services;

use App\Models\AccessoryUsage;
use App\Models\InventorySerial;
use App\Models\StockLedger;
use App\Models\StockBalance;
use App\Models\Ticket;
use App\Models\Depot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class AccessoryUsageService
{
    /**
     * Record accessory usage for a ticket.
     *
     * Rule: One ticket = one item / one quantity per accessory type.
     * Accessories can be: Used or Returned (via stock return).
     */
    public function recordUsage(array $data): AccessoryUsage
    {
        return DB::transaction(function () use ($data) {
            $ticketId = $data['ticket_id'];
            $modelId  = $data['model_id'];

            // Enforce: one ticket = one item / one quantity per model
            $existing = AccessoryUsage::where('ticket_id', $ticketId)
                ->where('model_id', $modelId)
                ->where('action', AccessoryUsage::ACTION_USED)
                ->whereNull('return_date')
                ->first();

            if ($existing) {
                throw new Exception(
                    'This accessory model is already recorded for this ticket. ' .
                    'Return the existing one first before adding another.'
                );
            }

            // Determine deduction source
            $fromLocType = $data['deducted_from_location_type'] ?? 'technician';
            $fromLocId   = $data['deducted_from_location_id'] ?? Auth::id();

            // Validate stock availability
            $balance = StockBalance::where('model_id', $modelId)
                ->where('location_type', $fromLocType)
                ->where('location_id', $fromLocId)
                ->first();

            $qty = $data['quantity'] ?? 1;

            if (!$balance || $balance->quantity_available < $qty) {
                throw new Exception(
                    'Insufficient accessory stock. Available: ' .
                    ($balance->quantity_available ?? 0) . ', Required: ' . $qty
                );
            }

            // For serial-tracked accessories
            $serialId = $data['serial_id'] ?? null;
            $serialNo = $data['serial_no'] ?? null;

            if ($serialId) {
                $serial = InventorySerial::findOrFail($serialId);
                $serial->update([
                    'current_status'        => InventorySerial::STATUS_INSTALLED,
                    'current_location_type'  => 'site',
                    'current_location_id'    => null,
                    'updated_by'             => Auth::id(),
                ]);
                $serialNo = $serial->serial_no;
            }

            // Create usage record
            $usage = AccessoryUsage::create([
                'ticket_id'                   => $ticketId,
                'serial_id'                   => $serialId,
                'model_id'                    => $modelId,
                'accessory_type'              => $data['accessory_type'] ?? 'other',
                'quantity'                    => $qty,
                'action'                      => AccessoryUsage::ACTION_USED,
                'serial_no'                   => $serialNo,
                'condition'                   => $data['condition'] ?? 'new',
                'remarks'                     => $data['remarks'] ?? null,
                'deducted_from_location_type' => $fromLocType,
                'deducted_from_location_id'   => $fromLocId,
                'created_by'                  => Auth::id(),
                'updated_by'                  => Auth::id(),
            ]);

            // Deduct stock balance
            $balance->decrement('quantity_on_hand', $qty);
            $balance->update(['last_movement_date' => now()->toDateString()]);

            // Create stock ledger entry
            $ticket = Ticket::find($ticketId);
            StockLedger::create([
                'transaction_date'   => now()->toDateString(),
                'transaction_no'     => $ticket ? $ticket->ticket_no : 'ACC-' . $usage->id,
                'transaction_type'   => 'install',
                'reference_type'     => 'accessory_usage',
                'reference_id'       => $usage->id,
                'serial_id'          => $serialId,
                'serial_no'          => $serialNo,
                'model_id'           => $modelId,
                'quantity'           => -$qty,
                'from_location_type' => $fromLocType,
                'from_location_id'   => $fromLocId,
                'remarks'            => 'Accessory used for ticket #' . ($ticket->ticket_no ?? $ticketId),
                'created_by'         => Auth::id(),
            ]);

            Log::info('Accessory usage recorded', [
                'ticket_id' => $ticketId,
                'model_id'  => $modelId,
                'quantity'  => $qty,
            ]);

            return $usage->fresh(['ticket', 'model', 'serial']);
        });
    }

    /**
     * Return an accessory (via stock return).
     */
    public function returnAccessory(AccessoryUsage $usage, array $data): AccessoryUsage
    {
        if (!$usage->canReturn()) {
            throw new Exception('This accessory cannot be returned. It may already be returned or not in "used" status.');
        }

        return DB::transaction(function () use ($usage, $data) {
            $returnDepotId = $data['return_depot_id'];
            $returnCondition = $data['return_condition'] ?? 'good';

            // Update the usage record
            $usage->update([
                'action'           => AccessoryUsage::ACTION_RETURNED,
                'return_date'      => now()->toDateString(),
                'return_condition' => $returnCondition,
                'return_depot_id'  => $returnDepotId,
                'updated_by'       => Auth::id(),
            ]);

            // If serial tracked, update serial status
            if ($usage->serial_id) {
                InventorySerial::where('id', $usage->serial_id)->update([
                    'current_status'        => InventorySerial::STATUS_IN_STOCK,
                    'current_location_type'  => 'depot',
                    'current_location_id'    => $returnDepotId,
                    'updated_by'             => Auth::id(),
                ]);
            }

            // Increase depot stock balance
            $balance = StockBalance::firstOrCreate(
                [
                    'model_id'      => $usage->model_id,
                    'location_type' => 'depot',
                    'location_id'   => $returnDepotId,
                ],
                ['quantity_on_hand' => 0, 'quantity_reserved' => 0]
            );
            $balance->increment('quantity_on_hand', $usage->quantity);
            $balance->update(['last_movement_date' => now()->toDateString()]);

            // Create stock ledger entry for return
            $ticket = $usage->ticket;
            StockLedger::create([
                'transaction_date'   => now()->toDateString(),
                'transaction_no'     => $ticket ? $ticket->ticket_no : 'ACCR-' . $usage->id,
                'transaction_type'   => 'return_from_tech',
                'reference_type'     => 'accessory_usage',
                'reference_id'       => $usage->id,
                'serial_id'          => $usage->serial_id,
                'serial_no'          => $usage->serial_no,
                'model_id'           => $usage->model_id,
                'quantity'           => $usage->quantity,
                'to_location_type'   => 'depot',
                'to_location_id'     => $returnDepotId,
                'remarks'            => 'Accessory returned: ' . $returnCondition,
                'created_by'         => Auth::id(),
            ]);

            Log::info('Accessory returned', [
                'usage_id'     => $usage->id,
                'ticket_id'    => $usage->ticket_id,
                'return_depot' => $returnDepotId,
                'condition'    => $returnCondition,
            ]);

            return $usage->fresh(['ticket', 'model', 'returnDepot']);
        });
    }

    /**
     * Get accessory usage history for a ticket.
     */
    public function getUsageByTicket(int $ticketId)
    {
        return AccessoryUsage::with(['model.category', 'serial', 'createdBy', 'returnDepot'])
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all accessory usage records with filters.
     */
    public function getUsageList($request)
    {
        $query = AccessoryUsage::with(['ticket', 'model.category', 'serial', 'createdBy']);

        if ($type = $request->input('accessory_type')) {
            $query->where('accessory_type', $type);
        }
        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }
        if ($ticketId = $request->input('ticket_id')) {
            $query->where('ticket_id', $ticketId);
        }
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query->orderBy('created_at', 'desc');
    }
}
