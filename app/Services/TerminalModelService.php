<?php

namespace App\Services;

use App\Models\TerminalModel;
use App\Models\TerminalCategory;
use App\Models\InventorySerial;
use App\Models\StockBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
            if (empty($data['model_code'])) {
                $data['model_code'] = $this->generateModelCode();
            }

            // Handle image upload
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $data['image_path'] = $this->handleImageUpload($data['image']);
                unset($data['image']);
            } else {
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

            Log::info('Terminal model created', [
                'id' => $terminalModel->id,
                'model_code' => $terminalModel->model_code,
                'image_path' => $terminalModel->image_path,
            ]);

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
                if ($terminalModel->image_path) {
                    $this->deleteImage($terminalModel->image_path);
                }
                $data['image_path'] = $this->handleImageUpload($data['image']);
                unset($data['image']);
            } else {
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

            Log::info('Terminal model updated', [
                'id' => $terminalModel->id,
                'image_path' => $terminalModel->image_path,
            ]);

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
            if ($terminalModel->inventorySerials()->exists()) {
                throw new \Exception('Cannot delete terminal model with existing inventory serials.');
            }

            if ($terminalModel->image_path) {
                $this->deleteImage($terminalModel->image_path);
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
        $stockBalances = StockBalance::where('model_id', $terminalModel->id)->get();

        $byLocation = $stockBalances->map(function ($balance) {
            return [
                'id' => $balance->id,
                'location_type' => $balance->location_type,
                'location_id' => $balance->location_id,
                'location_name' => $balance->location_name,
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
     * Get recent stock movements
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
     * Get the REAL public directory path.
     *
     * On cPanel: public_path() returns /home/user/laravel-app/public/
     * but the actual web root is /home/user/public_html/
     * DOCUMENT_ROOT always returns the real web-accessible directory.
     */
    protected function getRealPublicPath(): string
    {
        // DOCUMENT_ROOT is set by the web server and always points to
        // the actual directory serving web requests
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            return rtrim($_SERVER['DOCUMENT_ROOT'], '/');
        }

        // Fallback for CLI (artisan commands, queue workers)
        return rtrim(public_path(), '/');
    }

    /**
     * Handle image upload — cPanel compatible.
     *
     * Uses DOCUMENT_ROOT to find the real public directory,
     * then moves the file to {DOCUMENT_ROOT}/storage/terminal_models/
     * This works on both local (XAMPP) and cPanel without symlinks.
     */
    protected function handleImageUpload(UploadedFile $image): string
    {
        $directory = 'terminal_models';
        $realPublicPath = $this->getRealPublicPath();
        $targetDir = $realPublicPath . '/storage/' . $directory;

        // Create directory if not exists
        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
            Log::info('Created terminal_models directory', ['path' => $targetDir]);
        }

        $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $relativePath = $directory . '/' . $filename;

        // Move file directly to the real public storage directory
        $image->move($targetDir, $filename);

        // Verify file exists
        $fullPath = $targetDir . '/' . $filename;
        if (!file_exists($fullPath)) {
            Log::error('Image file not found after move', [
                'target_dir' => $targetDir,
                'filename' => $filename,
                'full_path' => $fullPath,
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'not set',
                'public_path' => public_path(),
            ]);
            throw new \Exception('Failed to save image file');
        }

        Log::info('Terminal model image uploaded successfully', [
            'relative_path' => $relativePath,
            'full_path' => $fullPath,
            'file_size' => filesize($fullPath),
            'document_root' => $realPublicPath,
            'url' => asset('storage/' . $relativePath),
        ]);

        return $relativePath;
    }

    /**
     * Delete image file — checks real public path, public_path, and storage disk
     */
    protected function deleteImage(string $path): void
    {
        $deleted = false;

        // 1. Check real public path (DOCUMENT_ROOT)
        $realPublicPath = $this->getRealPublicPath();
        $realFile = $realPublicPath . '/storage/' . $path;
        if (file_exists($realFile)) {
            unlink($realFile);
            Log::info('Image deleted from DOCUMENT_ROOT', ['path' => $realFile]);
            $deleted = true;
        }

        // 2. Check public_path (local/XAMPP)
        $publicFile = public_path('storage/' . $path);
        if (!$deleted && file_exists($publicFile)) {
            unlink($publicFile);
            Log::info('Image deleted from public_path', ['path' => $publicFile]);
            $deleted = true;
        }

        // 3. Fallback: storage disk (older uploads via storeAs)
        if (!$deleted && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            Log::info('Image deleted from storage disk', ['path' => $path]);
        }
    }

    /**
     * Delete image for a terminal model
     */
    public function deleteModelImage(TerminalModel $terminalModel): bool
    {
        if ($terminalModel->image_path) {
            $this->deleteImage($terminalModel->image_path);

            DB::table('terminal_models')
                ->where('id', $terminalModel->id)
                ->update([
                    'image_path' => null,
                    'updated_at' => now(),
                ]);

            $terminalModel->refresh();

            return true;
        }
        return false;
    }
}
