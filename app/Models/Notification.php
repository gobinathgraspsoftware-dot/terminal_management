<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Notification Model
 * 
 * Handles database notifications for the TMS system.
 * Used for job assignments, approval requests, system alerts, etc.
 *
 * @property string $id UUID primary key
 * @property string $type Notification class type
 * @property string $notifiable_type Polymorphic type (usually App\Models\User)
 * @property int $notifiable_id Polymorphic ID
 * @property array $data JSON notification data
 * @property \Carbon\Carbon|null $read_at When notification was read
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Notification extends Model
{
    /**
     * Indicates if the IDs are auto-incrementing.
     * Notifications use UUIDs.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the notifiable entity (User, etc.)
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to get unread notifications
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope to get read notifications
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope to filter by notification type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get notifications for a specific user
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('notifiable_type', 'App\\Models\\User')
                     ->where('notifiable_id', $userId);
    }

    /**
     * Scope to get recent notifications (last N days)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get the notification title from data
     *
     * @return string|null
     */
    public function getTitleAttribute(): ?string
    {
        return $this->data['title'] ?? null;
    }

    /**
     * Get the notification message from data
     *
     * @return string|null
     */
    public function getMessageAttribute(): ?string
    {
        return $this->data['message'] ?? null;
    }

    /**
     * Get the notification action URL from data
     *
     * @return string|null
     */
    public function getActionUrlAttribute(): ?string
    {
        return $this->data['action_url'] ?? null;
    }

    /**
     * Get the notification icon from data
     *
     * @return string
     */
    public function getIconAttribute(): string
    {
        return $this->data['icon'] ?? 'bi-bell';
    }

    /**
     * Get the notification priority from data
     *
     * @return string
     */
    public function getPriorityAttribute(): string
    {
        return $this->data['priority'] ?? 'normal';
    }

    /**
     * Check if notification is read
     *
     * @return bool
     */
    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Get short notification type name
     *
     * @return string
     */
    public function getTypeNameAttribute(): string
    {
        $parts = explode('\\', $this->type);
        return end($parts);
    }

    /**
     * Get time ago string
     *
     * @return string
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Mark notification as read
     *
     * @return bool
     */
    public function markAsRead(): bool
    {
        if ($this->read_at === null) {
            return $this->update(['read_at' => now()]);
        }
        return true;
    }

    /**
     * Mark notification as unread
     *
     * @return bool
     */
    public function markAsUnread(): bool
    {
        return $this->update(['read_at' => null]);
    }

    /**
     * Get CSS class based on notification type/priority
     *
     * @return string
     */
    public function getCssClass(): string
    {
        $priority = $this->priority;
        
        return match($priority) {
            'high', 'urgent' => 'border-danger',
            'medium' => 'border-warning',
            default => 'border-info',
        };
    }

    /**
     * Get badge class based on notification type
     *
     * @return string
     */
    public function getBadgeClass(): string
    {
        $typeName = strtolower($this->type_name);
        
        if (str_contains($typeName, 'job')) {
            return 'bg-primary';
        }
        if (str_contains($typeName, 'approval')) {
            return 'bg-warning';
        }
        if (str_contains($typeName, 'payment') || str_contains($typeName, 'invoice')) {
            return 'bg-success';
        }
        if (str_contains($typeName, 'alert') || str_contains($typeName, 'warning')) {
            return 'bg-danger';
        }
        
        return 'bg-secondary';
    }

    // =========================================================================
    // STATIC METHODS
    // =========================================================================

    /**
     * Get unread count for a user
     *
     * @param int $userId
     * @return int
     */
    public static function unreadCountForUser(int $userId): int
    {
        return static::forUser($userId)->unread()->count();
    }

    /**
     * Mark all notifications as read for a user
     *
     * @param int $userId
     * @return int Number of notifications marked as read
     */
    public static function markAllAsReadForUser(int $userId): int
    {
        return static::forUser($userId)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * Delete old read notifications (cleanup)
     *
     * @param int $daysOld
     * @return int Number of notifications deleted
     */
    public static function deleteOldReadNotifications(int $daysOld = 90): int
    {
        return static::read()
            ->where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    /**
     * Get notifications grouped by date for a user
     *
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function getGroupedByDateForUser(int $userId, int $limit = 50)
    {
        return static::forUser($userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->groupBy(function ($notification) {
                return $notification->created_at->format('Y-m-d');
            });
    }
}
