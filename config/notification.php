<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Notification Channel
    |--------------------------------------------------------------------------
    | whatsapp, email, sms, all
    */
    'default_channel' => env('NOTIFICATION_DEFAULT_CHANNEL', 'whatsapp'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Configuration (Ultra Messenger)
    |--------------------------------------------------------------------------
    */
    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', true),
        'api_url' => env('WHATSAPP_API_URL', 'https://api.ultramsg.com'),
        'instance_id' => env('WHATSAPP_INSTANCE_ID', ''),
        'token' => env('WHATSAPP_TOKEN', ''),
        'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY', '60'), // Malaysia
        'max_attempts' => 3,
        'retry_delay_minutes' => 5,
        'rate_limit_per_minute' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    */
    'email' => [
        'enabled' => env('EMAIL_ENABLED', true),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@arenamatriks.edu.my'),
        'from_name' => env('MAIL_FROM_NAME', 'Arena Matriks Edu Group'),
        'max_attempts' => 3,
        'retry_delay_minutes' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Configuration
    |--------------------------------------------------------------------------
    */
    'sms' => [
        'enabled' => env('SMS_ENABLED', false),
        'provider' => env('SMS_PROVIDER', 'twilio'), // twilio, nexmo, custom
        'api_url' => env('SMS_API_URL', ''),
        'api_key' => env('SMS_API_KEY', ''),
        'api_secret' => env('SMS_API_SECRET', ''),
        'sender_id' => env('SMS_SENDER_ID', 'ArenaMatriks'),
        'default_country_code' => env('SMS_DEFAULT_COUNTRY', '60'),
        'max_attempts' => 3,
        'retry_delay_minutes' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Processing Settings
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'process_limit' => 50, // Max messages per batch
        'schedule_interval' => 1, // Minutes between processing
        'failed_retention_days' => 30,
        'success_retention_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Types
    |--------------------------------------------------------------------------
    */
    'types' => [
        'payment_reminder' => [
            'channels' => ['whatsapp', 'email'],
            'priority' => 'high',
        ],
        'welcome' => [
            'channels' => ['whatsapp', 'email'],
            'priority' => 'normal',
        ],
        'attendance' => [
            'channels' => ['whatsapp'],
            'priority' => 'high',
        ],
        'exam_result' => [
            'channels' => ['whatsapp', 'email'],
            'priority' => 'normal',
        ],
        'announcement' => [
            'channels' => ['whatsapp', 'email'],
            'priority' => 'normal',
        ],
        'trial_class' => [
            'channels' => ['whatsapp', 'email'],
            'priority' => 'high',
        ],
        'enrollment' => [
            'channels' => ['whatsapp', 'email'],
            'priority' => 'high',
        ],
        'password_reset' => [
            'channels' => ['email'],
            'priority' => 'urgent',
        ],
        'student_approval' => [
            'name' => 'Student Approved',
            'description' => 'Notification when student registration is approved',
            'channels' => ['whatsapp', 'email'],
            'priority' => 'high',
        ],

        'student_rejection' => [
            'name' => 'Student Rejected',
            'description' => 'Notification when student registration is rejected',
            'channels' => ['whatsapp', 'email'],
            'priority' => 'high',
        ],

        'student_welcome' => [
            'name' => 'Student Welcome',
            'description' => 'Welcome notification for newly approved students',
            'channels' => ['whatsapp', 'email'],
            'priority' => 'normal',
        ],

        'info_request' => [
            'name' => 'Information Request',
            'description' => 'Request for additional information from parent',
            'channels' => ['whatsapp', 'email'],
            'priority' => 'normal',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Variables
    |--------------------------------------------------------------------------
    */
    'variables' => [
        'student_name',
        'parent_name',
        'teacher_name',
        'class_name',
        'subject_name',
        'amount',
        'due_date',
        'invoice_number',
        'attendance_date',
        'attendance_status',
        'exam_name',
        'exam_date',
        'score',
        'grade',
        'trial_date',
        'trial_time',
        'center_name',
        'center_phone',
        'reset_link',
        'login_link',
    ],
];
