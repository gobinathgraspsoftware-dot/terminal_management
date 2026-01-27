<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Country Codes Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains country codes for phone number handling in the system.
    | You can add or remove countries as needed.
    |
    */

    'countries' => [
        [
            'code' => '+60',
            'name' => 'Malaysia',
            'flag' => '🇲🇾',
            'format' => '(###) #### ####', // Example: (012) 3456 7890
            'min_length' => 9,
            'max_length' => 10,
        ],
        [
            'code' => '+65',
            'name' => 'Singapore',
            'flag' => '🇸🇬',
            'format' => '#### ####', // Example: 6123 4567
            'min_length' => 8,
            'max_length' => 8,
        ],
        [
            'code' => '+62',
            'name' => 'Indonesia',
            'flag' => '🇮🇩',
            'format' => '### #### ####', // Example: 812 3456 7890
            'min_length' => 10,
            'max_length' => 12,
        ],
        [
            'code' => '+66',
            'name' => 'Thailand',
            'flag' => '🇹🇭',
            'format' => '## #### ####', // Example: 81 2345 6789
            'min_length' => 9,
            'max_length' => 9,
        ],
        [
            'code' => '+63',
            'name' => 'Philippines',
            'flag' => '🇵🇭',
            'format' => '### #### ####', // Example: 912 3456 7890
            'min_length' => 10,
            'max_length' => 10,
        ],
        [
            'code' => '+84',
            'name' => 'Vietnam',
            'flag' => '🇻🇳',
            'format' => '### #### ####', // Example: 912 3456 7890
            'min_length' => 9,
            'max_length' => 10,
        ],
        [
            'code' => '+95',
            'name' => 'Myanmar',
            'flag' => '🇲🇲',
            'format' => '### #### ####', // Example: 912 3456 7890
            'min_length' => 9,
            'max_length' => 10,
        ],
        [
            'code' => '+91',
            'name' => 'India',
            'flag' => '🇮🇳',
            'format' => '##### #####', // Example: 98765 43210
            'min_length' => 10,
            'max_length' => 10,
        ],
        [
            'code' => '+86',
            'name' => 'China',
            'flag' => '🇨🇳',
            'format' => '### #### ####', // Example: 138 1234 5678
            'min_length' => 11,
            'max_length' => 11,
        ],
        [
            'code' => '+44',
            'name' => 'United Kingdom',
            'flag' => '🇬🇧',
            'format' => '#### ######', // Example: 7700 900123
            'min_length' => 10,
            'max_length' => 10,
        ],
        [
            'code' => '+1',
            'name' => 'United States',
            'flag' => '🇺🇸',
            'format' => '(###) ### ####', // Example: (555) 123 4567
            'min_length' => 10,
            'max_length' => 10,
        ],
        [
            'code' => '+61',
            'name' => 'Australia',
            'flag' => '🇦🇺',
            'format' => '### ### ###', // Example: 412 345 678
            'min_length' => 9,
            'max_length' => 9,
        ],
        [
            'code' => '+64',
            'name' => 'New Zealand',
            'flag' => '🇳🇿',
            'format' => '### ### ####', // Example: 021 234 5678
            'min_length' => 9,
            'max_length' => 10,
        ],
        [
            'code' => '+81',
            'name' => 'Japan',
            'flag' => '🇯🇵',
            'format' => '## #### ####', // Example: 90 1234 5678
            'min_length' => 10,
            'max_length' => 10,
        ],
        [
            'code' => '+82',
            'name' => 'South Korea',
            'flag' => '🇰🇷',
            'format' => '## #### ####', // Example: 10 1234 5678
            'min_length' => 10,
            'max_length' => 11,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Country Code
    |--------------------------------------------------------------------------
    |
    | This is the default country code that will be used when creating new
    | records or when no country code is specified.
    |
    */

    'default' => '+60', // Malaysia

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Base URL
    |--------------------------------------------------------------------------
    |
    | Base URL for WhatsApp Web/API links
    |
    */

    'whatsapp_url' => 'https://wa.me/',

    /*
    |--------------------------------------------------------------------------
    | Phone Number Display Format
    |--------------------------------------------------------------------------
    |
    | How to display phone numbers in the UI
    | Options: 'full' (with country code), 'local' (without country code)
    |
    */

    'display_format' => 'full',
];
