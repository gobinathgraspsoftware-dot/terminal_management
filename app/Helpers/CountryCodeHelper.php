<?php

namespace App\Helpers;

class CountryCodeHelper
{
    /**
     * Get all available country codes
     *
     * @return array
     */
    public static function getAllCountries()
    {
        return config('country_codes.countries', []);
    }

    /**
     * Get default country code
     *
     * @return string
     */
    public static function getDefaultCountryCode()
    {
        return config('country_codes.default', '+60');
    }

    /**
     * Extract country code from phone number
     *
     * @param string|null $phoneNumber
     * @return array ['country_code' => string, 'number' => string]
     */
    public static function extractCountryCode($phoneNumber)
    {
        if (empty($phoneNumber)) {
            return [
                'country_code' => self::getDefaultCountryCode(),
                'number' => ''
            ];
        }

        // Get all country codes sorted by length (longest first)
        $countries = self::getAllCountries();
        $countryCodes = array_column($countries, 'code');
        
        // Sort by length descending to match longest first
        usort($countryCodes, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($countryCodes as $code) {
            if (strpos($phoneNumber, $code) === 0) {
                return [
                    'country_code' => $code,
                    'number' => substr($phoneNumber, strlen($code))
                ];
            }
        }

        // If no country code found, assume it's Malaysian without code
        if (strpos($phoneNumber, '0') === 0) {
            return [
                'country_code' => self::getDefaultCountryCode(),
                'number' => substr($phoneNumber, 1)
            ];
        }

        // Default
        return [
            'country_code' => self::getDefaultCountryCode(),
            'number' => $phoneNumber
        ];
    }

    /**
     * Format phone number with country code
     *
     * @param string $countryCode
     * @param string $phoneNumber
     * @return string
     */
    public static function formatPhoneNumber($countryCode, $phoneNumber)
    {
        // Remove any spaces or special characters
        $cleanNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        return $countryCode . $cleanNumber;
    }

    /**
     * Get country info by country code
     *
     * @param string $countryCode
     * @return array|null
     */
    public static function getCountryInfo($countryCode)
    {
        $countries = self::getAllCountries();
        
        foreach ($countries as $country) {
            if ($country['code'] === $countryCode) {
                return $country;
            }
        }
        
        return null;
    }

    /**
     * Format phone number for display with spaces
     *
     * @param string $phoneNumber Full phone number with country code
     * @return string Formatted phone number
     */
    public static function formatForDisplay($phoneNumber)
    {
        if (empty($phoneNumber)) {
            return '';
        }

        $extracted = self::extractCountryCode($phoneNumber);
        $countryCode = $extracted['country_code'];
        $number = $extracted['number'];
        
        $countryInfo = self::getCountryInfo($countryCode);
        
        if ($countryInfo && isset($countryInfo['format'])) {
            // Try to apply the format
            $format = $countryInfo['format'];
            $cleanNumber = preg_replace('/[^0-9]/', '', $number);
            
            $formatted = '';
            $numberIndex = 0;
            
            for ($i = 0; $i < strlen($format); $i++) {
                if ($format[$i] === '#' && $numberIndex < strlen($cleanNumber)) {
                    $formatted .= $cleanNumber[$numberIndex];
                    $numberIndex++;
                } elseif ($format[$i] !== '#') {
                    $formatted .= $format[$i];
                }
            }
            
            // Add remaining digits if any
            if ($numberIndex < strlen($cleanNumber)) {
                $formatted .= substr($cleanNumber, $numberIndex);
            }
            
            return $countryCode . ' ' . $formatted;
        }
        
        // Default format if no specific format is defined
        return $phoneNumber;
    }

    /**
     * Get WhatsApp link for a phone number
     *
     * @param string $phoneNumber Full phone number with country code
     * @param string|null $message Optional message
     * @return string WhatsApp URL
     */
    public static function getWhatsAppLink($phoneNumber, $message = null)
    {
        // Remove + from country code for WhatsApp
        $cleanNumber = str_replace('+', '', $phoneNumber);
        $cleanNumber = preg_replace('/[^0-9]/', '', $cleanNumber);
        
        $baseUrl = config('country_codes.whatsapp_url', 'https://wa.me/');
        $url = $baseUrl . $cleanNumber;
        
        if ($message) {
            $url .= '?text=' . urlencode($message);
        }
        
        return $url;
    }

    /**
     * Validate phone number length for country
     *
     * @param string $countryCode
     * @param string $phoneNumber
     * @return bool
     */
    public static function validatePhoneLength($countryCode, $phoneNumber)
    {
        $countryInfo = self::getCountryInfo($countryCode);
        
        if (!$countryInfo) {
            return true; // If country not found, allow it
        }
        
        $cleanNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        $length = strlen($cleanNumber);
        
        return $length >= $countryInfo['min_length'] && $length <= $countryInfo['max_length'];
    }

    /**
     * Get country name by country code
     *
     * @param string $countryCode
     * @return string
     */
    public static function getCountryName($countryCode)
    {
        $countryInfo = self::getCountryInfo($countryCode);
        return $countryInfo ? $countryInfo['name'] : 'Unknown';
    }

    /**
     * Get country flag emoji by country code
     *
     * @param string $countryCode
     * @return string
     */
    public static function getCountryFlag($countryCode)
    {
        $countryInfo = self::getCountryInfo($countryCode);
        return $countryInfo ? $countryInfo['flag'] : '🌍';
    }

    /**
     * Generate country code options for HTML select
     *
     * @param string|null $selectedCode
     * @return string HTML options
     */
    public static function generateSelectOptions($selectedCode = null)
    {
        $countries = self::getAllCountries();
        $html = '';
        
        foreach ($countries as $country) {
            $selected = ($country['code'] === $selectedCode) ? 'selected' : '';
            $html .= sprintf(
                '<option value="%s" %s>%s %s (%s)</option>',
                $country['code'],
                $selected,
                $country['flag'],
                $country['code'],
                $country['name']
            );
        }
        
        return $html;
    }
}
