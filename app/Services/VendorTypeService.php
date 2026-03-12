<?php

namespace App\Services;

use App\Models\VendorType;
use Illuminate\Support\Facades\DB;

class VendorTypeService
{
    public function createVendorType(array $data): VendorType
    {
        $data['is_active'] = $data['is_active'] ?? true;

        return VendorType::create($data);
    }

    public function updateVendorType(VendorType $vendorType, array $data): VendorType
    {
        $vendorType->update($data);

        return $vendorType->fresh();
    }

    public function deleteVendorType(VendorType $vendorType): bool
    {
        return $vendorType->delete();
    }

    public function toggleStatus(VendorType $vendorType): VendorType
    {
        $vendorType->update(['is_active' => !$vendorType->is_active]);

        return $vendorType->fresh();
    }

    public function getStats(): array
    {
        return [
            'total' => VendorType::count(),
            'active' => VendorType::where('is_active', true)->count(),
            'inactive' => VendorType::where('is_active', false)->count(),
        ];
    }
}
