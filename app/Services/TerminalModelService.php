<?php

namespace App\Services;

use App\Models\TerminalModel;
use App\Models\TerminalCategory;
use App\Models\InventorySerial;
use App\Models\StockBalance;
use App\Models\Depot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            ->where('model_code', 'like', 'MDL%')
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
                $data['specifications'] = array_values(array_filter($data['specifications'], function ($spec) {
                    return !empty($spec['key']) || !empty($spec['value']);
                }));
            }

            // Handle default accessories JSON
            if (isset($data['default_accessories']) && is_array($data['default_accessories'])) {
                $data['default_accessories'] = array_values(array_filter($data['default_accessories']));
            }

            $terminalModel = TerminalModel::create($data);

            DB::commit();

            Log::info('Terminal model created', ['id' => $terminalModel->id, 'model_code' => $terminalModel->model_code]);

            return $terminalModel;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create terminal model', ['error' => $e->getMessage()]);
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
                $data['specifications'] = array_values(array_filter($data['specifications'], function ($spec) {
                    return !empty($spec['key']) || !empty($spec['value']);
                }));
            }

            // Handle default accessories JSON
            if (isset($data['default_accessories']) && is_array($data['default_accessories'])) {
                $data['default_accessories'] = array_values(array_filter($data['default_accessories']));
            }

            $terminalModel->update($data);

            DB::commit();

            Log::info('Terminal model updated', ['id' => $terminalModel->id]);

            return $terminalModel->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update terminal model', ['id' => $terminalModel->id, 'error' => $e->getMessage()]);
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

            Log::info('Terminal model deleted', ['id' => $terminalModel->id]);

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
     * StockBalance uses location_type/location_id (not a depot relationship)
     */
    public function getStockSummary(TerminalModel $terminalModel): array
    {
        $stockBalances = StockBalance::where('model_id', $terminalModel->id)->get();

        $byLocation = $stockBalances->map(function ($balance) {
            $locationName = $balance->location_name; // uses accessor from StockBalance model
            return [
                'id' => $balance->id,
                'location_type' => $balance->location_type,
                'location_id' => $balance->location_id,
                'location_name' => $locationName,
                'quantity_on_hand' => $balance->quantity_on_hand,
                'quantity_reserved' => $balance->quantity_reserved,
                'quantity_available' => $balance->quantity_available,
            ];
        });

        $totalOnHand = $stockBalances->sum('quantity_on_hand');
        $totalReserved = $stockBalances->sum('quantity_reserved');

        return [
            'by_location' => $byLocation,
            'total_on_hand' => $totalOnHand,
            'total_reserved' => $totalReserved,
            'total_available' => $totalOnHand - $totalReserved,
            'total_overall' => $totalOnHand,
        ];
    }

    /**
     * Get recent stock movements (recent serial updates)
     */
    public function getRecentMovements(TerminalModel $terminalModel, int $limit = 10)
    {
        return $terminalModel->inventorySerials()
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
        $directory = 'terminal_models';

        // Ensure directory exists
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs($directory, $filename, 'public');

        Log::info('Terminal model image uploaded', ['path' => $path]);

        return $path;
    }

    /**
     * Delete image file
     */
    protected function deleteImage(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            Log::info('Terminal model image deleted', ['path' => $path]);
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

    /**
     * Get image URL for a terminal model
     */
    public function getImageUrl(?string $imagePath): string
    {
        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            return asset('storage/' . $imagePath);
        }

        return asset('images/no-image.png');
    }
}
