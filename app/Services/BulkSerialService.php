<?php

namespace App\Services;

use App\Models\InventorySerial;
use App\Models\StockLedger;
use App\Models\Depot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

class BulkSerialService
{
    /**
     * Validate serials for bulk operation
     *
     * @param array $serialNumbers
     * @param string $operation (update|transfer)
     * @return array
     */
    public function validateSerials(array $serialNumbers, string $operation = 'update'): array
    {
        $results = [
            'valid' => [],
            'invalid' => [],
            'errors' => []
        ];

        foreach ($serialNumbers as $serialNo) {
            $serial = InventorySerial::where('serial_no', $serialNo)->first();

            if (!$serial) {
                $results['invalid'][] = $serialNo;
                $results['errors'][$serialNo] = 'Serial number not found';
                continue;
            }

            // Check if serial can be operated on based on operation type
            $canOperate = $this->canOperateOnSerial($serial, $operation);
            
            if (!$canOperate['can']) {
                $results['invalid'][] = $serialNo;
                $results['errors'][$serialNo] = $canOperate['reason'];
                continue;
            }

            $results['valid'][] = [
                'serial_no' => $serial->serial_no,
                'id' => $serial->id,
                'model' => $serial->terminalModel->model_name ?? 'Unknown',
                'current_status' => $serial->current_status,
                'current_location' => $serial->location_name
            ];
        }

        return $results;
    }

    /**
     * Check if serial can be operated on
     *
     * @param InventorySerial $serial
     * @param string $operation
     * @return array
     */
    protected function canOperateOnSerial(InventorySerial $serial, string $operation): array
    {
        // Cannot operate on installed or under service items
        if (in_array($serial->current_status, [
            InventorySerial::STATUS_INSTALLED,
            InventorySerial::STATUS_UNDER_SERVICE
        ])) {
            return [
                'can' => false,
                'reason' => "Cannot operate on {$serial->current_status} items"
            ];
        }

        return ['can' => true, 'reason' => ''];
    }

    /**
     * Bulk update serial statuses
     *
     * @param array $serialIds
     * @param string $newStatus
     * @param string|null $remarks
     * @return array
     */
    public function bulkUpdateStatus(array $serialIds, string $newStatus, ?string $remarks = null): array
    {
        DB::beginTransaction();
        try {
            $updated = 0;
            $failed = 0;
            $errors = [];

            foreach ($serialIds as $serialId) {
                try {
                    $serial = InventorySerial::findOrFail($serialId);
                    $oldStatus = $serial->current_status;

                    // Update serial status
                    $serial->update([
                        'current_status' => $newStatus,
                        'updated_by' => Auth::id()
                    ]);

                    // Log in stock ledger
                    StockLedger::create([
                        'transaction_date' => now(),
                        'transaction_type' => 'status_update',
                        'serial_id' => $serial->id,
                        'serial_no' => $serial->serial_no,
                        'model_id' => $serial->model_id,
                        'quantity' => 0,
                        'from_location_type' => $serial->current_location_type,
                        'from_location_id' => $serial->current_location_id,
                        'to_location_type' => $serial->current_location_type,
                        'to_location_id' => $serial->current_location_id,
                        'remarks' => $remarks ?? "Bulk status update: {$oldStatus} → {$newStatus}",
                        'created_by' => Auth::id()
                    ]);

                    $updated++;

                } catch (Exception $e) {
                    $failed++;
                    $errors[] = "Serial ID {$serialId}: " . $e->getMessage();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'updated' => $updated,
                'failed' => $failed,
                'errors' => $errors
            ];

        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Bulk update failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Bulk transfer serials between locations
     *
     * @param array $serialIds
     * @param string $toLocationType
     * @param int $toLocationId
     * @param string|null $remarks
     * @return array
     */
    public function bulkTransfer(
        array $serialIds,
        string $toLocationType,
        int $toLocationId,
        ?string $remarks = null
    ): array {
        DB::beginTransaction();
        try {
            $transferred = 0;
            $failed = 0;
            $errors = [];

            // Validate destination location exists
            if (!$this->validateLocation($toLocationType, $toLocationId)) {
                throw new Exception('Invalid destination location');
            }

            foreach ($serialIds as $serialId) {
                try {
                    $serial = InventorySerial::findOrFail($serialId);
                    
                    $fromLocationType = $serial->current_location_type;
                    $fromLocationId = $serial->current_location_id;

                    // Determine new status based on destination type
                    $newStatus = $this->getStatusForLocation($toLocationType);

                    // Update serial location and status
                    $serial->update([
                        'current_location_type' => $toLocationType,
                        'current_location_id' => $toLocationId,
                        'current_status' => $newStatus,
                        'updated_by' => Auth::id()
                    ]);

                    // Log in stock ledger
                    StockLedger::create([
                        'transaction_date' => now(),
                        'transaction_type' => 'transfer',
                        'serial_id' => $serial->id,
                        'serial_no' => $serial->serial_no,
                        'model_id' => $serial->model_id,
                        'quantity' => -1,
                        'from_location_type' => $fromLocationType,
                        'from_location_id' => $fromLocationId,
                        'to_location_type' => $toLocationType,
                        'to_location_id' => $toLocationId,
                        'remarks' => $remarks ?? 'Bulk transfer',
                        'created_by' => Auth::id()
                    ]);

                    $transferred++;

                } catch (Exception $e) {
                    $failed++;
                    $errors[] = "Serial ID {$serialId}: " . $e->getMessage();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'transferred' => $transferred,
                'failed' => $failed,
                'errors' => $errors
            ];

        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Bulk transfer failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get status based on location type
     *
     * @param string $locationType
     * @return string
     */
    protected function getStatusForLocation(string $locationType): string
    {
        return match ($locationType) {
            InventorySerial::LOCATION_TYPE_DEPOT => InventorySerial::STATUS_IN_STOCK,
            InventorySerial::LOCATION_TYPE_TECHNICIAN => InventorySerial::STATUS_ISSUED_TO_TECH,
            InventorySerial::LOCATION_TYPE_SITE => InventorySerial::STATUS_INSTALLED,
            InventorySerial::LOCATION_TYPE_VENDOR => InventorySerial::STATUS_RETURNED_TO_VENDOR,
            default => InventorySerial::STATUS_IN_STOCK
        };
    }

    /**
     * Validate location exists
     *
     * @param string $locationType
     * @param int $locationId
     * @return bool
     */
    protected function validateLocation(string $locationType, int $locationId): bool
    {
        return match ($locationType) {
            InventorySerial::LOCATION_TYPE_DEPOT => Depot::find($locationId) !== null,
            InventorySerial::LOCATION_TYPE_TECHNICIAN => User::find($locationId) !== null,
            default => false
        };
    }

    /**
     * Import serials from Excel data
     *
     * @param array $rows
     * @return array
     */
    public function importSerials(array $rows): array
    {
        DB::beginTransaction();
        try {
            $imported = 0;
            $updated = 0;
            $failed = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                try {
                    // Validate required fields
                    if (empty($row['serial_no']) || empty($row['model_id'])) {
                        $failed++;
                        $errors[] = "Row " . ($index + 2) . ": Missing required fields";
                        continue;
                    }

                    // Check if serial exists
                    $serial = InventorySerial::where('serial_no', $row['serial_no'])->first();

                    if ($serial) {
                        // Update existing serial
                        $serial->update([
                            'current_status' => $row['current_status'] ?? $serial->current_status,
                            'current_location_type' => $row['current_location_type'] ?? $serial->current_location_type,
                            'current_location_id' => $row['current_location_id'] ?? $serial->current_location_id,
                            'updated_by' => Auth::id()
                        ]);
                        $updated++;
                    } else {
                        // Create new serial
                        InventorySerial::create([
                            'serial_no' => $row['serial_no'],
                            'model_id' => $row['model_id'],
                            'current_status' => $row['current_status'] ?? InventorySerial::STATUS_IN_STOCK,
                            'current_location_type' => $row['current_location_type'] ?? InventorySerial::LOCATION_TYPE_DEPOT,
                            'current_location_id' => $row['current_location_id'] ?? 1,
                            'hardware_type' => $row['hardware_type'] ?? null,
                            'device_type' => $row['device_type'] ?? null,
                            'telco' => $row['telco'] ?? null,
                            'sim_quota' => $row['sim_quota'] ?? null,
                            'created_by' => Auth::id()
                        ]);
                        $imported++;
                    }

                } catch (Exception $e) {
                    $failed++;
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'failed' => $failed,
                'errors' => $errors
            ];

        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get preview of changes before commit
     *
     * @param array $serialIds
     * @param array $changes
     * @return array
     */
    public function getPreview(array $serialIds, array $changes): array
    {
        $serials = InventorySerial::with('terminalModel')
            ->whereIn('id', $serialIds)
            ->get();

        $preview = [];

        foreach ($serials as $serial) {
            $preview[] = [
                'serial_no' => $serial->serial_no,
                'model' => $serial->terminalModel->model_name ?? 'Unknown',
                'current_status' => $serial->current_status,
                'current_location' => $serial->location_name,
                'new_status' => $changes['status'] ?? $serial->current_status,
                'new_location' => $this->getLocationName($changes['location_type'] ?? null, $changes['location_id'] ?? null) ?? $serial->location_name
            ];
        }

        return $preview;
    }

    /**
     * Get location name
     *
     * @param string|null $type
     * @param int|null $id
     * @return string|null
     */
    protected function getLocationName(?string $type, ?int $id): ?string
    {
        if (!$type || !$id) {
            return null;
        }

        return match ($type) {
            InventorySerial::LOCATION_TYPE_DEPOT => Depot::find($id)?->depot_name,
            InventorySerial::LOCATION_TYPE_TECHNICIAN => User::find($id)?->name,
            default => null
        };
    }
}
