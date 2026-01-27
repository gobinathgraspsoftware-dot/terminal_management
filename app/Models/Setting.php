<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
    ];

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('key', $key);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Get setting value by key
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set setting value
     */
    public static function setValue(string $key, $value, string $group = 'general', string $type = 'string')
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $group,
                'type' => $type,
            ]
        );
    }

    /**
     * Cast value based on type
     */
    protected static function castValue($value, string $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
            case 'decimal':
                return (float) $value;
            case 'array':
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        return static::where('group', $group)
                    ->pluck('value', 'key')
                    ->toArray();
    }
}
