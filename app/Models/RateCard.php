<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'rate_card_code',
        'rate_card_name',
        'description',
        'job_type',
        'model_id',
        'state',
        'calculation_type',
        'rate_amount',
        'min_amount',
        'max_amount',
        'effective_from',
        'effective_to',
        'status',
    ];

    protected $casts = [
        'rate_amount' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    // ==========================================
    // CONSTANTS
    // ==========================================

    const JOB_TYPES = [
        'installation' => 'Installation',
        'service' => 'Service',
        'repair' => 'Repair',
        'replacement' => 'Replacement',
        'collection' => 'Collection',
        'all' => 'All Job Types',
    ];

    const CALCULATION_TYPES = [
        'flat' => 'Flat Rate',
        'per_terminal' => 'Per Terminal',
        'percentage' => 'Percentage',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the terminal model for this rate card.
     */
    public function terminalModel()
    {
        return $this->belongsTo(TerminalModel::class, 'model_id');
    }

    /**
     * Get users with this as default rate card.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'default_rate_card_id');
    }

    /**
     * Get jobs using this rate card.
     */
    public function jobs()
    {
        return $this->hasMany(JobOrder::class, 'commission_rate_card_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope to get only active rate cards.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->where('effective_from', '<=', now())
                          ->orWhereNull('effective_from');
                    })
                    ->where(function ($q) {
                        $q->where('effective_to', '>=', now())
                          ->orWhereNull('effective_to');
                    });
    }

    /**
     * Scope to get inactive rate cards.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope to filter by job type.
     */
    public function scopeByJobType($query, $jobType)
    {
        return $query->where(function ($q) use ($jobType) {
            $q->where('job_type', $jobType)
              ->orWhere('job_type', 'all');
        });
    }

    /**
     * Scope to filter by terminal model.
     */
    public function scopeByModel($query, $modelId)
    {
        return $query->where(function ($q) use ($modelId) {
            $q->where('model_id', $modelId)
              ->orWhereNull('model_id');
        });
    }

    /**
     * Scope to filter by state.
     */
    public function scopeByState($query, $state)
    {
        return $query->where(function ($q) use ($state) {
            $q->where('state', $state)
              ->orWhereNull('state');
        });
    }

    /**
     * Scope to get rate cards effective on a specific date.
     */
    public function scopeEffectiveOn($query, $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->where('effective_from', '<=', $date)
              ->orWhereNull('effective_from');
        })->where(function ($q) use ($date) {
            $q->where('effective_to', '>=', $date)
              ->orWhereNull('effective_to');
        });
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get the job type label.
     */
    public function getJobTypeLabelAttribute(): string
    {
        return self::JOB_TYPES[$this->job_type] ?? ucfirst($this->job_type);
    }

    /**
     * Get the calculation type label.
     */
    public function getCalculationTypeLabelAttribute(): string
    {
        return self::CALCULATION_TYPES[$this->calculation_type] ?? ucfirst($this->calculation_type);
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->status === 'active' ? 'badge bg-success' : 'badge bg-secondary';
    }

    /**
     * Check if rate card is currently effective.
     */
    public function getIsEffectiveAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();

        if ($this->effective_from && $this->effective_from->isFuture()) {
            return false;
        }

        if ($this->effective_to && $this->effective_to->isPast()) {
            return false;
        }

        return true;
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Calculate commission based on job details.
     */
    public function calculateCommission(float $amount = 0, int $terminalCount = 1): float
    {
        $commission = 0;

        switch ($this->calculation_type) {
            case 'flat':
                $commission = $this->rate_amount;
                break;

            case 'per_terminal':
                $commission = $this->rate_amount * $terminalCount;
                break;

            case 'percentage':
                $commission = ($amount * $this->rate_amount) / 100;
                break;
        }

        // Apply min/max constraints
        if ($this->min_amount && $commission < $this->min_amount) {
            $commission = $this->min_amount;
        }

        if ($this->max_amount && $commission > $this->max_amount) {
            $commission = $this->max_amount;
        }

        return round($commission, 2);
    }

    /**
     * Check if rate card is active.
     */
    public function isActive(): bool
    {
        return $this->is_effective;
    }

    /**
     * Check if rate card applies to a specific job.
     */
    public function appliesTo(string $jobType, ?int $modelId = null, ?string $state = null): bool
    {
        // Check job type
        if ($this->job_type !== 'all' && $this->job_type !== $jobType) {
            return false;
        }

        // Check model
        if ($this->model_id && $this->model_id !== $modelId) {
            return false;
        }

        // Check state
        if ($this->state && $this->state !== $state) {
            return false;
        }

        // Check if effective
        if (!$this->is_effective) {
            return false;
        }

        return true;
    }

    /**
     * Generate unique rate card code.
     */
    public static function generateRateCardCode(): string
    {
        $year = date('Y');
        $prefix = "RC{$year}";
        
        $lastRateCard = static::where('rate_card_code', 'like', "{$prefix}%")
                             ->orderBy('rate_card_code', 'desc')
                             ->first();
        
        if ($lastRateCard) {
            $lastNumber = (int) substr($lastRateCard->rate_card_code, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return $prefix . $newNumber;
    }

    /**
     * Boot method to auto-generate code.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rateCard) {
            if (empty($rateCard->rate_card_code)) {
                $rateCard->rate_card_code = static::generateRateCardCode();
            }
        });
    }

    /**
     * Find best matching rate card for job.
     * Priority: Specific model + specific state > Specific model > Specific state > Default
     */
    public static function findBestMatch(string $jobType, ?int $modelId = null, ?string $state = null)
    {
        $query = static::active()->byJobType($jobType)->effectiveOn(now());

        // Try to find most specific match first
        // 1. Model + State match
        if ($modelId && $state) {
            $rateCard = (clone $query)->where('model_id', $modelId)
                                      ->where('state', $state)
                                      ->first();
            if ($rateCard) return $rateCard;
        }

        // 2. Model match only
        if ($modelId) {
            $rateCard = (clone $query)->where('model_id', $modelId)
                                      ->whereNull('state')
                                      ->first();
            if ($rateCard) return $rateCard;
        }

        // 3. State match only
        if ($state) {
            $rateCard = (clone $query)->whereNull('model_id')
                                      ->where('state', $state)
                                      ->first();
            if ($rateCard) return $rateCard;
        }

        // 4. Default (no model, no state)
        return $query->whereNull('model_id')
                    ->whereNull('state')
                    ->first();
    }
}