<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway that will be used when
    | processing online payments. You may set this to any of the gateways
    | defined in the "gateways" array below.
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'toyyibpay'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Currency
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default currency for all payment gateways.
    |
    */

    'currency' => env('PAYMENT_CURRENCY', 'MYR'),

    /*
    |--------------------------------------------------------------------------
    | Online Payment Fee
    |--------------------------------------------------------------------------
    |
    | This is the additional fee charged for online payments (RM130 as per requirements)
    |
    */

    'online_fee' => env('ONLINE_PAYMENT_FEE', 130.00),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the payment gateways supported by your
    | application. Supported gateways: toyyibpay, senangpay, billplz, eghl
    |
    */

    'gateways' => [

        'toyyibpay' => [
            'name' => 'ToyyibPay',
            'description' => 'Malaysian FPX & Card Payment Gateway',
            'class' => \App\Services\Gateways\ToyyibPayGateway::class,
            'sandbox_url' => 'https://dev.toyyibpay.com',
            'production_url' => 'https://toyyibpay.com',
            'supported_currencies' => ['MYR'],
            'supported_methods' => ['fpx', 'card'],
            'fee_percentage' => env('TOYYIBPAY_FEE_PERCENT', 1.5),
            'fee_fixed' => env('TOYYIBPAY_FEE_FIXED', 0.00),
        ],

        'senangpay' => [
            'name' => 'SenangPay',
            'description' => 'Easy Malaysian Payment Gateway',
            'class' => \App\Services\Gateways\SenangPayGateway::class,
            'sandbox_url' => 'https://sandbox.senangpay.my',
            'production_url' => 'https://app.senangpay.my',
            'supported_currencies' => ['MYR'],
            'supported_methods' => ['fpx', 'card'],
            'fee_percentage' => env('SENANGPAY_FEE_PERCENT', 2.0),
            'fee_fixed' => env('SENANGPAY_FEE_FIXED', 0.20),
        ],

        'billplz' => [
            'name' => 'Billplz',
            'description' => 'Simple Malaysian Payment Platform',
            'class' => \App\Services\Gateways\BillplzGateway::class,
            'sandbox_url' => 'https://www.billplz-sandbox.com/api/v3',
            'production_url' => 'https://www.billplz.com/api/v3',
            'supported_currencies' => ['MYR'],
            'supported_methods' => ['fpx'],
            'fee_percentage' => env('BILLPLZ_FEE_PERCENT', 1.0),
            'fee_fixed' => env('BILLPLZ_FEE_FIXED', 0.00),
        ],

        'eghl' => [
            'name' => 'eGHL',
            'description' => 'Multi-currency payment gateway for Asia Pacific',
            'class' => \App\Services\Gateways\EghlGateway::class,
            'sandbox_url' => 'https://test2pay.ghl.com/IPGSG/Payment.aspx',
            'production_url' => 'https://pay.ghl.com/IPGSG/Payment.aspx',
            'query_url_sandbox' => 'https://test2pay.ghl.com/IPGSG/Payment.aspx',
            'query_url_production' => 'https://pay.ghl.com/IPGSG/Payment.aspx',
            'supported_methods' => ['fpx', 'card', 'ewallet', 'unionpay', 'alipay'],
            'supported_currencies' => ['MYR', 'USD', 'SGD', 'THB', 'IDR', 'CNY'],
            'default_fee_percent' => env('EGHL_FEE_PERCENT', 2.5),
            'default_fee_fixed' => env('EGHL_FEE_FIXED', 0.00),

            // Field mapping for admin form - these are editable
            'config_fields' => [
                'merchant_id' => 'Merchant ID',
                'merchant_password' => 'Merchant Password (Service ID)',
                'merchant_registered_name' => 'Merchant Registered Name',
                'sandbox_url' => 'Sandbox URL (Development)',
                'production_url' => 'Production URL (Live)',
            ],
        ],

    ],

    // Default gateway settings
    'default_gateway' => env('DEFAULT_PAYMENT_GATEWAY', 'toyyibpay'),

    // Global settings
    'currency' => 'MYR',
    'currency_symbol' => 'RM',

    // Payment timeout (in minutes)
    'payment_timeout' => 60,

    // Callback/Webhook retry settings
    'callback_retry_attempts' => 3,
    'callback_retry_delay' => 5, // seconds

    /*
    |--------------------------------------------------------------------------
    | Callback URLs
    |--------------------------------------------------------------------------
    |
    | These URLs will be used for payment gateway callbacks
    |
    */

    'callback' => [
        'success' => env('PAYMENT_SUCCESS_URL', '/payment/success'),
        'failed' => env('PAYMENT_FAILED_URL', '/payment/failed'),
        'webhook' => env('PAYMENT_WEBHOOK_URL', '/api/payment/webhook'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Settings
    |--------------------------------------------------------------------------
    */

    'transaction' => [
        'prefix' => 'TXN',
        'expiry_minutes' => 30,
        'auto_cancel_pending' => true,
    ],

];
