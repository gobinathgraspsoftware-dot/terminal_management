<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'browser',
        'platform',
        'device',
        'login_status',
        'location',
        'login_at',
        'logout_at',
        'session_duration',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'session_duration' => 'integer',
    ];

    /**
     * Get the user that owns the login history.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include successful logins.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('login_status', 'success');
    }

    /**
     * Scope a query to only include failed logins.
     */
    public function scopeFailed($query)
    {
        return $query->where('login_status', 'failed');
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('login_at', [$startDate, $endDate]);
    }

    /**
     * Get formatted session duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->session_duration) {
            return 'Active';
        }

        $hours = floor($this->session_duration / 60);
        $minutes = $this->session_duration % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }

    /**
     * Get login status badge color.
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->login_status === 'success' ? 'success' : 'danger';
    }

    /**
     * Record a login attempt.
     */
    public static function recordLogin(
        int $userId,
        string $ipAddress,
        ?string $userAgent = null,
        string $status = 'success'
    ): self {
        $deviceInfo = self::parseUserAgent($userAgent);

        return self::create([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'browser' => $deviceInfo['browser'],
            'platform' => $deviceInfo['platform'],
            'device' => $deviceInfo['device'],
            'login_status' => $status,
            'login_at' => now(),
        ]);
    }

    /**
     * Parse user agent string.
     */
    private static function parseUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return [
                'browser' => 'Unknown',
                'platform' => 'Unknown',
                'device' => 'Unknown',
            ];
        }

        // Simple user agent parsing
        $browser = 'Unknown';
        $platform = 'Unknown';
        $device = 'Desktop';

        // Detect browser
        if (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        }

        // Detect platform
        if (preg_match('/Windows/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/Mac OS/i', $userAgent)) {
            $platform = 'Mac OS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $platform = 'Android';
            $device = 'Mobile';
        } elseif (preg_match('/iOS|iPhone|iPad/i', $userAgent)) {
            $platform = 'iOS';
            $device = 'Mobile';
        }

        return [
            'browser' => $browser,
            'platform' => $platform,
            'device' => $device,
        ];
    }

    /**
     * Update logout time and calculate session duration.
     */
    public function recordLogout(): void
    {
        $logoutTime = now();
        $duration = $this->login_at->diffInMinutes($logoutTime);

        $this->update([
            'logout_at' => $logoutTime,
            'session_duration' => $duration,
        ]);
    }
}
