<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockIssue extends Model
{
    use HasFactory;

    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';
    const STATUS_CANCELLED = 'cancelled';

    const TYPE_ISSUE_TO_TECH = 'issue_to_tech';
    const TYPE_RETURN_FROM_TECH = 'return_from_tech';

    protected $fillable = [
        'issue_no', 'issue_date', 'issue_type', 'from_depot_id', 'to_technician_id',
        'from_technician_id', 'to_depot_id', 'total_items', 'remarks', 'status',
        'posted_at', 'posted_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    public function fromDepot() { return $this->belongsTo(Depot::class, 'from_depot_id'); }
    public function toTechnician() { return $this->belongsTo(User::class, 'to_technician_id'); }
    public function fromTechnician() { return $this->belongsTo(User::class, 'from_technician_id'); }
    public function toDepot() { return $this->belongsTo(Depot::class, 'to_depot_id'); }
    public function lines() { return $this->hasMany(StockIssueLine::class); }
}
