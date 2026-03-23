<?php

namespace App\Services;

use App\Models\InventorySerial;
use App\Models\StockLedger;
use App\Models\StockBalance;
use App\Models\Ticket;
use App\Models\JobCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class StockDeductionService
{
    /**
     * Auto-deduct stock when ticket status changes to done_success / closed.
     *
     * Stock rules per SRS:
     * - Router & Accessories → inventory linked (auto deduct)
     * - Terminal & Project → NOT linked to inventory
     */
    public function autoDeductOnTicketCompletion(Ticket $ticket): array
    {
        $deductions = [];

        // Load category to determine if inventory is linked
        $ticket->loadMissing(['jobCategory']);
        $category = $ticket->jobCategory;

        if (!$category) {
            return $deductions;
        }

        // Only Router and Accessories categories are inventory-linked
        $inventoryLinkedSlugs = [
            JobCategory::SLUG_ROUTER,
            JobCategory::SLUG_ACCESSORIES,
        ];

        if (!in_array($category->slug, $inventoryLinkedSlugs)) {
            Log::info('Ticket category not inventory-linked, skipping auto deduction', [
                'ticket_no' => $ticket->ticket_no,
                'category'  => $category->slug,
            ]);
            return $deductions;
        }

        return DB::transaction(function () use ($ticket, $category, &$deductions) {
            // For Router category: deduct the router_id serial from technician stock
            if ($category->slug === JobCategory::SLUG_ROUTER && !empty($ticket->router_id)) {
                $deduction = $this->deductSerialByNumber($ticket->router_id, $ticket);
                if ($deduction) {
                    $deductions[] = $deduction;
                }
            }

            // For Accessories category: deduct from accessory_usages table
            // (handled by AccessoryUsageService — already deducted at usage time)

            return $deductions;
        });
    }

    /**
     * Deduct a serial by its serial number (router_id field on ticket).
     */
    protected function deductSerialByNumber(string $serialNo, Ticket $ticket): ?array
    {
        $serial = InventorySerial::where('serial_no', $serialNo)->first();

        if (!$serial) {
            Log::warning('Serial not found for auto deduction', [
                'serial_no' => $serialNo,
                'ticket_no' => $ticket->ticket_no,
            ]);
            return null;
        }

        // Only deduct if issued to technician
        if ($serial->current_status !== InventorySerial::STATUS_ISSUED_TO_TECH) {
            Log::info('Serial not in issued status, skipping deduction', [
                'serial_no' => $serialNo,
                'status'    => $serial->current_status,
            ]);
            return null;
        }

        $prevLocationType = $serial->current_location_type;
        $prevLocationId   = $serial->current_location_id;

        // Update serial: mark as installed at site (if site available) or just deployed
        $destType = 'site';
        $destId   = $ticket->site_id ?? null;

        if (!$destId) {
            // No site linked, just mark as deployed (stay with technician conceptually)
            $serial->update([
                'current_status' => InventorySerial::STATUS_INSTALLED,
                'updated_by'     => Auth::id(),
            ]);
        } else {
            $serial->update([
                'current_status'        => InventorySerial::STATUS_INSTALLED,
                'current_location_type'  => $destType,
                'current_location_id'    => $destId,
                'updated_by'             => Auth::id(),
            ]);
        }

        // Create stock ledger entry
        StockLedger::create([
            'transaction_date'   => now()->toDateString(),
            'transaction_no'     => $ticket->ticket_no,
            'transaction_type'   => 'install',
            'reference_type'     => 'ticket',
            'reference_id'       => $ticket->id,
            'serial_id'          => $serial->id,
            'serial_no'          => $serial->serial_no,
            'model_id'           => $serial->model_id,
            'quantity'           => -1,
            'from_location_type' => $prevLocationType,
            'from_location_id'   => $prevLocationId,
            'to_location_type'   => $destType,
            'to_location_id'     => $destId,
            'remarks'            => "Auto-deduction: Ticket #{$ticket->ticket_no} completed",
            'created_by'         => Auth::id(),
        ]);

        // Decrease technician stock balance
        if ($prevLocationType && $prevLocationId) {
            $balance = StockBalance::where('model_id', $serial->model_id)
                ->where('location_type', $prevLocationType)
                ->where('location_id', $prevLocationId)
                ->first();

            if ($balance) {
                $balance->decrement('quantity_on_hand', 1);
                $balance->update(['last_movement_date' => now()->toDateString()]);
            }
        }

        Log::info('Auto stock deduction completed', [
            'serial_no' => $serialNo,
            'ticket_no' => $ticket->ticket_no,
            'from'      => "{$prevLocationType}:{$prevLocationId}",
        ]);

        return [
            'serial_no'  => $serialNo,
            'serial_id'  => $serial->id,
            'model_id'   => $serial->model_id,
            'from_type'  => $prevLocationType,
            'from_id'    => $prevLocationId,
            'to_type'    => $destType,
            'to_id'      => $destId,
        ];
    }

    /**
     * Check if a ticket should trigger auto deduction.
     */
    public function shouldAutoDeduct(Ticket $ticket, string $newStatus): bool
    {
        // Only trigger on done_success or closed
        $triggerStatuses = [Ticket::STATUS_DONE_SUCCESS, Ticket::STATUS_CLOSED];

        if (!in_array($newStatus, $triggerStatuses)) {
            return false;
        }

        // Must have a category
        $ticket->loadMissing('jobCategory');
        if (!$ticket->jobCategory) {
            return false;
        }

        // Only inventory-linked categories
        return in_array($ticket->jobCategory->slug, [
            JobCategory::SLUG_ROUTER,
            JobCategory::SLUG_ACCESSORIES,
        ]);
    }
}
