#!/usr/bin/env php
<?php

/**
 * Local Architecture Test Script
 * 
 * Run this script to validate the SDK architecture locally:
 * php test_architecture.php
 */

require 'vendor/autoload.php';

use Ekatra\Product\EkatraSDK;
use Ekatra\Product\Core\EkatraProduct;
use Ekatra\Product\Core\EkatraVariant;
use Ekatra\Product\Transformers\FlexibleSmartTransformer;

echo "=== EKATRA SDK ARCHITECTURE VALIDATION ===\n\n";

/**
 * Get expected version from composer.json
 */
function getExpectedVersion(): string
{
    $composerPath = __DIR__ . '/../composer.json';
    if (file_exists($composerPath)) {
        $composer = json_decode(file_get_contents($composerPath), true);
        return $composer['version'] ?? 'unknown';
    }
    return 'unknown';
}

$testData = [
    'product_id' => '20051',
    'title' => 'Test Product',
    'description' => 'Test Description',
    'currency' => 'INR',
    'variant_quantity' => 5,
    'variant_mrp' => 1000,
    'variant_selling_price' => 800,
    'image_urls' => 'https://example.com/image.jpg',
    'discount' => '20% OFF on DIA'  // Test discountLabel functionality
];

$tests = [
    'EkatraSDK::transformProduct' => function() use ($testData) { return EkatraSDK::transformProduct($testData); },
    'EkatraSDK::transformVariant' => function() use ($testData) { return EkatraSDK::transformVariant($testData); },
    'EkatraSDK::smartTransformProduct' => function() use ($testData) { return EkatraSDK::smartTransformProduct($testData); },
    'EkatraSDK::smartTransformProductFlexible' => function() use ($testData) { return EkatraSDK::smartTransformProductFlexible($testData); },
    'EkatraProduct::toEkatraFormatWithValidation' => function() use ($testData) { 
        $product = EkatraProduct::fromCustomerData($testData);
        return $product->toEkatraFormatWithValidation();
    },
    'EkatraVariant::toEkatraFormatWithValidation' => function() use ($testData) { 
        $variant = EkatraVariant::fromCustomerData($testData);
        return $variant->toEkatraFormatWithValidation();
    },
    'FlexibleSmartTransformer::transformToEkatra' => function() use ($testData) { 
        $transformer = new FlexibleSmartTransformer();
        return $transformer->transformToEkatra($testData);
    }
];

$allPassed = true;
$results = [];

echo "Testing all SDK methods...\n";
foreach ($tests as $testName => $testFunction) {
    try {
        $result = $testFunction();
        $checks = [
            'Has status' => isset($result['status']),
            'Has metadata' => isset($result['metadata']),
            'Has message' => isset($result['message']),
            'Has SDK version' => isset($result['metadata']['sdkVersion']),
            'SDK version matches composer.json' => $result['metadata']['sdkVersion'] === getExpectedVersion()
        ];
        
        // Additional checks for FlexibleSmartTransformer
        if ($testName === 'FlexibleSmartTransformer::transformToEkatra') {
            $additionalChecks = [
                'Has data' => isset($result['data']),
                'Has offers field' => isset($result['data']['offers']),
                'Has handle field' => isset($result['data']['handle']),
                'Has countryCode field' => array_key_exists('countryCode', $result['data']),
                'Has variants' => isset($result['data']['variants']),
                'Has mediaList in variant' => isset($result['data']['variants'][0]['mediaList']),
                'MediaList has mediaType' => isset($result['data']['variants'][0]['mediaList'][0]['mediaType']),
                'MediaList has playUrl' => isset($result['data']['variants'][0]['mediaList'][0]['playUrl']),
                'MediaList has thumbnailUrl' => isset($result['data']['variants'][0]['mediaList'][0]['thumbnailUrl']),
                'Variant has id' => isset($result['data']['variants'][0]['id']),
                'Has variations' => isset($result['data']['variants'][0]['variations']),
                'Variation has discount field' => isset($result['data']['variants'][0]['variations'][0]['discount']),
                'Variation has discountLabel field' => array_key_exists('discountLabel', $result['data']['variants'][0]['variations'][0])
            ];
            $checks = array_merge($checks, $additionalChecks);
        }
        
        // Additional discountLabel functionality test for FlexibleSmartTransformer
        if ($testName === 'FlexibleSmartTransformer::transformToEkatra') {
            $discountChecks = [
                'Discount is calculated correctly' => $result['data']['variants'][0]['variations'][0]['discount'] === 20.0,
                'DiscountLabel is set correctly' => $result['data']['variants'][0]['variations'][0]['discountLabel'] === '20% OFF on DIA',
                'Discount is float type' => is_float($result['data']['variants'][0]['variations'][0]['discount']),
                'DiscountLabel is string type' => is_string($result['data']['variants'][0]['variations'][0]['discountLabel'])
            ];
            $checks = array_merge($checks, $discountChecks);
        }
        
        $passed = !in_array(false, $checks);
        $results[$testName] = $passed;
        
        if (!$passed) {
            $allPassed = false;
            echo "❌ $testName FAILED\n";
            $failedChecks = array_keys(array_filter($checks, function($v) { return !$v; }));
            echo "   Failed checks: " . implode(', ', $failedChecks) . "\n";
        } else {
            echo "✅ $testName PASSED\n";
        }
    } catch (\Exception $e) {
        $allPassed = false;
        echo "❌ $testName EXCEPTION: " . $e->getMessage() . "\n";
    }
}

echo "\n=== CONSISTENCY CHECK ===\n";
$sdkVersions = [];
foreach ($tests as $testName => $testFunction) {
    try {
        $result = $testFunction();
        $sdkVersions[] = $result['metadata']['sdkVersion'] ?? 'missing';
    } catch (\Exception $e) {
        $sdkVersions[] = 'error';
    }
}

$uniqueVersions = array_unique($sdkVersions);
echo "SDK Versions found: " . implode(', ', $uniqueVersions) . "\n";
echo "All versions same: " . (count($uniqueVersions) === 1 ? 'Yes' : 'No') . "\n";

if (count($uniqueVersions) !== 1) {
    $allPassed = false;
}

echo "\n=== ERROR HANDLING TEST ===\n";
$errorTests = [
    'null input' => null,
    'string input' => 'invalid',
    'integer input' => 123,
    'empty array' => []
];

foreach ($errorTests as $testName => $testValue) {
    try {
        $result = EkatraSDK::smartTransformProductFlexible($testValue);
        $checks = [
            'Status is error' => $result['status'] === 'error',
            'Data is null' => $result['data'] === null,
            'Has metadata' => isset($result['metadata']),
            'Has SDK version' => isset($result['metadata']['sdkVersion'])
        ];
        
        $passed = !in_array(false, $checks);
        if (!$passed) {
            $allPassed = false;
            echo "❌ Error test $testName FAILED\n";
        } else {
            echo "✅ Error test $testName PASSED\n";
        }
    } catch (\Exception $e) {
        $allPassed = false;
        echo "❌ Error test $testName EXCEPTION: " . $e->getMessage() . "\n";
    }
}

echo "\n=== FINAL RESULT ===\n";
if ($allPassed) {
    echo "🎉 ALL ARCHITECTURE TESTS PASSED!\n";
    echo "✅ Single ResponseBuilder pattern working\n";
    echo "✅ Consistent response structure across all methods\n";
    echo "✅ Proper error handling for all edge cases\n";
    echo "✅ SDK version auto-detection working\n";
    echo "✅ Production-ready!\n";
    exit(0);
} else {
    echo "❌ SOME ARCHITECTURE TESTS FAILED!\n";
    echo "Please review the failed tests above.\n";
    exit(1);
}
