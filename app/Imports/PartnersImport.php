<?php

namespace App\Imports;

use App\Models\Partner;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class PartnersImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected array $results = [
        'success' => 0,
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
                // Convert to array and lowercase keys
                $data = $this->normalizeRow($row->toArray());

                // Skip empty rows
                if (empty($data['partner_name'])) {
                    continue;
                }

                // Validate the row
                $validator = $this->validateRow($data, $rowNumber);

                if ($validator->fails()) {
                    $this->results['failed']++;
                    $this->results['errors'][] = [
                        'row' => $rowNumber,
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }

                // Check if partner already exists
                $existingPartner = null;
                if (!empty($data['partner_code'])) {
                    $existingPartner = Partner::withTrashed()
                        ->where('partner_code', $data['partner_code'])
                        ->first();
                }

                if ($existingPartner) {
                    // Update existing partner
                    $this->updatePartner($existingPartner, $data);
                } else {
                    // Create new partner
                    $this->createPartner($data);
                }

                $this->results['success']++;

            } catch (\Exception $e) {
                $this->results['failed']++;
                $this->results['errors'][] = [
                    'row' => $rowNumber,
                    'errors' => [$e->getMessage()],
                ];
            }
        }
    }

    /**
     * Normalize row data
     */
    private function normalizeRow(array $row): array
    {
        $normalized = [];

        // Map heading variations to standard keys
        $keyMapping = [
            'partner_code' => ['partner_code', 'code', 'partner code'],
            'partner_name' => ['partner_name', 'name', 'partner name'],
            'pic_name' => ['pic_name', 'pic name', 'contact name', 'person in charge'],
            'pic_email' => ['pic_email', 'pic email', 'contact email', 'email'],
            'pic_phone' => ['pic_phone', 'pic phone', 'contact phone', 'phone'],
            'address' => ['address', 'street address'],
            'city' => ['city'],
            'state' => ['state', 'province'],
            'postcode' => ['postcode', 'postal code', 'zip code'],
            'country' => ['country'],
            'job_intake_method' => ['job_intake_method', 'job intake method', 'intake method'],
            'status' => ['status'],
            'notes' => ['notes', 'remarks', 'comments'],
        ];

        foreach ($keyMapping as $standardKey => $variations) {
            foreach ($variations as $variation) {
                $key = strtolower(str_replace(' ', '_', $variation));
                if (isset($row[$key]) && !empty($row[$key])) {
                    $normalized[$standardKey] = trim($row[$key]);
                    break;
                }
            }
        }

        return $normalized;
    }

    /**
     * Validate a single row
     */
    private function validateRow(array $data, int $rowNumber): \Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'partner_name' => 'required|string|max:255',
            'partner_code' => 'nullable|string|max:50',
            'pic_name' => 'nullable|string|max:100',
            'pic_email' => 'nullable|email|max:255',
            'pic_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postcode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'job_intake_method' => 'nullable|string|in:manual,import,api,Manual,Import,API',
            'status' => 'nullable|string|in:active,inactive,Active,Inactive',
            'notes' => 'nullable|string|max:1000',
        ], [
            'partner_name.required' => "Row {$rowNumber}: Partner name is required.",
            'partner_name.max' => "Row {$rowNumber}: Partner name cannot exceed 255 characters.",
            'pic_email.email' => "Row {$rowNumber}: Invalid PIC email format.",
            'job_intake_method.in' => "Row {$rowNumber}: Invalid job intake method. Use: manual, import, or api.",
            'status.in' => "Row {$rowNumber}: Invalid status. Use: active or inactive.",
        ]);
    }

    /**
     * Create a new partner from import data
     */
    private function createPartner(array $data): Partner
    {
        return Partner::create([
            'partner_name' => $data['partner_name'],
            'partner_code' => $data['partner_code'] ?? null, // Will be auto-generated if null
            'pic_name' => $data['pic_name'] ?? null,
            'pic_email' => $data['pic_email'] ?? null,
            'pic_phone' => $data['pic_phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'postcode' => $data['postcode'] ?? null,
            'country' => $data['country'] ?? 'Malaysia',
            'job_intake_method' => strtolower($data['job_intake_method'] ?? 'manual'),
            'status' => strtolower($data['status'] ?? 'active'),
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    /**
     * Update an existing partner from import data
     */
    private function updatePartner(Partner $partner, array $data): Partner
    {
        // Restore if trashed
        if ($partner->trashed()) {
            $partner->restore();
        }

        $partner->update([
            'partner_name' => $data['partner_name'],
            'pic_name' => $data['pic_name'] ?? $partner->pic_name,
            'pic_email' => $data['pic_email'] ?? $partner->pic_email,
            'pic_phone' => $data['pic_phone'] ?? $partner->pic_phone,
            'address' => $data['address'] ?? $partner->address,
            'city' => $data['city'] ?? $partner->city,
            'state' => $data['state'] ?? $partner->state,
            'postcode' => $data['postcode'] ?? $partner->postcode,
            'country' => $data['country'] ?? $partner->country,
            'job_intake_method' => !empty($data['job_intake_method']) 
                ? strtolower($data['job_intake_method']) 
                : $partner->job_intake_method,
            'status' => !empty($data['status']) 
                ? strtolower($data['status']) 
                : $partner->status,
            'notes' => $data['notes'] ?? $partner->notes,
            'updated_by' => Auth::id(),
        ]);

        return $partner->fresh();
    }

    /**
     * Get import results
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
