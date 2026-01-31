<?php

namespace App\Imports;

use App\Models\TerminalModel;
use App\Models\TerminalCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class TerminalModelsImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected $importedCount = 0;
    protected $updatedCount = 0;
    protected $errors = [];

    /**
     * Process the imported collection
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                // Find category by name
                $category = TerminalCategory::where('category_name', $row['category'])
                    ->orWhere('category_code', $row['category'])
                    ->first();

                if (!$category) {
                    $this->errors[] = "Row " . ($index + 2) . ": Category '{$row['category']}' not found";
                    continue;
                }

                // Prepare data
                $data = [
                    'model_name' => $row['model_name'],
                    'category_id' => $category->id,
                    'brand' => $row['brand'] ?? null,
                    'description' => $row['description'] ?? null,
                    'is_serial_tracked' => $this->parseBoolean($row['serial_tracked'] ?? 'yes'),
                    'warranty_months' => $row['warranty_months'] ?? 12,
                    'sort_order' => $row['sort_order'] ?? 0,
                    'status' => strtolower($row['status'] ?? 'active'),
                ];

                // Check if model code exists
                if (!empty($row['model_code'])) {
                    $existingModel = TerminalModel::where('model_code', $row['model_code'])->first();

                    if ($existingModel) {
                        // Update existing
                        $existingModel->update($data);
                        $this->updatedCount++;
                    } else {
                        // Create new with code
                        $data['model_code'] = $row['model_code'];
                        TerminalModel::create($data);
                        $this->importedCount++;
                    }
                } else {
                    // Create new without code (will auto-generate)
                    TerminalModel::create($data);
                    $this->importedCount++;
                }

            } catch (\Exception $e) {
                $this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'model_name' => 'required|string|max:100',
            'category' => 'required|string',
            'brand' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'serial_tracked' => 'nullable|string',
            'warranty_months' => 'nullable|integer|min:0|max:120',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    /**
     * Get imported count
     */
    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    /**
     * Get updated count
     */
    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    /**
     * Get errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Parse boolean value
     */
    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim($value));
        return in_array($value, ['yes', 'true', '1', 'y']);
    }
}
