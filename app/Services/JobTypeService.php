<?php

namespace App\Services;

use App\Models\JobType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JobTypeService
{
    /**
     * Create a new job type.
     */
    public function create(array $data): JobType
    {
        DB::beginTransaction();
        try {
            $data['slug'] = $data['slug'] ?? Str::slug($data['job_title']);

            // Ensure unique slug
            $originalSlug = $data['slug'];
            $counter = 1;
            while (JobType::withTrashed()->where('slug', $data['slug'])->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter++;
            }

            $data['status'] = $data['status'] ?? JobType::STATUS_ACTIVE;

            $jobType = JobType::create($data);

            activity()
                ->performedOn($jobType)
                ->causedBy(auth()->user())
                ->withProperties($data)
                ->log('Job type created');

            DB::commit();
            return $jobType;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create job type: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing job type.
     */
    public function update(JobType $jobType, array $data): JobType
    {
        DB::beginTransaction();
        try {
            $oldData = $jobType->toArray();

            if (isset($data['job_title']) && $data['job_title'] !== $jobType->job_title) {
                $data['slug'] = Str::slug($data['job_title']);
                $originalSlug = $data['slug'];
                $counter = 1;
                while (JobType::withTrashed()->where('slug', $data['slug'])->where('id', '!=', $jobType->id)->exists()) {
                    $data['slug'] = $originalSlug . '-' . $counter++;
                }
            }

            $jobType->update($data);

            activity()
                ->performedOn($jobType)
                ->causedBy(auth()->user())
                ->withProperties(['old' => $oldData, 'new' => $jobType->fresh()->toArray()])
                ->log('Job type updated');

            DB::commit();
            return $jobType->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update job type: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a job type (soft delete).
     */
    public function delete(JobType $jobType): bool
    {
        DB::beginTransaction();
        try {
            // Check if job type is in use
            if ($jobType->jobOrders()->exists()) {
                throw new \Exception('Cannot delete job type that is linked to existing job orders. Consider deactivating it instead.');
            }

            activity()
                ->performedOn($jobType)
                ->causedBy(auth()->user())
                ->withProperties($jobType->toArray())
                ->log('Job type deleted');

            $jobType->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete job type: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Toggle job type status.
     */
    public function toggleStatus(JobType $jobType): JobType
    {
        $newStatus = $jobType->status === JobType::STATUS_ACTIVE
            ? JobType::STATUS_INACTIVE
            : JobType::STATUS_ACTIVE;

        $jobType->update(['status' => $newStatus]);

        activity()
            ->performedOn($jobType)
            ->causedBy(auth()->user())
            ->withProperties(['status' => $newStatus])
            ->log('Job type status toggled');

        return $jobType->fresh();
    }

    /**
     * Get active job types for dropdowns.
     */
    public function getActiveForDropdown()
    {
        return JobType::active()
            ->orderBy('job_title')
            ->pluck('job_title', 'id');
    }
}
