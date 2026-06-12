<?php

return [

    'gateway' => env('PAYMENT_GATEWAY', 'null'),

    'methods' => [
        'stripe' => [
            'enabled' => env('PAYMENT_STRIPE_ENABLED', false),
            'public_key' => env('STRIPE_PUBLIC_KEY'),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'currency' => env('PAYMENT_STRIPE_CURRENCY', 'usd'),
        ],
        'transfer' => [
            'enabled' => env('PAYMENT_TRANSFER_ENABLED', true),
            'clabe' => env('PAYMENT_TRANSFER_CLABE'),
            'bank' => env('PAYMENT_TRANSFER_BANK'),
            'reference_prefix' => env('PAYMENT_TRANSFER_REFERENCE_PREFIX', 'PCB-'),
        ],
        'credit' => [
            'enabled' => env('PAYMENT_CREDIT_ENABLED', false),
            'requires_approval' => true,
        ],
    ],

    'auto_complete' => env('PAYMENT_AUTO_COMPLETE', true),

    'tax_rate' => env('PAYMENT_TAX_RATE', 0.16),

    'shipping' => [
        'flat' => env('PAYMENT_SHIPPING_FLAT', 99.0),
        'free_threshold' => env('PAYMENT_SHIPPING_FREE_THRESHOLD', 500.0),
        'currency' => env('PAYMENT_SHIPPING_CURRENCY', 'MXN'),
    ],
];
