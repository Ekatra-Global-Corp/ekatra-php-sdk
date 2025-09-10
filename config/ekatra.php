<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ekatra Product SDK Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the Ekatra Product SDK.
    | You can customize these settings based on your requirements.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency to use when none is specified.
    |
    */
    'default_currency' => 'INR',

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | List of supported currencies for validation.
    |
    */
    'supported_currencies' => ['INR', 'USD', 'EUR', 'GBP'],

    /*
    |--------------------------------------------------------------------------
    | Validation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for data validation.
    |
    */
    'validation' => [
        'strict_mode' => false, // If true, throws exceptions on validation failures
        'log_errors' => true,   // If true, logs validation errors
        'log_channel' => 'default', // Log channel to use
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Mapping
    |--------------------------------------------------------------------------
    |
    | Custom field mappings for customer data transformation.
    | You can add your own field mappings here.
    |
    */
    'field_mappings' => [
        'product' => [
            'productId' => ['id', 'sku', 'product_id', 'item_id'],
            'title' => ['name', 'title', 'product_name', 'item_name'],
            'description' => ['desc', 'description', 'details', 'summary'],
            'existingUrl' => ['url', 'productUrl', 'product_url', 'link'],
            'keywords' => ['keywords', 'searchKeywords', 'tags', 'search_terms'],
        ],
        'variant' => [
            'name' => ['name', 'title', 'variant_name', 'item_name'],
            'color' => ['color', 'colour', 'variant_color', 'item_color'],
            'size' => ['size', 'variant_size', 'item_size'],
            'quantity' => ['quantity', 'stock', 'available', 'inventory'],
            'mrp' => ['mrp', 'originalPrice', 'listPrice', 'price_original'],
            'sellingPrice' => ['price', 'salePrice', 'current_price', 'sale_price'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SDK logging.
    |
    */
    'logging' => [
        'enabled' => true,
        'channel' => 'default',
        'level' => 'info',
        'log_mapping' => true,    // Log field mapping operations
        'log_validation' => true, // Log validation results
        'log_errors' => true,     // Log errors and exceptions
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for API-related features (future use).
    |
    */
    'api' => [
        'timeout' => 30,
        'retry_attempts' => 3,
        'retry_delay' => 1000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for testing features.
    |
    */
    'testing' => [
        'enabled' => env('EKATRA_TESTING_ENABLED', true),
        'routes_enabled' => env('EKATRA_TEST_ROUTES_ENABLED', true),
        'sample_data_path' => null, // Path to sample data file
    ],
];
