<?php

namespace App\Services;

use App\Models\User;
use App\Models\SupervisorJobPricing;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class UserService
{
    /**
     * Create a new user
     */
    public function createUser(array $data): User
    {
        // Handle avatar upload
        if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            $data['avatar'] = $this->handleAvatarUpload($data['avatar']);
        }

        // DO NOT Hash::make() password here — User model has 'password' => 'hashed' cast
        // DO NOT json_encode() coverage_states/skill_tags — User model has 'array' cast

        // Extract role before creating user
        $role = $data['role'] ?? null;
        unset($data['role']);

        // Remove non-fillable fields
        unset($data['has_supervisor']);
        unset($data['password_confirmation']);
        unset($data['remove_avatar']);
        unset($data['job_pricing']); // Handled separately in controller

        // Clear supervisor_type if not supervisor role
        if ($role !== 'supervisor') {
            $data['supervisor_type'] = null;
        }

        // External supervisor cannot have technicians — clear supervisor_id for safety
        // (External supervisors should not appear in technician's supervisor dropdown)

        Log::info('UserService::createUser - Data keys: ' . implode(', ', array_keys($data)));
        Log::info('UserService::createUser - state_id: ' . ($data['state_id'] ?? 'NULL') . ', city_id: ' . ($data['city_id'] ?? 'NULL'));

        // Create user
        $user = User::create($data);

        // Assign role
        if ($role) {
            $user->assignRole($role);
        }

        return $user->fresh(['roles', 'supervisor', 'state', 'city']);
    }

    /**
     * Update an existing user
     */
    public function updateUser(User $user, array $data): User
    {
        // Handle remove avatar
        if (isset($data['remove_avatar']) && $data['remove_avatar']) {
            if ($user->avatar) {
                $this->deleteAvatar($user->avatar);
            }
            $data['avatar'] = null;
        }
        // Handle avatar upload
        elseif (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            if ($user->avatar) {
                $this->deleteAvatar($user->avatar);
            }
            $data['avatar'] = $this->handleAvatarUpload($data['avatar']);
        } else {
            unset($data['avatar']);
        }

        // DO NOT Hash::make() password — User model has 'password' => 'hashed' cast
        if (!isset($data['password']) || empty($data['password'])) {
            unset($data['password']);
        }
        unset($data['password_confirmation']);

        // DO NOT json_encode() coverage_states/skill_tags — User model has 'array' cast

        // Extract role before updating user
        $role = $data['role'] ?? null;
        unset($data['role']);

        // Remove non-fillable fields
        unset($data['has_supervisor']);
        unset($data['remove_avatar']);
        unset($data['job_pricing']); // Handled separately in controller

        // Clear supervisor_type if not supervisor role
        if ($role !== 'supervisor') {
            $data['supervisor_type'] = null;
        }

        // If changing from internal to external, detach all technicians
        if (
            $role === 'supervisor'
            && isset($data['supervisor_type'])
            && $data['supervisor_type'] === 'external'
            && $user->isInternalSupervisor()
        ) {
            User::where('supervisor_id', $user->id)->update(['supervisor_id' => null]);
            Log::info("UserService::updateUser - Detached all technicians from supervisor #{$user->id} (changed to external)");
        }

        Log::info('UserService::updateUser - User ID: ' . $user->id);
        Log::info('UserService::updateUser - Data keys: ' . implode(', ', array_keys($data)));
        Log::info('UserService::updateUser - state_id: ' . ($data['state_id'] ?? 'NULL') . ', city_id: ' . ($data['city_id'] ?? 'NULL'));

        // Update user
        $user->update($data);

        // Update role if provided
        if ($role) {
            $user->syncRoles([$role]);
        }

        Log::info('UserService::updateUser - After save - state_id: ' . $user->state_id . ', city_id: ' . $user->city_id);

        return $user->fresh(['roles', 'supervisor', 'state', 'city']);
    }

    /**
     * Save supervisor job pricing (upsert pattern).
     * Receives array of [category_id][type_id] => price
     *
     * @param User  $user
     * @param array $pricingData  e.g. ['1' => ['1' => '50.00', '2' => '30.00'], '2' => ['1' => '40.00']]
     */
    public function saveSupervisorJobPricing(User $user, array $pricingData): void
    {
        // Delete existing pricing for this supervisor
        SupervisorJobPricing::where('supervisor_id', $user->id)->delete();

        $rows = [];
        $now = now();

        foreach ($pricingData as $categoryId => $types) {
            if (!is_array($types)) {
                continue;
            }
            foreach ($types as $typeId => $price) {
                $priceValue = (float) $price;
                if ($priceValue <= 0) {
                    continue; // Skip zero/empty prices
                }
                $rows[] = [
                    'supervisor_id'   => $user->id,
                    'job_category_id' => (int) $categoryId,
                    'job_type_id'     => (int) $typeId,
                    'price'           => $priceValue,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
        }

        if (!empty($rows)) {
            SupervisorJobPricing::insert($rows);
        }

        Log::info("UserService::saveSupervisorJobPricing - Saved " . count($rows) . " pricing entries for supervisor #{$user->id}");
    }

    /**
     * Handle avatar file upload
     */
    protected function handleAvatarUpload(UploadedFile $file): string
    {
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        $directory = 'avatars';
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        $path = $file->storeAs($directory, $filename, 'public');

        return $path;
    }

    /**
     * Delete avatar file
     */
    protected function deleteAvatar(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Generate unique employee ID
     */
    public function generateEmployeeId(string $prefix = 'EMP'): string
    {
        $lastUser = User::withTrashed()
            ->where('employee_id', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastUser) {
            $lastNumber = (int) substr($lastUser->employee_id, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get statistics for user management dashboard
     * CHANGED: Removed 'suspended' — only total, active, inactive counted
     */
    public function getStatistics(): array
    {
        return [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'inactive' => User::where('status', 'inactive')->count(),
            'supervisors_internal' => User::whereHas('roles', fn ($q) => $q->where('roles.name', 'supervisor'))
                ->where('supervisor_type', 'internal')->count(),
            'supervisors_external' => User::whereHas('roles', fn ($q) => $q->where('roles.name', 'supervisor'))
                ->where('supervisor_type', 'external')->count(),
        ];
    }
}
