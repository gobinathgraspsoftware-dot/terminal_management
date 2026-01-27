<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_log';

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'batch_uuid',
        'event',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function subject()
    {
        return $this->morphTo();
    }

    public function causer()
    {
        return $this->morphTo();
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeForEvent($query, $event)
    {
        return $query->where('event', $event);
    }

    public function scopeForSubject($query, $subject)
    {
        return $query->where('subject_type', get_class($subject))
                     ->where('subject_id', $subject->id);
    }

    public function scopeCausedBy($query, $causer)
    {
        return $query->where('causer_type', get_class($causer))
                     ->where('causer_id', $causer->id);
    }

    public function scopeInLog($query, $logName)
    {
        return $query->where('log_name', $logName);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
