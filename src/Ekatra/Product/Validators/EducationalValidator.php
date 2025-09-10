<?php

namespace Ekatra\Product\Validators;

/**
 * EducationalValidator
 * 
 * Provides clear, educational error messages and suggestions
 * to help customers understand and fix validation issues
 */
class EducationalValidator
{
    /**
     * Validate with educational guidance
     */
    public function validateWithGuidance(array $data): array
    {
        $errors = [];
        $suggestions = [];
        $fixInstructions = [];
        
        // Check critical fields
        if (empty($data['product_id']) && empty($data['id'])) {
            $errors[] = "❌ Product ID is required";
            $suggestions[] = "💡 Add 'product_id' field to your data";
            $fixInstructions[] = "🔧 Fix: Add 'product_id' => 'YOUR_PRODUCT_ID' to your data array";
        }
        
        if (empty($data['title'])) {
            $errors[] = "❌ Product title is required";
            $suggestions[] = "💡 Add 'title' field to your data";
            $fixInstructions[] = "🔧 Fix: Add 'title' => 'Your Product Title' to your data array";
        }
        
        if (empty($data['currency'])) {
            $errors[] = "❌ Currency is required";
            $suggestions[] = "💡 Add 'currency' field to your data";
            $fixInstructions[] = "🔧 Fix: Add 'currency' => 'INR' (or USD, EUR, GBP) to your data array";
        }
        
        if (empty($data['description'])) {
            $errors[] = "❌ Product description is required";
            $suggestions[] = "💡 Add 'description' field to your data";
            $fixInstructions[] = "🔧 Fix: Add 'description' => 'Your product description' to your data array";
        }
        
        if (empty($data['existing_url']) && empty($data['existingUrl'])) {
            $errors[] = "❌ Existing URL is required";
            $suggestions[] = "💡 Add 'existing_url' field to your data";
            $fixInstructions[] = "🔧 Fix: Add 'existing_url' => 'https://your-site.com/product' to your data array";
        }
        
        // Check for variant data
        if (empty($data['variants']) && empty($data['variant_name'])) {
            $errors[] = "❌ No variant data found";
            $suggestions[] = "💡 Add variant data using one of these methods:";
            $suggestions[] = "   Method 1: Add 'variant_name', 'variant_mrp', 'variant_selling_price' fields";
            $suggestions[] = "   Method 2: Add 'variants' array with variant objects";
            $fixInstructions[] = "🔧 Fix: Add variant data or use manual setup with \$product->addVariant()";
        }
        
        // Check for keywords
        if (empty($data['keywords']) && empty($data['product_keywords'])) {
            $errors[] = "❌ At least one keyword is required";
            $suggestions[] = "💡 Add 'keywords' field to your data";
            $fixInstructions[] = "🔧 Fix: Add 'keywords' => ['keyword1', 'keyword2'] to your data array";
        }
        
        // Check currency validity
        if (!empty($data['currency']) && !in_array($data['currency'], ['INR', 'USD', 'EUR', 'GBP'])) {
            $errors[] = "❌ Currency must be one of: INR, USD, EUR, GBP";
            $suggestions[] = "💡 Use a supported currency code";
            $fixInstructions[] = "🔧 Fix: Change 'currency' => '{$data['currency']}' to one of: INR, USD, EUR, GBP";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'suggestions' => $suggestions,
            'fixInstructions' => $fixInstructions,
            'canAutoTransform' => $this->canAutoTransform($data),
            'manualSetupRequired' => !$this->canAutoTransform($data),
            'dataType' => $this->detectDataType($data)
        ];
    }
    
    /**
     * Get error messages with context
     */
    public function getErrorMessages(array $errors): array
    {
        $messages = [];
        
        foreach ($errors as $error) {
            $messages[] = $this->addContextToError($error);
        }
        
        return $messages;
    }
    
    /**
     * Get suggestions based on data structure
     */
    public function getSuggestions(array $data): array
    {
        $suggestions = [];
        
        // Check for simple data that can be auto-transformed
        if (isset($data['variant_name']) && !isset($data['variants'])) {
            $suggestions[] = "🎯 We detected simple variant data. We'll auto-generate the complex structure for you!";
            $suggestions[] = "💡 Your data will be transformed to include 'variations' and 'mediaList' automatically";
        }
        
        // Check for missing optional fields
        if (empty($data['handle'])) {
            $suggestions[] = "💡 Consider adding 'handle' field for better SEO";
        }
        
        if (empty($data['categories'])) {
            $suggestions[] = "💡 Consider adding 'categories' field for better organization";
        }
        
        if (empty($data['tags'])) {
            $suggestions[] = "💡 Consider adding 'tags' field for better searchability";
        }
        
        return $suggestions;
    }
    
    /**
     * Get fix instructions for specific errors
     */
    public function getFixInstructions(array $data): array
    {
        $instructions = [];
        
        if (empty($data['product_id'])) {
            $instructions[] = [
                'error' => 'Missing Product ID',
                'fix' => "Add 'product_id' => 'YOUR_PRODUCT_ID' to your data array",
                'example' => [
                    'product_id' => 'PROD001',
                    'title' => 'Your Product Title'
                ]
            ];
        }
        
        if (empty($data['variants']) && empty($data['variant_name'])) {
            $instructions[] = [
                'error' => 'Missing Variant Data',
                'fix' => 'Add variant data using one of these methods:',
                'example' => [
                    'variant_name' => 'Red Variant',
                    'variant_mrp' => 1000,
                    'variant_selling_price' => 800,
                    'variant_quantity' => 10
                ]
            ];
        }
        
        return $instructions;
    }
    
    /**
     * Check if data can be auto-transformed
     */
    public function canAutoTransform(array $data): bool
    {
        // Check if we have the minimum required fields for auto-transformation
        $hasProductId = !empty($data['product_id']) || !empty($data['id']);
        $hasTitle = !empty($data['title']);
        $hasCurrency = !empty($data['currency']);
        $hasVariantData = !empty($data['variants']) || !empty($data['variant_name']);
        
        return $hasProductId && $hasTitle && $hasCurrency && $hasVariantData;
    }
    
    /**
     * Detect data structure type
     */
    private function detectDataType(array $data): string
    {
        if (isset($data['variant_name']) && !isset($data['variants'])) {
            return 'SIMPLE_SINGLE_VARIANT';
        }
        
        if (isset($data['variants']) && is_array($data['variants'])) {
            $firstVariant = $data['variants'][0] ?? [];
            if (isset($firstVariant['variant_name']) && !isset($firstVariant['variations'])) {
                return 'SIMPLE_MULTI_VARIANT';
            }
            
            if (isset($firstVariant['variations']) || isset($firstVariant['mediaList'])) {
                return 'COMPLEX_STRUCTURE';
            }
        }
        
        return 'MIXED_STRUCTURE';
    }
    
    /**
     * Add context to error message
     */
    private function addContextToError(string $error): string
    {
        $contextMap = [
            'Product ID is required' => 'This is needed to uniquely identify your product',
            'Product title is required' => 'This is the main name of your product',
            'Currency is required' => 'This specifies the currency for pricing',
            'No variant data found' => 'Variants are the different options of your product (size, color, etc.)',
            'At least one keyword is required' => 'Keywords help customers find your product'
        ];
        
        $context = $contextMap[$error] ?? '';
        
        return $context ? "$error ($context)" : $error;
    }
    
    /**
     * Get supported data formats
     */
    public function getSupportedFormats(): array
    {
        return [
            'SIMPLE_SINGLE_VARIANT' => [
                'description' => 'Single product with one variant',
                'example' => [
                    'product_id' => 'PROD001',
                    'title' => 'My Product',
                    'variant_name' => 'Red',
                    'variant_mrp' => 1000,
                    'variant_selling_price' => 800
                ]
            ],
            'SIMPLE_MULTI_VARIANT' => [
                'description' => 'Single product with multiple variants',
                'example' => [
                    'product_id' => 'PROD001',
                    'title' => 'My Product',
                    'variants' => [
                        ['variant_name' => 'Red', 'variant_mrp' => 1000],
                        ['variant_name' => 'Blue', 'variant_mrp' => 1000]
                    ]
                ]
            ],
            'COMPLEX_STRUCTURE' => [
                'description' => 'Full Ekatra-like structure',
                'example' => [
                    'product_id' => 'PROD001',
                    'title' => 'My Product',
                    'variants' => [
                        [
                            'name' => 'Red Variant',
                            'variations' => [
                                ['sizeId' => 'size-s', 'mrp' => 1000, 'sellingPrice' => 800]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
