<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'event_date',
        'start_time',
        'end_time',
        'location',
        'type',
        'target_audience',
        'organizer',
        'max_participants',
        'registration_required',
        'registration_deadline',
        'status',
    ];

    protected $casts = [
        'event_date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'max_participants' => 'integer',
        'registration_required' => 'boolean',
        'registration_deadline' => 'datetime',
    ];

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now())
                     ->where('status', 'active')
                     ->orderBy('event_date');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
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
        return $this->event_date->isPast();
    }

    public function isUpcoming(): bool
    {
        return $this->event_date->isFuture();
    }

    public function registrationOpen(): bool
    {
        if (!$this->registration_required) {
            return false;
        }

        if ($this->registration_deadline) {
            return $this->registration_deadline->isFuture();
        }

        return $this->event_date->isFuture();
    }
}
