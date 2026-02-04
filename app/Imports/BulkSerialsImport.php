<?php

namespace App\Imports;

use App\Models\InventorySerial;
use App\Models\TerminalModel;
use App\Models\Depot;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class BulkSerialsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows, WithBatchInserts
{
    protected array $results = [
        'success' => 0,
        'updated' => 0,
        'failed' => 0,
        'errors' => [],
    ];

    /**
     * Process the imported collection
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // Account for header row

            try {
                // Normalize the row data
                $data = $this->normalizeRow($row->toArray());

                // Skip empty rows
                if (empty($data['serial_no'])) {
                    continue;
                }

                // Validate the row
                $validator = $this->validateRow($data, $rowNumber);

                if ($validator->fails()) {
                    $this->results['failed']++;
                    $this->results['errors'][] = [
                        'row' => $rowNumber,
                        'serial_no' => $data['serial_no'] ?? 'Unknown',
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }

                // Check if serial already exists
                $existingSerial = InventorySerial::where('serial_no', $data['serial_no'])->first();

                if ($existingSerial) {
                    // Update existing serial
                    $this->updateSerial($existingSerial, $data);
                    $this->results['updated']++;
                } else {
                    // Create new serial
                    $this->createSerial($data);
                    $this->results['success']++;
                }

            } catch (\Exception $e) {
                $this->results['failed']++;
                $this->results['errors'][] = [
                    'row' => $rowNumber,
                    'serial_no' => $data['serial_no'] ?? 'Unknown',
                    'errors' => [$e->getMessage()],
                ];
            }
        }
    }

    /**
     * Normalize row data
     */
    protected function normalizeRow(array $row): array
    {
        return [
            'serial_no' => trim($row['serial_no'] ?? $row['serial_number'] ?? ''),
            'model_code' => trim($row['model_code'] ?? ''),
            'model_name' => trim($row['model_name'] ?? ''),
            'hardware_type' => trim($row['hardware_type'] ?? ''),
            'device_type' => trim($row['device_type'] ?? ''),
            'telco' => trim($row['telco'] ?? ''),
            'sim_quota' => trim($row['sim_quota'] ?? ''),
            'current_status' => strtolower(trim($row['current_status'] ?? 'in_stock')),
            'current_location_type' => strtolower(trim($row['current_location_type'] ?? 'depot')),
            'current_location_code' => trim($row['current_location_code'] ?? ''),
            'current_location_name' => trim($row['current_location_name'] ?? ''),
            'remarks' => trim($row['remarks'] ?? ''),
        ];
    }

    /**
     * Validate row data
     */
    protected function validateRow(array $data, int $rowNumber)
    {
        return Validator::make($data, [
            'serial_no' => 'required|string|max:100',
            'model_code' => 'nullable|string',
            'current_status' => 'required|in:in_stock,issued_to_tech,installed,under_service,returned_to_vendor,wasted,reserved',
            'current_location_type' => 'required|in:depot,technician,site,vendor',
        ], [
            'serial_no.required' => "Row {$rowNumber}: Serial number is required",
            'current_status.in' => "Row {$rowNumber}: Invalid status value",
            'current_location_type.in' => "Row {$rowNumber}: Invalid location type",
        ]);
    }

    /**
     * Create new serial
     */
    protected function createSerial(array $data): void
    {
        // Find model by code or name
        $model = null;
        if (!empty($data['model_code'])) {
            $model = TerminalModel::where('model_code', $data['model_code'])->first();
        }
        if (!$model && !empty($data['model_name'])) {
            $model = TerminalModel::where('model_name', 'like', '%' . $data['model_name'] . '%')->first();
        }

        if (!$model) {
            throw new \Exception('Terminal model not found');
        }

        // Find location
        $locationId = $this->findLocationId($data['current_location_type'], $data['current_location_code'], $data['current_location_name']);

        if (!$locationId) {
            throw new \Exception('Location not found');
        }

        InventorySerial::create([
            'serial_no' => $data['serial_no'],
            'model_id' => $model->id,
            'hardware_type' => $data['hardware_type'] ?: null,
            'device_type' => $data['device_type'] ?: null,
            'telco' => $data['telco'] ?: null,
            'sim_quota' => $data['sim_quota'] ?: null,
            'current_status' => $data['current_status'],
            'current_location_type' => $data['current_location_type'],
            'current_location_id' => $locationId,
            'remarks' => $data['remarks'] ?: null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    /**
     * Update existing serial
     */
    protected function updateSerial(InventorySerial $serial, array $data): void
    {
        // Find location if provided
        $locationId = null;
        if (!empty($data['current_location_code']) || !empty($data['current_location_name'])) {
            $locationId = $this->findLocationId($data['current_location_type'], $data['current_location_code'], $data['current_location_name']);
        }

        $updateData = [
            'current_status' => $data['current_status'],
            'updated_by' => Auth::id(),
        ];

        if (!empty($data['hardware_type'])) {
            $updateData['hardware_type'] = $data['hardware_type'];
        }
        if (!empty($data['device_type'])) {
            $updateData['device_type'] = $data['device_type'];
        }
        if (!empty($data['telco'])) {
            $updateData['telco'] = $data['telco'];
        }
        if (!empty($data['sim_quota'])) {
            $updateData['sim_quota'] = $data['sim_quota'];
        }

        if ($locationId) {
            $updateData['current_location_type'] = $data['current_location_type'];
            $updateData['current_location_id'] = $locationId;
        }

        $serial->update($updateData);
    }

    /**
     * Find location ID by code or name
     */
    protected function findLocationId(string $type, ?string $code, ?string $name): ?int
    {
        if ($type === 'depot') {
            if ($code) {
                $depot = Depot::where('depot_code', $code)->first();
                if ($depot) return $depot->id;
            }
            if ($name) {
                $depot = Depot::where('depot_name', 'like', '%' . $name . '%')->first();
                if ($depot) return $depot->id;
            }
            // Return first active depot as fallback
            return Depot::where('status', 'active')->first()?->id;
        }

        if ($type === 'technician') {
            if ($code) {
                $user = User::where('employee_id', $code)->first();
                if ($user) return $user->id;
            }
            if ($name) {
                $user = User::where('name', 'like', '%' . $name . '%')->first();
                if ($user) return $user->id;
            }
        }

        return null;
    }

    /**
     * Batch size for inserts
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * Get import results
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
