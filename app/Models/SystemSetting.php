<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * SystemSetting Model
 * 
 * Manages system-wide configuration settings
 * Supports caching for performance
 */
class SystemSetting extends Model
{
    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
        'description',
    ];

    /**
     * Get a setting value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        // Try to get from cache first
        $cacheKey = 'system_setting_' . $key;
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = self::where('setting_key', $key)->first();
            
            if (!$setting) {
                return $default;
            }
            
            return $setting->setting_value ?? $default;
        });
    }

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @return bool
     */
    public static function set(string $key, $value, string $type = 'text'): bool
    {
        $setting = self::updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => $value,
                'setting_type' => $type,
            ]
        );

        // Clear cache
        Cache::forget('system_setting_' . $key);

        return $setting ? true : false;
    }

    /**
     * Get multiple settings at once
     *
     * @param array $keys
     * @return array
     */
    public static function getMultiple(array $keys): array
    {
        $settings = [];
        
        foreach ($keys as $key) {
            $settings[$key] = self::get($key);
        }
        
        return $settings;
    }

    /**
     * Upload and set company logo
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string|false
     */
    public static function uploadCompanyLogo($file)
    {
        try {
            // Delete old logo if exists
            $oldLogo = self::get('company_logo_path');
            if ($oldLogo && Storage::exists($oldLogo)) {
                Storage::delete($oldLogo);
            }
            
            // Store new logo
            $path = $file->store('company', 'public');
            
            // Update setting
            self::set('company_logo_path', $path, 'file');
            
            return $path;
        } catch (\Exception $e) {
            \Log::error('Error uploading company logo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get company logo URL
     *
     * @return string|null
     */
    public static function getCompanyLogoUrl(): ?string
    {
        $logoPath = self::get('company_logo_path');
        
        if (!$logoPath) {
            return null;
        }
        
        return Storage::url($logoPath);
    }

    /**
     * Delete company logo
     *
     * @return bool
     */
    public static function deleteCompanyLogo(): bool
    {
        try {
            $logoPath = self::get('company_logo_path');
            
            if ($logoPath && Storage::exists($logoPath)) {
                Storage::delete($logoPath);
            }
            
            // Clear setting
            self::set('company_logo_path', null, 'file');
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Error deleting company logo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all company settings for documents
     *
     * @return array
     */
    public static function getCompanySettings(): array
    {
        $keys = [
            'company_name',
            'company_registration_no',
            'company_tax_id',
            'company_address',
            'company_phone',
            'company_email',
            'company_website',
            'company_logo_path',
            'company_bank_name',
            'company_bank_account_no',
            'company_bank_account_name',
        ];
        
        return self::getMultiple($keys);
    }

    // getQuotationSettings() removed — Quotation module removed

    /**
     * Clear all settings cache
     *
     * @return void
     */
    public static function clearCache(): void
    {
        $settings = self::all();
        
        foreach ($settings as $setting) {
            Cache::forget('system_setting_' . $setting->setting_key);
        }
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache on update
        static::updated(function ($setting) {
            Cache::forget('system_setting_' . $setting->setting_key);
        });

        // Clear cache on delete
        static::deleted(function ($setting) {
            Cache::forget('system_setting_' . $setting->setting_key);
        });
    }
}
