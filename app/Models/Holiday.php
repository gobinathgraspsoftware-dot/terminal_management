<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'date',
        'type',
        'is_recurring',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
    ];

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now())
                     ->where('status', 'active')
                     ->orderBy('date');
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isPast(): bool
    {
        return $this->date->isPast();
    }

    public function isUpcoming(): bool
    {
        return $this->date->isFuture();
    }

    public function isToday(): bool
    {
        return $this->date->isToday();
    }
}
