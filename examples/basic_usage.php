<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Ekatra\Product\EkatraSDK;
use Ekatra\Product\Core\EkatraProduct;
use Ekatra\Product\Core\EkatraVariant;

echo "🚀 Ekatra Product SDK - Basic Usage Examples\n";
echo "==========================================\n\n";

// Example 1: Manual Product Creation
echo "1. Manual Product Creation\n";
echo "---------------------------\n";

$product = EkatraSDK::product()
    ->setBasicInfo('PROD001', 'Premium Silver Ring', 'Beautiful silver ring with premium finish', 'INR')
    ->setUrl('https://mystore.com/products/ring001')
    ->setKeywords(['rings', 'silver', 'jewelry', 'premium'])
    ->setAdditionalInfo(['Material' => 'Silver', 'Brand' => 'MyBrand'])
    ->setSpecifications([
        ['key' => 'Material', 'value' => 'Premium Silver Alloy'],
        ['key' => 'Weight', 'value' => '250g'],
        ['key' => 'Dimensions', 'value' => '15cm x 10cm x 5cm']
    ])
    ->setOffers([
        [
            'title' => 'Special Offer',
            'productOfferDetails' => [
                [
                    'title' => 'SAVE20',
                    'description' => '20% off on all silver jewelry'
                ]
            ]
        ]
    ]);

// Add variants
$variant1 = EkatraSDK::variant()
    ->setBasicInfo('Silver Ring - Size 6', 25, 1000, 850)
    ->setAttributes('Silver', '6')
    ->setMedia(['img1.jpg', 'img2.jpg'], ['video1.mp4'])
    ->setId('VAR001')
    ->setWeight(250)
    ->setThumbnail('thumb1.jpg');

$variant2 = EkatraSDK::variant()
    ->setBasicInfo('Silver Ring - Size 8', 15, 1000, 900)
    ->setAttributes('Silver', '8')
    ->setMedia(['img3.jpg', 'img4.jpg'])
    ->setId('VAR002')
    ->setWeight(260)
    ->setThumbnail('thumb2.jpg');

$product->addVariant($variant1)->addVariant($variant2);

// Add sizes
$product->addSize('size-6', '6')->addSize('size-8', '8');

echo "✅ Product created manually\n";
echo "Product ID: " . $product->productId . "\n";
echo "Title: " . $product->title . "\n";
echo "Variants: " . count($product->variants) . "\n\n";

// Example 2: Auto-mapping from Customer Data
echo "2. Auto-mapping from Customer Data\n";
echo "-----------------------------------\n";

$customerData = [
    'id' => 'PROD002',
    'name' => 'Gold Necklace',
    'desc' => 'Elegant gold necklace for special occasions',
    'url' => 'https://mystore.com/products/necklace001',
    'keywords' => 'necklace,gold,jewelry,elegant',
    'currency' => 'INR',
    'variants' => [
        [
            'name' => 'Gold Necklace - 18K',
            'price' => 2500,
            'originalPrice' => 3000,
            'stock' => 10,
            'color' => 'Gold',
            'size' => 'One Size',
            'images' => 'img1.jpg,img2.jpg,img3.jpg',
            'videos' => 'video1.mp4'
        ],
        [
            'name' => 'Gold Necklace - 22K',
            'price' => 3500,
            'originalPrice' => 4000,
            'stock' => 5,
            'color' => 'Gold',
            'size' => 'One Size',
            'images' => ['img4.jpg', 'img5.jpg']
        ]
    ],
    'specifications' => [
        ['key' => 'Material', 'value' => '18K/22K Gold'],
        ['key' => 'Length', 'value' => '18 inches'],
        ['key' => 'Weight', 'value' => '15g']
    ]
];

$mappedProduct = EkatraSDK::productFromData($customerData);
echo "✅ Product mapped from customer data\n";
echo "Product ID: " . $mappedProduct->productId . "\n";
echo "Title: " . $mappedProduct->title . "\n";
echo "Variants: " . count($mappedProduct->variants) . "\n\n";

// Example 3: Validation and Transformation
echo "3. Validation and Transformation\n";
echo "--------------------------------\n";

// Validate product
$validation = $mappedProduct->validate();
if ($validation['valid']) {
    echo "✅ Product validation passed\n";
} else {
    echo "❌ Product validation failed:\n";
    foreach ($validation['errors'] as $error) {
        echo "  - $error\n";
    }
}

// Transform to Ekatra format
try {
    $ekatraFormat = $mappedProduct->toEkatraFormat();
    echo "✅ Product transformed to Ekatra format\n";
    echo "Ekatra Product ID: " . $ekatraFormat['productId'] . "\n";
    echo "Ekatra Title: " . $ekatraFormat['title'] . "\n";
    echo "Ekatra Variants: " . count($ekatraFormat['variants']) . "\n";
} catch (Exception $e) {
    echo "❌ Transformation failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 4: Using SDK Helper Methods
echo "4. Using SDK Helper Methods\n";
echo "---------------------------\n";

$result = EkatraSDK::transformProduct($customerData);
if ($result['success']) {
    echo "✅ SDK transformation successful\n";
    echo "Data keys: " . implode(', ', array_keys($result['data'])) . "\n";
} else {
    echo "❌ SDK transformation failed\n";
    echo "Error: " . $result['error'] . "\n";
}

echo "\n";

// Example 5: Error Handling
echo "5. Error Handling\n";
echo "------------------\n";

$invalidData = [
    'id' => '', // Invalid: empty ID
    'name' => 'Test Product',
    'desc' => '', // Invalid: empty description
    'variants' => [
        [
            'name' => 'Test Variant',
            'price' => -100, // Invalid: negative price
            'stock' => 5
        ]
    ]
];

$result = EkatraSDK::transformProduct($invalidData);
if (!$result['success']) {
    echo "❌ Expected validation failure\n";
    echo "Error: " . $result['error'] . "\n";
    if (isset($result['validation']['errors'])) {
        echo "Validation errors:\n";
        foreach ($result['validation']['errors'] as $error) {
            echo "  - $error\n";
        }
    }
}

echo "\n🎉 Examples completed!\n";
