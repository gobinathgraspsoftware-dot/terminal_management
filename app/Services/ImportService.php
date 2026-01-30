<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;

class ImportService
{
    protected int $successCount = 0;
    protected int $failedCount = 0;
    protected array $errors = [];

    /**
     * Validate import data
     */
    public function validateRow(array $row, array $rules): array
    {
        $validator = Validator::make($row, $rules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->all()
            ];
        }

        return ['valid' => true];
    }

    /**
     * Process import with validation
     */
    public function processImport(
        Collection $rows,
        array $rules,
        callable $processCallback
    ): array {
        $this->successCount = 0;
        $this->failedCount = 0;
        $this->errors = [];

        foreach ($rows as $index => $row) {
            try {
                // Validate row
                $validation = $this->validateRow($row, $rules);

                if (!$validation['valid']) {
                    $this->failedCount++;
                    $this->errors[] = [
                        'row' => $index + 1,
                        'data' => $row,
                        'errors' => $validation['errors']
                    ];
                    continue;
                }

                // Process row
                $result = call_user_func($processCallback, $row, $index);

                if ($result === true || $result === null) {
                    $this->successCount++;
                } else {
                    $this->failedCount++;
                    $this->errors[] = [
                        'row' => $index + 1,
                        'data' => $row,
                        'errors' => is_array($result) ? $result : ['Processing failed']
                    ];
                }

            } catch (\Exception $e) {
                $this->failedCount++;
                $this->errors[] = [
                    'row' => $index + 1,
                    'data' => $row,
                    'errors' => [$e->getMessage()]
                ];
            }
        }

        return $this->getResults();
    }

    /**
     * Get import results
     */
    public function getResults(): array
    {
        return [
            'success' => $this->successCount,
            'failed' => $this->failedCount,
            'total' => $this->successCount + $this->failedCount,
            'errors' => $this->errors
        ];
    }

    /**
     * Format errors for display
     */
    public function formatErrors(): array
    {
        return array_map(function ($error) {
            return sprintf(
                'Row %d: %s',
                $error['row'],
                implode(', ', $error['errors'])
            );
        }, $this->errors);
    }

    /**
     * Clean import data
     */
    public function cleanData(array $data): array
    {
        return array_map(function ($value) {
            if (is_string($value)) {
                return trim($value);
            }
            return $value;
        }, $data);
    }

    /**
     * Normalize column names
     */
    public function normalizeColumns(array $row): array
    {
        $normalized = [];
        
        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(str_replace(' ', '_', $key));
            $normalized[$normalizedKey] = $value;
        }
        
        return $normalized;
    }

    /**
     * Parse date from import
     */
    public function parseDate($value, string $format = 'Y-m-d'): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                // Excel date serial number
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                return $date->format($format);
            }

            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse boolean from import
     */
    public function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim($value));

        return in_array($value, ['yes', 'y', 'true', '1', 'active'], true);
    }

    /**
     * Get unique values for a column
     */
    public function getUniqueValues(Collection $rows, string $column): array
    {
        return $rows->pluck($column)->unique()->filter()->values()->toArray();
    }

    /**
     * Check for duplicate rows
     */
    public function checkDuplicates(Collection $rows, array $uniqueColumns): array
    {
        $seen = [];
        $duplicates = [];

        foreach ($rows as $index => $row) {
            $key = implode('|', array_map(fn($col) => $row[$col] ?? '', $uniqueColumns));

            if (isset($seen[$key])) {
                $duplicates[] = [
                    'row' => $index + 1,
                    'duplicate_of' => $seen[$key],
                    'data' => $row
                ];
            } else {
                $seen[$key] = $index + 1;
            }
        }

        return $duplicates;
    }
}
