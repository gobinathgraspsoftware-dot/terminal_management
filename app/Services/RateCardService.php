<?php

namespace App\Services;

use App\Models\RateCard;
use App\Models\NumberSeries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RateCardService
{
    /**
     * Get DataTables data
     */
    public function getDatatableData(Request $request)
    {
        $query = RateCard::with('model');

        // Apply filters
        if ($request->filled('job_type')) {
            $query->where('job_type', $request->job_type);
        }

        if ($request->filled('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        if ($request->filled('state')) {
            $query->where('state', $request->state);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Show only effective rates for technicians
        if ($request->filled('effective_only') && $request->effective_only) {
            $query->effective();
        }

        // Search
        if ($request->filled('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function($q) use ($searchValue) {
                $q->where('rate_card_code', 'like', "%{$searchValue}%")
                  ->orWhere('rate_card_name', 'like', "%{$searchValue}%")
                  ->orWhere('description', 'like', "%{$searchValue}%")
                  ->orWhere('state', 'like', "%{$searchValue}%")
                  ->orWhereHas('model', function($modelQuery) use ($searchValue) {
                      $modelQuery->where('model_name', 'like', "%{$searchValue}%");
                  });
            });
        }

        // Total count
        $totalRecords = RateCard::count();
        $filteredRecords = $query->count();

        // Sorting
        $orderColumnIndex = $request->order[0]['column'] ?? 0;
        $orderDirection = $request->order[0]['dir'] ?? 'desc';

        $columns = [
            0 => 'rate_card_code',
            1 => 'rate_card_name',
            2 => 'job_type',
            3 => 'calculation_type',
            4 => 'rate_amount',
            5 => 'effective_from',
            6 => 'status',
        ];

        $orderColumn = $columns[$orderColumnIndex] ?? 'rate_card_code';
        $query->orderBy($orderColumn, $orderDirection);

        // Pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $rateCards = $query->skip($start)->take($length)->get();

        // Format data
        $data = $rateCards->map(function($rateCard) {
            return [
                'id' => $rateCard->id,
                'rate_card_code' => $rateCard->rate_card_code,
                'rate_card_name' => $rateCard->rate_card_name,
                'job_type' => $rateCard->job_type_display,
                'model' => $rateCard->model ? $rateCard->model->model_name : 'All Models',
                'state' => $rateCard->state ?? 'All States',
                'calculation_type' => $rateCard->calculation_type_display,
                'rate_amount' => 'RM ' . number_format($rateCard->rate_amount, 2),
                'effective_from' => $rateCard->effective_from->format('d M Y'),
                'effective_to' => $rateCard->effective_to ? $rateCard->effective_to->format('d M Y') : 'No End Date',
                'is_effective' => $rateCard->is_effective,
                'is_expired' => $rateCard->is_expired,
                'status' => $rateCard->status,
                'status_badge' => $rateCard->status_badge,
                'created_at' => $rateCard->created_at->format('d M Y'),
            ];
        });

        return response()->json([
            'draw' => intval($request->draw ?? 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Create a new rate card
     */
    public function create(array $data): RateCard
    {
        // Generate rate card code
        $data['rate_card_code'] = $this->generateRateCardCode();

        return RateCard::create($data);
    }

    /**
     * Update rate card
     */
    public function update(RateCard $rateCard, array $data): RateCard
    {
        $rateCard->update($data);
        return $rateCard->fresh();
    }

    /**
     * Delete rate card
     */
    public function delete(RateCard $rateCard): bool
    {
        // Check if rate card is being used in any commission calculations
        // This would check JobOrder or PayoutDetail tables if they reference rate cards
        // For now, we'll just delete

        return $rateCard->delete();
    }

    /**
     * Generate unique rate card code
     *
     * FIXED: Uses correct column names for number_series table
     * - series_type (not doc_type)
     * - current_number (not next_number)
     * - number_length (not padding)
     */
    private function generateRateCardCode(): string
    {
        return DB::transaction(function() {
            // Use correct column name: series_type instead of doc_type
            $numberSeries = NumberSeries::where('series_type', 'rate_card')
                ->lockForUpdate()
                ->first();

            if (!$numberSeries) {
                // Create default number series for rate cards
                // Using correct column names
                $numberSeries = NumberSeries::create([
                    'series_type' => 'rate_card',
                    'prefix' => 'RC',
                    'suffix' => null,
                    'current_number' => 0,
                    'number_length' => 6,
                    'reset_frequency' => 'never',
                    'is_active' => 1,
                ]);
            }

            // Increment current_number
            $nextNumber = $numberSeries->current_number + 1;

            // Generate code with padding
            $code = $numberSeries->prefix . str_pad(
                $nextNumber,
                $numberSeries->number_length,
                '0',
                STR_PAD_LEFT
            );

            // Add suffix if exists
            if ($numberSeries->suffix) {
                $code .= $numberSeries->suffix;
            }

            // Update current_number
            $numberSeries->update(['current_number' => $nextNumber]);

            return $code;
        });
    }

    /**
     * Find applicable rate card for a job
     *
     * Priority order:
     * 1. Specific model + specific state + specific job type
     * 2. Specific model + specific state + all job types
     * 3. Specific model + all states + specific job type
     * 4. Specific model + all states + all job types
     * 5. All models + specific state + specific job type
     * 6. All models + specific state + all job types
     * 7. All models + all states + specific job type
     * 8. All models + all states + all job types (default rate)
     */
    public function findApplicableRate(
        string $jobType,
        ?int $modelId = null,
        ?string $state = null,
        ?string $date = null
    ): ?RateCard {
        $date = $date ?? now()->toDateString();

        // Define priority search patterns
        $searchPatterns = [
            // Specific model + specific state + specific job type
            ['model_id' => $modelId, 'state' => $state, 'job_type' => $jobType],
            // Specific model + specific state + all job types
            ['model_id' => $modelId, 'state' => $state, 'job_type' => RateCard::JOB_TYPE_ALL],
            // Specific model + all states + specific job type
            ['model_id' => $modelId, 'state' => null, 'job_type' => $jobType],
            // Specific model + all states + all job types
            ['model_id' => $modelId, 'state' => null, 'job_type' => RateCard::JOB_TYPE_ALL],
            // All models + specific state + specific job type
            ['model_id' => null, 'state' => $state, 'job_type' => $jobType],
            // All models + specific state + all job types
            ['model_id' => null, 'state' => $state, 'job_type' => RateCard::JOB_TYPE_ALL],
            // All models + all states + specific job type
            ['model_id' => null, 'state' => null, 'job_type' => $jobType],
            // All models + all states + all job types (default)
            ['model_id' => null, 'state' => null, 'job_type' => RateCard::JOB_TYPE_ALL],
        ];

        // Try each pattern in priority order
        foreach ($searchPatterns as $pattern) {
            $query = RateCard::active()->effective($date);

            // Apply pattern criteria
            if ($pattern['model_id'] !== null) {
                $query->where('model_id', $pattern['model_id']);
            } else {
                $query->whereNull('model_id');
            }

            if ($pattern['state'] !== null) {
                $query->where('state', $pattern['state']);
            } else {
                $query->whereNull('state');
            }

            $query->where('job_type', $pattern['job_type']);

            $rateCard = $query->first();

            if ($rateCard) {
                return $rateCard;
            }
        }

        return null;
    }

    /**
     * Calculate commission for a job using applicable rate card
     */
    public function calculateJobCommission(
        string $jobType,
        ?int $modelId = null,
        ?string $state = null,
        float $jobValue = 0,
        int $terminalCount = 1,
        ?string $date = null
    ): array {
        $rateCard = $this->findApplicableRate($jobType, $modelId, $state, $date);

        if (!$rateCard) {
            return [
                'success' => false,
                'message' => 'No applicable rate card found.',
                'commission' => 0,
                'rate_card' => null,
            ];
        }

        $commission = $rateCard->calculateCommission($jobValue, $terminalCount);

        return [
            'success' => true,
            'commission' => $commission,
            'rate_card' => $rateCard,
            'rate_card_id' => $rateCard->id,
            'rate_card_code' => $rateCard->rate_card_code,
            'calculation_type' => $rateCard->calculation_type,
            'rate_amount' => $rateCard->rate_amount,
        ];
    }

    /**
     * Get all effective rate cards for a technician
     */
    public function getEffectiveRatesForTechnician(?array $coverageStates = null): \Illuminate\Support\Collection
    {
        $query = RateCard::active()
            ->effective()
            ->with('model')
            ->orderBy('job_type')
            ->orderBy('rate_amount', 'desc');

        // If technician has specific coverage states, filter accordingly
        if ($coverageStates && count($coverageStates) > 0) {
            $query->where(function($q) use ($coverageStates) {
                $q->whereNull('state')
                  ->orWhereIn('state', $coverageStates);
            });
        }

        return $query->get();
    }

    /**
     * Get rate card history for a specific criteria
     */
    public function getRateHistory(string $jobType, ?int $modelId = null, ?string $state = null): \Illuminate\Support\Collection
    {
        $query = RateCard::where('job_type', $jobType);

        if ($modelId) {
            $query->where('model_id', $modelId);
        } else {
            $query->whereNull('model_id');
        }

        if ($state) {
            $query->where('state', $state);
        } else {
            $query->whereNull('state');
        }

        return $query->orderBy('effective_from', 'desc')->get();
    }
}
