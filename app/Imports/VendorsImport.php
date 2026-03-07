<?php

namespace App\Imports;

use App\Models\Vendor;
use App\Models\VendorBranch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;

class VendorsImport implements ToModel, WithHeadingRow, WithBatchInserts, SkipsOnFailure
{
    use SkipsFailures;

    protected int $successCount = 0;
    protected int $failedCount = 0;
    protected array $errors = [];

    /**
     * Transform each row into a model
     */
    public function model(array $row)
    {
        // Validate the row
        $validator = Validator::make($row, $this->rules($row));

        if ($validator->fails()) {
            $this->failedCount++;
            $this->errors[] = [
                'row' => $row,
                'errors' => $validator->errors()->all(),
            ];
            return null;
        }

        try {
            // Check if vendor already exists
            $existingVendor = Vendor::where('vendor_code', $row['vendor_code'])->first();

            if ($existingVendor) {
                // Update existing vendor
                $existingVendor->update([
                    'vendor_name' => $row['vendor_name'],
                    'vendor_type' => strtolower($row['vendor_type']),
                    'company_name' => $row['company_name'] ?? null,
                    'registration_no' => $row['registration_no'] ?? null,
                    'tax_id' => $row['tax_id'] ?? null,
                    'pic_name' => $row['pic_name'] ?? null,
                    'pic_email' => $row['pic_email'] ?? null,
                    'pic_phone' => $row['pic_phone'] ?? null,
                    'bank_name' => $row['bank_name'] ?? null,
                    'bank_account_no' => $row['bank_account_no'] ?? null,
                    'bank_account_name' => $row['bank_account_name'] ?? null,
                    'payment_terms' => $row['payment_terms_days'] ?? 30,
                    'status' => strtolower($row['status'] ?? 'active'),
                    'notes' => $row['notes'] ?? null,
                    'updated_by' => Auth::id(),
                ]);

                // Create/update default branch from address columns if provided
                $this->syncImportBranch($existingVendor, $row);

                $this->successCount++;
                return null; // Don't create a new model
            }

            // Create new vendor
            $vendor = Vendor::create([
                'vendor_code' => $row['vendor_code'],
                'vendor_name' => $row['vendor_name'],
                'vendor_type' => strtolower($row['vendor_type']),
                'company_name' => $row['company_name'] ?? null,
                'registration_no' => $row['registration_no'] ?? null,
                'tax_id' => $row['tax_id'] ?? null,
                'pic_name' => $row['pic_name'] ?? null,
                'pic_email' => $row['pic_email'] ?? null,
                'pic_phone' => $row['pic_phone'] ?? null,
                'bank_name' => $row['bank_name'] ?? null,
                'bank_account_no' => $row['bank_account_no'] ?? null,
                'bank_account_name' => $row['bank_account_name'] ?? null,
                'payment_terms' => $row['payment_terms_days'] ?? 30,
                'status' => strtolower($row['status'] ?? 'active'),
                'notes' => $row['notes'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Create default branch from address columns
            $this->syncImportBranch($vendor, $row);

            $this->successCount++;
            return null;

        } catch (\Exception $e) {
            $this->failedCount++;
            $this->errors[] = [
                'row' => $row,
                'errors' => [$e->getMessage()],
            ];
            return null;
        }
    }

    /**
     * Create or update a branch from import row address fields
     * Supports both old-style (address/city/state) and new branch_name column
     */
    protected function syncImportBranch(Vendor $vendor, array $row): void
    {
        $branchName = $row['branch_name'] ?? 'Main Branch';
        $address = $row['address'] ?? null;
        $city = $row['city'] ?? null;
        $state = $row['state'] ?? null;
        $postcode = $row['postcode'] ?? null;
        $country = $row['country'] ?? 'Malaysia';

        // Only create branch if at least branch_name or address data exists
        if (!$address && !$city && !$state && $branchName === 'Main Branch') {
            // No address data and no explicit branch name - only create if vendor has no branches
            if ($vendor->branches()->count() === 0) {
                VendorBranch::create([
                    'vendor_id' => $vendor->id,
                    'branch_name' => $branchName,
                    'country' => $country,
                    'is_primary' => true,
                    'status' => 'active',
                ]);
            }
            return;
        }

        // Check if a branch with this name already exists for this vendor
        $existingBranch = $vendor->branches()->where('branch_name', $branchName)->first();

        if ($existingBranch) {
            $existingBranch->update([
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'postcode' => $postcode,
                'country' => $country,
            ]);
        } else {
            $isPrimary = $vendor->branches()->count() === 0;
            VendorBranch::create([
                'vendor_id' => $vendor->id,
                'branch_name' => $branchName,
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'postcode' => $postcode,
                'country' => $country,
                'is_primary' => $isPrimary,
                'status' => 'active',
            ]);
        }
    }

    /**
     * Validation rules for import
     */
    protected function rules(array $row): array
    {
        return [
            'vendor_code' => [
                'required',
                'string',
                'max:50',
            ],
            'vendor_name' => 'required|string|max:255',
            'vendor_type' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $validTypes = ['supplier', 'subcon', 'courier', 'other'];
                    if (!in_array(strtolower($value), $validTypes)) {
                        $fail('The vendor type must be one of: ' . implode(', ', $validTypes));
                    }
                }
            ],
            'pic_email' => 'nullable|email',
            'payment_terms_days' => 'nullable|integer|min:0|max:365',
            'status' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value && !in_array(strtolower($value), ['active', 'inactive'])) {
                        $fail('The status must be either active or inactive');
                    }
                }
            ],
        ];
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
        return [
            'success' => $this->successCount,
            'failed' => $this->failedCount,
            'errors' => $this->errors,
        ];
    }

    /**
     * Handle failures
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failedCount++;
            $this->errors[] = [
                'row' => $failure->row(),
                'errors' => $failure->errors(),
            ];
        }
    }
}
