<?php

return [

    'base_url' => env('SYSCOM_BASE_URL', 'https://developers.syscom.mx'),
    'api_url' => env('SYSCOM_API_URL', 'https://developers.syscom.mx/api/v1'),
    'oauth_url' => env('SYSCOM_OAUTH_URL', 'https://developers.syscom.mx/oauth/token'),

    'client_id' => env('SYSCOM_CLIENT_ID'),
    'client_secret' => env('SYSCOM_CLIENT_SECRET'),

    'token' => [
        'cache_key' => 'syscom:oauth:access_token',
        'safety_margin_seconds' => 60,
    ],

    'rate_limit' => [
        'per_minute' => env('SYSCOM_RATE_LIMIT_PER_MINUTE', 60),
    ],

    'http' => [
        'timeout' => env('SYSCOM_HTTP_TIMEOUT', 15),
        'connect_timeout' => env('SYSCOM_HTTP_CONNECT_TIMEOUT', 5),
        'retry_times' => env('SYSCOM_HTTP_RETRY_TIMES', 3),
        'retry_sleep_ms' => env('SYSCOM_HTTP_RETRY_SLEEP_MS', 200),
    ],

    'cache' => [
        'products_ttl' => env('SYSCOM_CACHE_PRODUCTS_TTL', 600),
        'categories_ttl' => env('SYSCOM_CACHE_CATEGORIES_TTL', 86400),
        'product_ttl' => env('SYSCOM_CACHE_PRODUCT_TTL', 600),
        'brands_ttl' => env('SYSCOM_CACHE_BRANDS_TTL', 86400),
    ],

    'wishlist' => [
        'enabled' => env('SYSCOM_WISHLIST_ENABLED', true),
    ],

    'cart' => [
        'tax_rate' => env('CART_TAX_RATE', 0.16),
        'free_shipping_threshold' => env('CART_FREE_SHIPPING_THRESHOLD', 500.0),
        'flat_shipping' => env('CART_FLAT_SHIPPING', 99.0),
    ],

    'cart_checkout' => [
        'enabled' => env('SYSCOM_CART_CHECKOUT_ENABLED', true),
    ],
];
