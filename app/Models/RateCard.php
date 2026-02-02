<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateCard extends Model
{
    use HasFactory;

    // Job Types
    const JOB_TYPE_ALL = 'all';
    const JOB_TYPE_INSTALLATION = 'installation';
    const JOB_TYPE_SERVICE = 'service';
    const JOB_TYPE_REPAIR = 'repair';
    const JOB_TYPE_REPLACEMENT = 'replacement';
    const JOB_TYPE_COLLECTION = 'collection';

    // Calculation Types
    const CALC_TYPE_FLAT = 'flat';
    const CALC_TYPE_PER_TERMINAL = 'per_terminal';
    const CALC_TYPE_PERCENTAGE = 'percentage';

    // Status
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

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

    protected function casts(): array
    {
        return [
            'rate_amount' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    /**
     * Relationships
     */
    public function model()
    {
        return $this->belongsTo(TerminalModel::class, 'model_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeEffective($query, $date = null)
    {
        $date = $date ?? now();
        return $query->where('effective_from', '<=', $date)
            ->where(function($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $date);
            });
    }

    public function scopeForJobType($query, $jobType)
    {
        return $query->where(function($q) use ($jobType) {
            $q->where('job_type', $jobType)
              ->orWhere('job_type', self::JOB_TYPE_ALL);
        });
    }

    public function scopeForModel($query, $modelId)
    {
        return $query->where(function($q) use ($modelId) {
            $q->whereNull('model_id')
              ->orWhere('model_id', $modelId);
        });
    }

    public function scopeForState($query, $state)
    {
        return $query->where(function($q) use ($state) {
            $q->whereNull('state')
              ->orWhere('state', $state);
        });
    }

    /**
     * Accessors
     */
    public function getJobTypeDisplayAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->job_type));
    }

    public function getCalculationTypeDisplayAttribute()
    {
        $types = [
            self::CALC_TYPE_FLAT => 'Flat Rate',
            self::CALC_TYPE_PER_TERMINAL => 'Per Terminal',
            self::CALC_TYPE_PERCENTAGE => 'Percentage',
        ];
        return $types[$this->calculation_type] ?? $this->calculation_type;
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_ACTIVE => '<span class="badge bg-success">Active</span>',
            self::STATUS_INACTIVE => '<span class="badge bg-secondary">Inactive</span>',
        ];
        return $badges[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    public function getIsExpiredAttribute()
    {
        return $this->effective_to && $this->effective_to->isPast();
    }

    public function getIsEffectiveAttribute()
    {
        $now = now();
        $started = $this->effective_from->lte($now);
        $notExpired = !$this->effective_to || $this->effective_to->gte($now);
        return $started && $notExpired;
    }

    /**
     * Static methods for options
     */
    public static function getJobTypeOptions()
    {
        return [
            self::JOB_TYPE_ALL => 'All Job Types',
            self::JOB_TYPE_INSTALLATION => 'Installation',
            self::JOB_TYPE_SERVICE => 'Service',
            self::JOB_TYPE_REPAIR => 'Repair',
            self::JOB_TYPE_REPLACEMENT => 'Replacement',
            self::JOB_TYPE_COLLECTION => 'Collection',
        ];
    }

    public static function getCalculationTypeOptions()
    {
        return [
            self::CALC_TYPE_FLAT => 'Flat Rate',
            self::CALC_TYPE_PER_TERMINAL => 'Per Terminal',
            self::CALC_TYPE_PERCENTAGE => 'Percentage of Job Value',
        ];
    }

    public static function getStatusOptions()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
        ];
    }

    /**
     * Malaysian states
     */
    public static function getMalaysianStates()
    {
        return [
            'Johor' => 'Johor',
            'Kedah' => 'Kedah',
            'Kelantan' => 'Kelantan',
            'Melaka' => 'Melaka',
            'Negeri Sembilan' => 'Negeri Sembilan',
            'Pahang' => 'Pahang',
            'Penang' => 'Penang',
            'Perak' => 'Perak',
            'Perlis' => 'Perlis',
            'Sabah' => 'Sabah',
            'Sarawak' => 'Sarawak',
            'Selangor' => 'Selangor',
            'Terengganu' => 'Terengganu',
            'WP Kuala Lumpur' => 'WP Kuala Lumpur',
            'WP Labuan' => 'WP Labuan',
            'WP Putrajaya' => 'WP Putrajaya',
        ];
    }

    /**
     * Calculate commission for a job
     */
    public function calculateCommission($jobValue = 0, $terminalCount = 1)
    {
        $amount = 0;

        switch ($this->calculation_type) {
            case self::CALC_TYPE_FLAT:
                $amount = $this->rate_amount;
                break;

            case self::CALC_TYPE_PER_TERMINAL:
                $amount = $this->rate_amount * $terminalCount;
                break;

            case self::CALC_TYPE_PERCENTAGE:
                $amount = ($jobValue * $this->rate_amount) / 100;
                break;
        }

        // Apply min/max limits
        if ($this->min_amount && $amount < $this->min_amount) {
            $amount = $this->min_amount;
        }

        if ($this->max_amount && $amount > $this->max_amount) {
            $amount = $this->max_amount;
        }

        return round($amount, 2);
    }
}
