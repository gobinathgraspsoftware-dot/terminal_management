<?php

namespace App\Services;

use App\Models\JobCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JobCategoryService
{
    /**
     * Create a new job category.
     */
    public function create(array $data): JobCategory
    {
        DB::beginTransaction();
        try {
            $data['slug'] = $data['slug'] ?? Str::slug($data['category_name']);

            // Ensure unique slug
            $originalSlug = $data['slug'];
            $counter = 1;
            while (JobCategory::withTrashed()->where('slug', $data['slug'])->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter++;
            }

            $data['status'] = $data['status'] ?? JobCategory::STATUS_ACTIVE;

            $jobCategory = JobCategory::create($data);

            activity()
                ->performedOn($jobCategory)
                ->causedBy(auth()->user())
                ->withProperties($data)
                ->log('Job category created');

            DB::commit();
            return $jobCategory;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create job category: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing job category.
     */
    public function update(JobCategory $jobCategory, array $data): JobCategory
    {
        DB::beginTransaction();
        try {
            $oldData = $jobCategory->toArray();

            if (isset($data['category_name']) && $data['category_name'] !== $jobCategory->category_name) {
                $data['slug'] = Str::slug($data['category_name']);
                $originalSlug = $data['slug'];
                $counter = 1;
                while (JobCategory::withTrashed()->where('slug', $data['slug'])->where('id', '!=', $jobCategory->id)->exists()) {
                    $data['slug'] = $originalSlug . '-' . $counter++;
                }
            }

            $jobCategory->update($data);

            activity()
                ->performedOn($jobCategory)
                ->causedBy(auth()->user())
                ->withProperties(['old' => $oldData, 'new' => $jobCategory->fresh()->toArray()])
                ->log('Job category updated');

            DB::commit();
            return $jobCategory->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update job category: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a job category (soft delete).
     */
    public function delete(JobCategory $jobCategory): bool
    {
        DB::beginTransaction();
        try {
            // Check if job category is in use
            if ($jobCategory->jobOrders()->exists()) {
                throw new \Exception('Cannot delete job category that is linked to existing job orders. Consider deactivating it instead.');
            }

            activity()
                ->performedOn($jobCategory)
                ->causedBy(auth()->user())
                ->withProperties($jobCategory->toArray())
                ->log('Job category deleted');

            $jobCategory->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete job category: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Toggle job category status.
     */
    public function toggleStatus(JobCategory $jobCategory): JobCategory
    {
        $newStatus = $jobCategory->status === JobCategory::STATUS_ACTIVE
            ? JobCategory::STATUS_INACTIVE
            : JobCategory::STATUS_ACTIVE;

        $jobCategory->update(['status' => $newStatus]);

        activity()
            ->performedOn($jobCategory)
            ->causedBy(auth()->user())
            ->withProperties(['status' => $newStatus])
            ->log('Job category status toggled');

        return $jobCategory->fresh();
    }

    /**
     * Get active job categories for dropdowns.
     */
    public function getActiveForDropdown()
    {
        return JobCategory::active()
            ->orderBy('category_name')
            ->pluck('category_name', 'id');
    }
}
