<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seminar extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'date',
        'start_time',
        'end_time',
        'venue',
        'is_online',
        'online_platform',
        'meeting_link',
        'capacity',
        'current_participants',
        'fee',
        'instructor',
        'target_audience',
        'registration_deadline',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'is_online' => 'boolean',
        'capacity' => 'integer',
        'current_participants' => 'integer',
        'fee' => 'decimal:2',
        'registration_deadline' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function participants()
    {
        return $this->hasMany(SeminarParticipant::class);
    }

    public function expenses()
    {
        return $this->hasMany(SeminarExpense::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', today())
                     ->where('status', 'active')
                     ->orderBy('date');
    }

    public function scopePast($query)
    {
        return $query->where('date', '<', today());
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeOffline($query)
    {
        return $query->where('is_online', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isFull(): bool
    {
        return $this->current_participants >= $this->capacity;
    }

    public function hasAvailableSlots(): bool
    {
        return $this->current_participants < $this->capacity;
    }

    public function getAvailableSlotsAttribute(): int
    {
        return max(0, $this->capacity - $this->current_participants);
    }

    public function isRegistrationOpen(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->isFull()) {
            return false;
        }

        if ($this->registration_deadline && $this->registration_deadline->isPast()) {
            return false;
        }

        return true;
    }

    public function getTotalExpensesAttribute(): float
    {
        return $this->expenses()->sum('amount');
    }

    public function getTotalRevenueAttribute(): float
    {
        return $this->participants()->where('payment_status', 'paid')->sum('fee_amount');
    }

    public function getProfitAttribute(): float
    {
        return $this->total_revenue - $this->total_expenses;
    }
}
