<?php

namespace App\Services;

use App\Models\ChargeCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Charge Catalog Service
 *
 * Handles business logic for charge catalog operations
 */
class ChargeCatalogService
{
    /**
     * Generate next charge code.
     */
    public function generateChargeCode(): string
    {
        $lastCharge = ChargeCatalog::orderBy('id', 'desc')->first();

        if (!$lastCharge) {
            return 'CHG000001';
        }

        $lastNumber = (int) substr($lastCharge->charge_code, 3);
        $newNumber = $lastNumber + 1;

        return 'CHG' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new charge.
     */
    public function create(array $data): ChargeCatalog
    {
        DB::beginTransaction();
        try {
            // Generate charge code if not provided
            if (!isset($data['charge_code'])) {
                $data['charge_code'] = $this->generateChargeCode();
            }

            // Convert boolean fields
            $data['is_taxable'] = $data['is_taxable'] ?? false;

            // Set default status
            $data['status'] = $data['status'] ?? ChargeCatalog::STATUS_ACTIVE;

            $charge = ChargeCatalog::create($data);

            // Log activity
            activity()
                ->performedOn($charge)
                ->causedBy(auth()->user())
                ->withProperties($data)
                ->log('Charge catalog created');

            DB::commit();
            return $charge;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create charge catalog: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing charge.
     */
    public function update(ChargeCatalog $charge, array $data): ChargeCatalog
    {
        DB::beginTransaction();
        try {
            $oldData = $charge->toArray();

            // Convert boolean fields
            $data['is_taxable'] = $data['is_taxable'] ?? false;

            $charge->update($data);

            // Log activity
            activity()
                ->performedOn($charge)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old' => $oldData,
                    'new' => $charge->fresh()->toArray(),
                ])
                ->log('Charge catalog updated');

            DB::commit();
            return $charge->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update charge catalog: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a charge.
     */
    public function delete(ChargeCatalog $charge): bool
    {
        DB::beginTransaction();
        try {
            // Log activity before deletion
            activity()
                ->performedOn($charge)
                ->causedBy(auth()->user())
                ->withProperties($charge->toArray())
                ->log('Charge catalog deleted');

            $charge->delete();

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete charge catalog: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Toggle charge status.
     */
    public function toggleStatus(ChargeCatalog $charge): ChargeCatalog
    {
        $newStatus = $charge->status === ChargeCatalog::STATUS_ACTIVE
            ? ChargeCatalog::STATUS_INACTIVE
            : ChargeCatalog::STATUS_ACTIVE;

        $charge->update(['status' => $newStatus]);

        // Log activity
        activity()
            ->performedOn($charge)
            ->causedBy(auth()->user())
            ->withProperties(['status' => $newStatus])
            ->log('Charge catalog status toggled');

        return $charge->fresh();
    }

    /**
     * Calculate total price with tax.
     */
    public function calculateTotalPrice(ChargeCatalog $charge, float $quantity = 1): array
    {
        $subtotal = $charge->default_price * $quantity;
        $taxAmount = 0;

        if ($charge->is_taxable && $charge->tax_rate > 0) {
            $taxAmount = $subtotal * ($charge->tax_rate / 100);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'total' => round($subtotal + $taxAmount, 2),
        ];
    }

    /**
     * Get charges by job type.
     */
    public function getByType(int $jobTypeId)
    {
        return ChargeCatalog::where('job_type_id', $jobTypeId)
            ->where('status', ChargeCatalog::STATUS_ACTIVE)
            ->orderBy('charge_name')
            ->get();
    }

    /**
     * Get all active charges grouped by job type.
     */
    public function getGroupedByType()
    {
        return ChargeCatalog::with('jobType')
            ->where('status', ChargeCatalog::STATUS_ACTIVE)
            ->orderBy('job_type_id')
            ->orderBy('charge_name')
            ->get()
            ->groupBy(function ($charge) {
                return $charge->jobType?->job_title ?? 'Unknown';
            });
    }

    /**
     * Search charges for quotation/invoice line items.
     */
    public function searchForLineItems(string $query = '')
    {
        return ChargeCatalog::with('jobType')
            ->where('status', ChargeCatalog::STATUS_ACTIVE)
            ->where(function ($q) use ($query) {
                $q->where('charge_name', 'like', "%{$query}%")
                  ->orWhere('charge_code', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhereHas('jobType', function ($jt) use ($query) {
                      $jt->where('job_title', 'like', "%{$query}%");
                  });
            })
            ->orderBy('charge_name')
            ->limit(50)
            ->get()
            ->map(function ($charge) {
                return [
                    'id' => $charge->id,
                    'code' => $charge->charge_code,
                    'name' => $charge->charge_name,
                    'type' => $charge->jobType?->job_title ?? 'Unknown',
                    'job_type_id' => $charge->job_type_id,
                    'price' => $charge->default_price,
                    'tax_rate' => $charge->tax_rate,
                    'is_taxable' => $charge->is_taxable,
                    'unit' => $charge->unit,
                ];
            });
    }
}
