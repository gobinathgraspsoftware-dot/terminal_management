<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_JSON = 'json';
    const TYPE_TEXT = 'text';

    protected $fillable = [
        'setting_group', 'setting_key', 'setting_value', 'setting_type',
        'description', 'is_public',
    ];

    protected function casts(): array { return ['is_public' => 'boolean']; }

    public function getValueAttribute()
    {
        return match($this->setting_type) {
            self::TYPE_INTEGER => (int) $this->setting_value,
            self::TYPE_DECIMAL => (float) $this->setting_value,
            self::TYPE_BOOLEAN => (bool) $this->setting_value,
            self::TYPE_JSON => json_decode($this->setting_value, true),
            default => $this->setting_value,
        };
    }

    public static function getValue($group, $key, $default = null)
    {
        $setting = static::where('setting_group', $group)
            ->where('setting_key', $key)
            ->first();
        return $setting ? $setting->value : $default;
    }
}
