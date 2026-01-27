<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ultra Messenger WhatsApp API
    |--------------------------------------------------------------------------
    */
    'ultramsg' => [
        'instance_id' => env('WHATSAPP_INSTANCE_ID'),
        'token' => env('WHATSAPP_TOKEN'),
        'api_url' => env('WHATSAPP_API_URL', 'https://api.ultramsg.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Gateway Configuration
    |--------------------------------------------------------------------------
    */
    'sms' => [
        'provider' => env('SMS_PROVIDER', 'twilio'),

        // Twilio Settings
        'twilio' => [
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from' => env('TWILIO_FROM'),
        ],

        // Nexmo/Vonage Settings
        'nexmo' => [
            'key' => env('NEXMO_KEY'),
            'secret' => env('NEXMO_SECRET'),
            'from' => env('NEXMO_FROM'),
        ],

        // Custom SMS Gateway
        'custom' => [
            'api_url' => env('SMS_API_URL'),
            'api_key' => env('SMS_API_KEY'),
            'api_secret' => env('SMS_API_SECRET'),
            'sender_id' => env('SMS_SENDER_ID', 'ArenaMatriks'),
        ],
    ],

];
