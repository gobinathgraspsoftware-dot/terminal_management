<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Reminder Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all configuration options for the payment reminder
    | system including scheduling, retry logic, and notification channels.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Reminder Scheduling
    |--------------------------------------------------------------------------
    |
    | Configure the days of month when reminders should be sent.
    | Default: 10th (first), 18th (second), 24th (final)
    |
    */
    'reminder_days' => [
        'first' => env('REMINDER_DAY_FIRST', 10),
        'second' => env('REMINDER_DAY_SECOND', 18),
        'final' => env('REMINDER_DAY_FINAL', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Channel
    |--------------------------------------------------------------------------
    |
    | The default notification channel for sending reminders.
    | Options: 'whatsapp', 'email', 'sms'
    |
    */
    'default_channel' => env('REMINDER_DEFAULT_CHANNEL', 'whatsapp'),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for retrying failed reminder deliveries.
    |
    */
    'max_retry_attempts' => env('REMINDER_MAX_RETRY', 3),
    'retry_delay_hours' => env('REMINDER_RETRY_DELAY', 2),
    'retry_backoff_multiplier' => 1.5, // Each retry waits longer

    /*
    |--------------------------------------------------------------------------
    | Overdue Reminders
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic overdue reminders.
    |
    */
    'auto_overdue_enabled' => env('AUTO_OVERDUE_REMINDER', true),
    'overdue_reminder_interval' => env('OVERDUE_REMINDER_INTERVAL', 7), // Days between overdue reminders
    'max_overdue_reminders' => env('MAX_OVERDUE_REMINDERS', 5), // Max overdue reminders per invoice

    /*
    |--------------------------------------------------------------------------
    | Grace Period
    |--------------------------------------------------------------------------
    |
    | Number of days after due date before marking as overdue.
    |
    */
    'grace_period_days' => env('PAYMENT_GRACE_PERIOD', 3),

    /*
    |--------------------------------------------------------------------------
    | Late Fee Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for late payment fees.
    |
    */
    'late_fee' => [
        'enabled' => env('LATE_FEE_ENABLED', false),
        'type' => env('LATE_FEE_TYPE', 'fixed'), // 'fixed' or 'percentage'
        'amount' => env('LATE_FEE_AMOUNT', 10.00), // Fixed amount or percentage
        'max_fee' => env('LATE_FEE_MAX', 50.00), // Maximum late fee
        'apply_after_days' => env('LATE_FEE_AFTER_DAYS', 7), // Days overdue before applying
    ],

    /*
    |--------------------------------------------------------------------------
    | Installment Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for installment plans.
    |
    */
    'installment' => [
        'min_installments' => 2,
        'max_installments' => 12,
        'default_interval_days' => 30,
        'allow_custom_amounts' => true,
        'auto_reminder' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Templates
    |--------------------------------------------------------------------------
    |
    | Template keys for different reminder types.
    |
    */
    'templates' => [
        'first_reminder' => 'payment_reminder_first',
        'second_reminder' => 'payment_reminder_second',
        'final_reminder' => 'payment_reminder_final',
        'overdue_notice' => 'payment_overdue_notice',
        'installment_reminder' => 'installment_payment_due',
        'follow_up' => 'payment_follow_up',
    ],

    /*
    |--------------------------------------------------------------------------
    | Arrears Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for arrears tracking and reporting.
    |
    */
    'arrears' => [
        // Age buckets for aging analysis (in days)
        'age_buckets' => [
            '0-30' => [0, 30],
            '31-60' => [31, 60],
            '61-90' => [61, 90],
            '90+' => [91, 9999],
        ],

        // Critical threshold - flag students with arrears exceeding this
        'critical_threshold' => env('ARREARS_CRITICAL_THRESHOLD', 500.00),

        // Days overdue to be considered critical
        'critical_days' => env('ARREARS_CRITICAL_DAYS', 60),

        // Auto-flag for follow-up after X days overdue
        'auto_flag_after_days' => env('ARREARS_AUTO_FLAG_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduler Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for automated scheduler tasks.
    |
    */
    'scheduler' => [
        // Time to run daily reminder check (24-hour format)
        'daily_check_time' => env('REMINDER_CHECK_TIME', '08:00'),

        // Time to send reminders on scheduled days
        'send_time' => env('REMINDER_SEND_TIME', '09:00'),

        // Batch size for processing reminders
        'batch_size' => env('REMINDER_BATCH_SIZE', 50),

        // Enable/disable scheduler
        'enabled' => env('REMINDER_SCHEDULER_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Priority
    |--------------------------------------------------------------------------
    |
    | Priority levels for different reminder types.
    | Higher number = higher priority
    |
    */
    'priority' => [
        'first' => 1,
        'second' => 2,
        'final' => 3,
        'overdue' => 4,
        'installment' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Working Days
    |--------------------------------------------------------------------------
    |
    | Days when reminders can be sent (0 = Sunday, 6 = Saturday).
    |
    */
    'working_days' => [1, 2, 3, 4, 5], // Monday to Friday

    /*
    |--------------------------------------------------------------------------
    | Quiet Hours
    |--------------------------------------------------------------------------
    |
    | Time range when reminders should not be sent.
    |
    */
    'quiet_hours' => [
        'start' => '21:00',
        'end' => '08:00',
    ],
];
