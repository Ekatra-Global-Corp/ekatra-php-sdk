<?php

namespace Ekatra\Product\Helpers;

/**
 * ManualSetupGuide
 * 
 * Provides step-by-step instructions for manual product setup
 * when auto-transformation fails or is not desired
 */
class ManualSetupGuide
{
    /**
     * Get manual setup instructions
     */
    public function getManualSetupInstructions(): array
    {
        return [
            'title' => '🛠️ Manual Setup Guide',
            'description' => 'If auto-transformation fails, you can manually build your product object using our SDK methods:',
            'steps' => [
                [
                    'step' => 1,
                    'title' => 'Create Product Object',
                    'code' => '$product = EkatraSDK::product();',
                    'description' => 'Start with a new product instance'
                ],
                [
                    'step' => 2,
                    'title' => 'Set Basic Product Info',
                    'code' => '$product->setBasicInfo(
    "PROD001", 
    "Your Product Title", 
    "Product description", 
    "INR"
);',
                    'description' => 'Set required product fields'
                ],
                [
                    'step' => 3,
                    'title' => 'Set Optional Product Fields',
                    'code' => '$product->setHandle("my-product");
$product->addCategory("electronics");
$product->addTag("featured");
$product->setCountryCode("IN");',
                    'description' => 'Set optional product fields for better organization'
                ],
                [
                    'step' => 4,
                    'title' => 'Create Variant',
                    'code' => '$variant = EkatraSDK::variant();
$variant->setBasicInfo(
    "Red Variant",
    10, // quantity
    1000, // mrp
    800  // selling price
);
$variant->setColor("red");
$variant->setSize("M");',
                    'description' => 'Create and configure variant'
                ],
                [
                    'step' => 5,
                    'title' => 'Add Variations to Variant',
                    'code' => '$variant->addVariation([
    "sizeId" => "size-s",
    "mrp" => 1000,
    "sellingPrice" => 800,
    "availability" => true,
    "quantity" => 10,
    "size" => "Small",
    "variantId" => "var-001"
]);',
                    'description' => 'Add variations array to variant'
                ],
                [
                    'step' => 6,
                    'title' => 'Add Media to Variant',
                    'code' => '$variant->addMedia([
    "mediaType" => "IMAGE",
    "playUrl" => "https://example.com/image.jpg",
    "mimeType" => "image/jpeg",
    "playerTypeEnum" => "IMAGE",
    "weight" => 0,
    "duration" => 0,
    "size" => 0
]);',
                    'description' => 'Add media list to variant'
                ],
                [
                    'step' => 7,
                    'title' => 'Add Variant to Product',
                    'code' => '$product->addVariant($variant);',
                    'description' => 'Add variant to product'
                ],
                [
                    'step' => 8,
                    'title' => 'Add Sizes to Product',
                    'code' => '$product->addSize("size-s", "Small");
$product->addSize("size-m", "Medium");',
                    'description' => 'Add sizes array to product'
                ],
                [
                    'step' => 9,
                    'title' => 'Transform to Ekatra Format',
                    'code' => '$result = $product->toEkatraFormatWithValidation();
if ($result["success"]) {
    echo json_encode($result["data"], JSON_PRETTY_PRINT);
} else {
    echo "Validation errors: " . implode(", ", $result["validation"]["errors"]);
}',
                    'description' => 'Get final Ekatra format'
                ]
            ]
        ];
    }
    
    /**
     * Get data structure examples
     */
    public function getDataStructureExamples(): array
    {
        return [
            'title' => '📋 Supported Data Structure Examples',
            'examples' => [
                [
                    'name' => 'Simple Single Variant',
                    'description' => 'Customer provides flat variant fields',
                    'example' => [
                        'product_id' => 'PROD001',
                        'title' => 'Product Title',
                        'variant_name' => 'Variant Name',
                        'variant_mrp' => 1000,
                        'variant_selling_price' => 800,
                        'image_urls' => 'url1,url2,url3'
                    ]
                ],
                [
                    'name' => 'Simple Multi Variant',
                    'description' => 'Customer provides variants array with simple fields',
                    'example' => [
                        'product_id' => 'PROD001',
                        'title' => 'Product Title',
                        'variants' => [
                            [
                                'variant_name' => 'Variant 1',
                                'variant_mrp' => 1000,
                                'variant_selling_price' => 800
                            ],
                            [
                                'variant_name' => 'Variant 2',
                                'variant_mrp' => 1200,
                                'variant_selling_price' => 900
                            ]
                        ]
                    ]
                ],
                [
                    'name' => 'Complex Structure',
                    'description' => 'Customer provides full Ekatra-like structure',
                    'example' => [
                        'product_id' => 'PROD001',
                        'title' => 'Product Title',
                        'variants' => [
                            [
                                'name' => 'Variant Name',
                                'color' => 'red',
                                'variations' => [
                                    [
                                        'sizeId' => 'size-123',
                                        'mrp' => 1000,
                                        'sellingPrice' => 800,
                                        'availability' => true,
                                        'quantity' => 10,
                                        'size' => 'M',
                                        'variantId' => 'var-123'
                                    ]
                                ],
                                'mediaList' => [
                                    [
                                        'mediaType' => 'IMAGE',
                                        'playUrl' => 'https://example.com/image.jpg',
                                        'mimeType' => 'image/jpeg',
                                        'playerTypeEnum' => 'IMAGE'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Get code examples for common scenarios
     */
    public function getCodeExamples(): array
    {
        return [
            'title' => '💻 Code Examples',
            'examples' => [
                [
                    'name' => 'Basic Product Creation',
                    'description' => 'Create a simple product with one variant',
                    'code' => '<?php
use Ekatra\Product\EkatraSDK;

// Create product
$product = EkatraSDK::product();
$product->setBasicInfo("PROD001", "My Product", "Description", "USD");

// Create variant
$variant = EkatraSDK::variant();
$variant->setBasicInfo("Red", 10, 1000, 800);
$variant->setColor("red");

// Add variation
$variant->addVariation([
    "sizeId" => "size-s",
    "mrp" => 1000,
    "sellingPrice" => 800,
    "availability" => true,
    "quantity" => 10,
    "size" => "Small",
    "variantId" => "var-001"
]);

// Add media
$variant->addMedia([
    "mediaType" => "IMAGE",
    "playUrl" => "https://example.com/image.jpg",
    "mimeType" => "image/jpeg",
    "playerTypeEnum" => "IMAGE"
]);

// Combine
$product->addVariant($variant);
$product->addSize("size-s", "Small");

// Transform
$result = $product->toEkatraFormatWithValidation();
echo json_encode($result["data"], JSON_PRETTY_PRINT);'
                ],
                [
                    'name' => 'Multiple Variants',
                    'description' => 'Create a product with multiple variants',
                    'code' => '<?php
use Ekatra\Product\EkatraSDK;

$product = EkatraSDK::product();
$product->setBasicInfo("PROD002", "Multi Variant Product", "Description", "EUR");

// Red variant
$redVariant = EkatraSDK::variant();
$redVariant->setBasicInfo("Red", 5, 1000, 800);
$redVariant->setColor("red");
$redVariant->addVariation([
    "sizeId" => "size-s",
    "mrp" => 1000,
    "sellingPrice" => 800,
    "availability" => true,
    "quantity" => 5,
    "size" => "Small",
    "variantId" => "var-red"
]);
$product->addVariant($redVariant);

// Blue variant
$blueVariant = EkatraSDK::variant();
$blueVariant->setBasicInfo("Blue", 3, 1000, 800);
$blueVariant->setColor("blue");
$blueVariant->addVariation([
    "sizeId" => "size-s",
    "mrp" => 1000,
    "sellingPrice" => 800,
    "availability" => true,
    "quantity" => 3,
    "size" => "Small",
    "variantId" => "var-blue"
]);
$product->addVariant($blueVariant);

$result = $product->toEkatraFormatWithValidation();'
                ],
                [
                    'name' => 'Auto-Transformation',
                    'description' => 'Use auto-transformation for simple data',
                    'code' => '<?php
use Ekatra\Product\EkatraSDK;

// Simple data
$simpleData = [
    "product_id" => "PROD003",
    "title" => "Auto Product",
    "description" => "Auto-transformed product",
    "currency" => "INR",
    "existing_url" => "https://example.com/product",
    "keywords" => ["auto", "product"],
    "variant_name" => "Auto Variant",
    "variant_quantity" => 10,
    "variant_mrp" => 1000,
    "variant_selling_price" => 800,
    "image_urls" => "https://example.com/image1.jpg,https://example.com/image2.jpg"
];

// Auto-transform
$result = EkatraSDK::transformProduct($simpleData);

if ($result["success"]) {
    echo "✅ Auto-transformation successful!";
    echo json_encode($result["data"], JSON_PRETTY_PRINT);
} else {
    echo "❌ Auto-transformation failed:";
    foreach ($result["validation"]["errors"] as $error) {
        echo "- " . $error;
    }
}'
                ]
            ]
        ];
    }
    
    /**
     * Get best practices
     */
    public function getBestPractices(): array
    {
        return [
            'title' => '⭐ Best Practices',
            'practices' => [
                [
                    'practice' => 'Use Auto-Transformation When Possible',
                    'description' => 'Try auto-transformation first for simple data structures',
                    'benefit' => 'Faster implementation and less code'
                ],
                [
                    'practice' => 'Validate Before Transformation',
                    'description' => 'Always validate your data before transforming',
                    'benefit' => 'Catch errors early and get clear feedback'
                ],
                [
                    'practice' => 'Use Manual Setup for Complex Requirements',
                    'description' => 'Use manual setup when you need full control',
                    'benefit' => 'Complete control over the final structure'
                ],
                [
                    'practice' => 'Handle Errors Gracefully',
                    'description' => 'Always check validation results and handle errors',
                    'benefit' => 'Better user experience and debugging'
                ],
                [
                    'practice' => 'Test with Sample Data',
                    'description' => 'Test with sample data before using real data',
                    'benefit' => 'Ensure everything works as expected'
                ],
                [
                    'practice' => 'Use Descriptive Field Names',
                    'description' => 'Use clear, descriptive names for your fields',
                    'benefit' => 'Easier to understand and maintain'
                ]
            ]
        ];
    }
    
    /**
     * Get troubleshooting guide
     */
    public function getTroubleshootingGuide(): array
    {
        return [
            'title' => '🔧 Troubleshooting Guide',
            'issues' => [
                [
                    'issue' => 'Class not found errors',
                    'solutions' => [
                        'Run composer install to install dependencies',
                        'Check autoloader configuration',
                        'Ensure you\'re using the correct namespace'
                    ]
                ],
                [
                    'issue' => 'Validation failed errors',
                    'solutions' => [
                        'Check required fields are present',
                        'Use validation suggestions to fix issues',
                        'Ensure data types are correct'
                    ]
                ],
                [
                    'issue' => 'Auto-transformation failed',
                    'solutions' => [
                        'Check data structure matches expected format',
                        'Use manual setup for complex requirements',
                        'Verify all required fields are present'
                    ]
                ],
                [
                    'issue' => 'Performance issues',
                    'solutions' => [
                        'Check data size and complexity',
                        'Consider batch processing for large datasets',
                        'Use manual setup for better control'
                    ]
                ]
            ]
        ];
    }
}
