<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargeCatalog extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    protected $table = 'charge_catalog';

    protected $fillable = [
        'charge_code', 'charge_name', 'job_type_id', 'description',
        'default_price', 'tax_rate', 'is_taxable', 'unit', 'status',
    ];

    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'is_taxable' => 'boolean',
        ];
    }

    /**
     * Get the job type (replaces old charge_type enum).
     */
    public function jobType()
    {
        return $this->belongsTo(JobType::class);
    }

    public function scopeActive($query) { return $query->where('status', self::STATUS_ACTIVE); }
    public function scopeByType($query, $jobTypeId) { return $query->where('job_type_id', $jobTypeId); }
}
