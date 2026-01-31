<?php

namespace App\Services;

use App\Models\TerminalCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Terminal Category Service
 *
 * Business logic for terminal category operations
 */
class TerminalCategoryService
{
    /**
     * Create a new terminal category.
     */
    public function create(array $data): TerminalCategory
    {
        return DB::transaction(function () use ($data) {
            // Generate category code if not provided
            if (empty($data['category_code'])) {
                $data['category_code'] = $this->generateCategoryCode($data['category_type']);
            }

            // Set sort order to last if not provided
            if (!isset($data['sort_order'])) {
                $data['sort_order'] = TerminalCategory::max('sort_order') + 1;
            }

            $category = TerminalCategory::create($data);

            Log::info('Terminal category created', [
                'category_id' => $category->id,
                'category_code' => $category->category_code,
                'created_by' => auth()->id(),
            ]);

            return $category;
        });
    }

    /**
     * Update an existing terminal category.
     */
    public function update(TerminalCategory $category, array $data): TerminalCategory
    {
        return DB::transaction(function () use ($category, $data) {
            $category->update($data);

            Log::info('Terminal category updated', [
                'category_id' => $category->id,
                'category_code' => $category->category_code,
                'updated_by' => auth()->id(),
            ]);

            return $category->fresh();
        });
    }

    /**
     * Delete a terminal category.
     */
    public function delete(TerminalCategory $category): bool
    {
        return DB::transaction(function () use ($category) {
            // Check if category has associated models
            if ($category->terminalModels()->count() > 0) {
                throw new \Exception('Cannot delete category with associated terminal models');
            }

            $categoryCode = $category->category_code;
            $deleted = $category->delete();

            Log::info('Terminal category deleted', [
                'category_code' => $categoryCode,
                'deleted_by' => auth()->id(),
            ]);

            return $deleted;
        });
    }

    /**
     * Update sort order for multiple categories.
     */
    public function updateSortOrder(array $orders): void
    {
        DB::transaction(function () use ($orders) {
            foreach ($orders as $order) {
                TerminalCategory::where('id', $order['id'])
                    ->update(['sort_order' => $order['sort_order']]);
            }

            Log::info('Terminal categories sort order updated', [
                'count' => count($orders),
                'updated_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Generate a unique category code.
     */
    protected function generateCategoryCode(string $type): string
    {
        // Get prefix based on type
        $prefix = match($type) {
            'terminal' => 'TRM',
            'router' => 'RTR',
            'sim' => 'SIM',
            'accessory' => 'ACC',
            'other' => 'OTH',
            default => 'CAT',
        };

        // Get last code for this type
        $lastCategory = TerminalCategory::where('category_code', 'LIKE', $prefix . '%')
            ->orderBy('category_code', 'desc')
            ->first();

        if ($lastCategory) {
            $lastNumber = (int) substr($lastCategory->category_code, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get statistics for terminal categories.
     */
    public function getStatistics(): array
    {
        return [
            'total_categories' => TerminalCategory::count(),
            'active_categories' => TerminalCategory::where('status', 'active')->count(),
            'inactive_categories' => TerminalCategory::where('status', 'inactive')->count(),
            'serial_tracked' => TerminalCategory::where('is_serial_tracked', true)->count(),
            'by_type' => TerminalCategory::selectRaw('category_type, COUNT(*) as count')
                ->groupBy('category_type')
                ->pluck('count', 'category_type')
                ->toArray(),
            'total_models' => DB::table('terminal_models')->count(),
        ];
    }

    /**
     * Get categories grouped by type.
     */
    public function getCategoriesByType(bool $activeOnly = false): array
    {
        $query = TerminalCategory::withCount('terminalModels')
            ->orderBy('sort_order')
            ->orderBy('category_name');

        if ($activeOnly) {
            $query->where('status', 'active');
        }

        return $query->get()->groupBy('category_type')->toArray();
    }
}
