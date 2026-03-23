<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockIn extends Model
{
    use HasFactory;

    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';
    const STATUS_CANCELLED = 'cancelled';

    const SOURCE_MANUAL = 'manual';
    const SOURCE_RETURN_FROM_SITE = 'return_from_site';
    const SOURCE_FOUND = 'found';
    const SOURCE_DONATION = 'donation';
    const SOURCE_OTHER = 'other';

    const SOURCE_OPTIONS = [
        self::SOURCE_MANUAL          => 'Manual Entry',
        self::SOURCE_RETURN_FROM_SITE => 'Return from Site',
        self::SOURCE_FOUND           => 'Found/Recovered',
        self::SOURCE_DONATION        => 'Donation',
        self::SOURCE_OTHER           => 'Other',
    ];

    const STATUS_BADGES = [
        self::STATUS_DRAFT     => 'secondary',
        self::STATUS_POSTED    => 'success',
        self::STATUS_CANCELLED => 'danger',
    ];

    protected $fillable = [
        'stock_in_no', 'stock_in_date', 'depot_id', 'source_type',
        'source_reference', 'total_items', 'remarks', 'status',
        'posted_at', 'posted_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'stock_in_date' => 'date',
            'posted_at'     => 'datetime',
        ];
    }

    public function depot()       { return $this->belongsTo(Depot::class); }
    public function lines()       { return $this->hasMany(StockInLine::class); }
    public function postedByUser() { return $this->belongsTo(User::class, 'posted_by'); }
    public function createdByUser() { return $this->belongsTo(User::class, 'created_by'); }

    public function isDraft(): bool     { return $this->status === self::STATUS_DRAFT; }
    public function isPosted(): bool    { return $this->status === self::STATUS_POSTED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }
}
