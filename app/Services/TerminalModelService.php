<?php

namespace App\Services;

use App\Models\TerminalModel;
use App\Models\TerminalCategory;
use App\Models\InventorySerial;
use App\Models\StockBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class TerminalModelService
{
    /**
     * Get statistics for terminal models
     */
    public function getStatistics(): array
    {
        return [
            'total' => TerminalModel::count(),
            'active' => TerminalModel::where('status', TerminalModel::STATUS_ACTIVE)->count(),
            'inactive' => TerminalModel::where('status', TerminalModel::STATUS_INACTIVE)->count(),
            'with_warranty' => TerminalModel::where('warranty_months', '>', 0)->count(),
        ];
    }

    /**
     * Generate next model code
     */
    public function generateModelCode(): string
    {
        $lastModel = TerminalModel::withTrashed()
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastModel) {
            return 'MDL000001';
        }

        $lastNumber = (int) substr($lastModel->model_code, 3);
        $nextNumber = $lastNumber + 1;

        return 'MDL' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new terminal model
     */
    public function create(array $data): TerminalModel
    {
        DB::beginTransaction();
        try {
            // Generate model code if not provided
            if (empty($data['model_code'])) {
                $data['model_code'] = $this->generateModelCode();
            }

            // Handle image upload
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $data['image_path'] = $this->handleImageUpload($data['image']);
                unset($data['image']);
            }

            // Handle specifications JSON
            if (isset($data['specifications']) && is_array($data['specifications'])) {
                // Remove empty specification entries
                $data['specifications'] = array_filter($data['specifications'], function($spec) {
                    return !empty($spec['key']) || !empty($spec['value']);
                });
            }

            // Handle default accessories JSON
            if (isset($data['default_accessories']) && is_array($data['default_accessories'])) {
                // Filter out empty values
                $data['default_accessories'] = array_filter($data['default_accessories']);
            }

            $terminalModel = TerminalModel::create($data);

            DB::commit();
            return $terminalModel;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update terminal model
     */
    public function update(TerminalModel $terminalModel, array $data): TerminalModel
    {
        DB::beginTransaction();
        try {
            // Handle image upload
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                // Delete old image if exists
                if ($terminalModel->image_path) {
                    $this->deleteImage($terminalModel->image_path);
                }
                $data['image_path'] = $this->handleImageUpload($data['image']);
                unset($data['image']);
            }

            // Handle specifications JSON
            if (isset($data['specifications']) && is_array($data['specifications'])) {
                $data['specifications'] = array_filter($data['specifications'], function($spec) {
                    return !empty($spec['key']) || !empty($spec['value']);
                });
            }

            // Handle default accessories JSON
            if (isset($data['default_accessories']) && is_array($data['default_accessories'])) {
                $data['default_accessories'] = array_filter($data['default_accessories']);
            }

            $terminalModel->update($data);

            DB::commit();
            return $terminalModel->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete terminal model
     */
    public function delete(TerminalModel $terminalModel): bool
    {
        DB::beginTransaction();
        try {
            // Check if model has inventory serials
            $hasSerials = $terminalModel->inventorySerials()->exists();

            if ($hasSerials) {
                throw new \Exception('Cannot delete terminal model with existing inventory serials.');
            }

            $terminalModel->delete();

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Toggle status
     */
    public function toggleStatus(TerminalModel $terminalModel): TerminalModel
    {
        $newStatus = $terminalModel->status === TerminalModel::STATUS_ACTIVE
            ? TerminalModel::STATUS_INACTIVE
            : TerminalModel::STATUS_ACTIVE;

        $terminalModel->update(['status' => $newStatus]);

        return $terminalModel->fresh();
    }

    /**
     * Get stock summary for a model
     */
    public function getStockSummary(TerminalModel $terminalModel): array
    {
        $stockBalances = StockBalance::where('model_id', $terminalModel->id)
            ->with('depot')
            ->get();

        $totalInStock = $stockBalances->sum('quantity_in_stock');
        $totalIssued = $stockBalances->sum('quantity_issued');
        $totalInstalled = $stockBalances->sum('quantity_installed');

        return [
            'by_depot' => $stockBalances,
            'total_in_stock' => $totalInStock,
            'total_issued' => $totalIssued,
            'total_installed' => $totalInstalled,
            'total_overall' => $totalInStock + $totalIssued + $totalInstalled,
        ];
    }

    /**
     * Get recent stock movements
     */
    public function getRecentMovements(TerminalModel $terminalModel, int $limit = 10)
    {
        return $terminalModel->inventorySerials()
            ->with(['currentLocationDepot', 'currentLocationSite', 'currentLocationUser'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all active categories
     */
    public function getActiveCategories()
    {
        return TerminalCategory::active()
            ->orderBy('category_name')
            ->get();
    }

    /**
     * Get all accessory models for selection
     */
    public function getAccessoryModels()
    {
        $accessoryCategory = TerminalCategory::where('category_type', TerminalCategory::TYPE_ACCESSORY)
            ->first();

        if (!$accessoryCategory) {
            return collect();
        }

        return TerminalModel::where('category_id', $accessoryCategory->id)
            ->where('status', TerminalModel::STATUS_ACTIVE)
            ->orderBy('model_name')
            ->get();
    }

    /**
     * Handle image upload
     */
    protected function handleImageUpload(UploadedFile $image): string
    {
        $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs('terminal_models', $filename, 'public');

        return $path;
    }

    /**
     * Delete image file
     */
    protected function deleteImage(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Delete image for a terminal model
     */
    public function deleteModelImage(TerminalModel $terminalModel): bool
    {
        if ($terminalModel->image_path) {
            $this->deleteImage($terminalModel->image_path);
            $terminalModel->update(['image_path' => null]);
            return true;
        }
        return false;
    }
}
