<?php

namespace App\Observers;

use App\Models\InventorySerial;
use App\Models\AuditTrail;
use Illuminate\Support\Facades\Log;

class InventorySerialObserver
{
    /**
     * Handle the created event.
     */
    public function created(InventorySerial $serial): void
    {
        $this->logAudit($serial, AuditTrail::EVENT_CREATED, null, [
            'serial_no'             => $serial->serial_no,
            'model_id'              => $serial->model_id,
            'current_status'        => $serial->current_status,
            'current_location_type' => $serial->current_location_type,
            'current_location_id'   => $serial->current_location_id,
            'grn_id'                => $serial->grn_id,
            'po_id'                 => $serial->po_id,
            'purchase_price'        => $serial->purchase_price,
        ]);
    }

    /**
     * Handle the updated event — focus on status change logging.
     */
    public function updated(InventorySerial $serial): void
    {
        $changes = $serial->getChanges();
        $original = $serial->getOriginal();

        // Build old/new values from what actually changed
        $oldValues = [];
        $newValues = [];

        foreach ($changes as $key => $newValue) {
            if (in_array($key, ['updated_at', 'updated_by'])) {
                continue;
            }
            $oldValues[$key] = $original[$key] ?? null;
            $newValues[$key] = $newValue;
        }

        if (empty($oldValues)) {
            return;
        }

        // Determine event type based on what changed
        $event = AuditTrail::EVENT_UPDATED;

        // Special logging for status changes
        if (isset($changes['current_status'])) {
            $oldStatus = $original['current_status'] ?? 'unknown';
            $newStatus = $changes['current_status'];

            Log::info("Serial [{$serial->serial_no}] status changed: {$oldStatus} → {$newStatus}", [
                'serial_id'  => $serial->id,
                'serial_no'  => $serial->serial_no,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'old_location_type' => $original['current_location_type'] ?? null,
                'old_location_id'   => $original['current_location_id'] ?? null,
                'new_location_type' => $changes['current_location_type'] ?? $serial->current_location_type,
                'new_location_id'   => $changes['current_location_id'] ?? $serial->current_location_id,
                'user_id' => auth()->id(),
            ]);
        }

        // Special logging for location changes
        if (isset($changes['current_location_type']) || isset($changes['current_location_id'])) {
            $fromType = $original['current_location_type'] ?? 'unknown';
            $fromId   = $original['current_location_id'] ?? 0;
            $toType   = $changes['current_location_type'] ?? $serial->current_location_type;
            $toId     = $changes['current_location_id'] ?? $serial->current_location_id;

            Log::info("Serial [{$serial->serial_no}] location changed: {$fromType}#{$fromId} → {$toType}#{$toId}", [
                'serial_id' => $serial->id,
                'serial_no' => $serial->serial_no,
                'user_id'   => auth()->id(),
            ]);
        }

        $this->logAudit($serial, $event, $oldValues, $newValues);
    }

    /**
     * Handle the deleted event.
     */
    public function deleted(InventorySerial $serial): void
    {
        $this->logAudit($serial, AuditTrail::EVENT_DELETED, [
            'serial_no'      => $serial->serial_no,
            'current_status' => $serial->current_status,
            'model_id'       => $serial->model_id,
        ], null);

        Log::info("Serial [{$serial->serial_no}] deleted (soft)", [
            'serial_id' => $serial->id,
            'user_id'   => auth()->id(),
        ]);
    }

    /**
     * Handle the restored event.
     */
    public function restored(InventorySerial $serial): void
    {
        $this->logAudit($serial, AuditTrail::EVENT_RESTORED, null, [
            'serial_no'      => $serial->serial_no,
            'current_status' => $serial->current_status,
        ]);

        Log::info("Serial [{$serial->serial_no}] restored", [
            'serial_id' => $serial->id,
            'user_id'   => auth()->id(),
        ]);
    }

    /**
     * Write audit trail entry.
     */
    protected function logAudit(InventorySerial $serial, string $event, ?array $oldValues, ?array $newValues): void
    {
        try {
            AuditTrail::create([
                'auditable_type' => InventorySerial::class,
                'auditable_id'   => $serial->id,
                'event'          => $event,
                'old_values'     => $oldValues,
                'new_values'     => $newValues,
                'url'            => request()->fullUrl(),
                'ip_address'     => request()->ip(),
                'user_agent'     => request()->userAgent(),
                'user_id'        => auth()->id(),
                'created_at'     => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log audit trail for InventorySerial', [
                'serial_id' => $serial->id,
                'event'     => $event,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
