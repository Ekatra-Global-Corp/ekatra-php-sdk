<?php

use Illuminate\Support\Facades\Route;
use Ekatra\Product\Core\EkatraProduct;
use Ekatra\Product\Core\EkatraVariant;
use Ekatra\Product\Exceptions\EkatraValidationException;
use Ekatra\Product\ResponseBuilder;

/**
 * Ekatra SDK Test Routes
 * 
 * These routes are only available in non-production environments
 * and are used for testing the SDK functionality
 */

if (app()->environment('local', 'testing')) {
    
    /**
     * Test product mapping endpoint
     */
    Route::post('/ekatra/test/product', function (Illuminate\Http\Request $request) {
        try {
            $customerData = $request->all();
            
            $product = EkatraProduct::fromCustomerData($customerData);
            $result = $product->toEkatraFormatWithValidation();
            
            return response()->json($result); // Core class now returns v2.0.0 structure directly
            
        } catch (EkatraValidationException $e) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'metadata' => [
                    'validation' => [
                        'valid' => false,
                        'errors' => $e->getErrors()
                    ],
                    'sdkVersion' => \Ekatra\Product\EkatraSDK::version()
                ],
                'message' => 'Product validation failed: ' . $e->getMessage()
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'validation' => null,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    });

    /**
     * Test variant mapping endpoint
     */
    Route::post('/ekatra/test/variant', function (Illuminate\Http\Request $request) {
        try {
            $customerData = $request->all();
            
            $variant = EkatraVariant::fromCustomerData($customerData);
            $result = $variant->toEkatraFormatWithValidation();
            
            return response()->json($result); // Core class now returns v2.0.0 structure directly
            
        } catch (EkatraValidationException $e) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'metadata' => [
                    'validation' => [
                        'valid' => false,
                        'errors' => $e->getErrors()
                    ],
                    'sdkVersion' => \Ekatra\Product\EkatraSDK::version()
                ],
                'message' => 'Variant validation failed: ' . $e->getMessage()
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'validation' => null,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    });

    /**
     * Test complete product with variants endpoint
     */
    Route::post('/ekatra/test/complete', function (Illuminate\Http\Request $request) {
        try {
            $customerData = $request->all();
            
            $product = EkatraProduct::fromCustomerData($customerData);
            $result = $product->toEkatraFormatWithValidation();
            
            // Also test individual variants
            $variantResults = [];
            foreach ($product->variants as $index => $variant) {
                if ($variant instanceof EkatraVariant) {
                    $variantResult = $variant->toEkatraFormatWithValidation();
                    $variantResults[$index] = $variantResult;
                }
            }
            
            // Merge variant results into the main result
            $result['additionalInfo']['variant_results'] = $variantResults;
            return response()->json($result); // Core class now returns v2.0.0 structure directly
            
        } catch (EkatraValidationException $e) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'metadata' => [
                    'validation' => [
                        'valid' => false,
                        'errors' => $e->getErrors()
                    ],
                    'sdkVersion' => \Ekatra\Product\EkatraSDK::version()
                ],
                'message' => 'Product validation failed: ' . $e->getMessage()
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'validation' => null,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    });

    /**
     * Get sample data endpoint
     */
    Route::get('/ekatra/test/sample', function () {
        return response()->json([
            'product_sample' => [
                'id' => 'PROD001',
                'name' => 'Sample Product',
                'description' => 'This is a sample product for testing',
                'url' => 'https://example.com/products/sample',
                'keywords' => 'sample,test,product',
                'variants' => [
                    [
                        'name' => 'Sample Variant',
                        'price' => 99.99,
                        'originalPrice' => 129.99,
                        'stock' => 10,
                        'color' => 'Red',
                        'size' => 'M',
                        'images' => ['https://example.com/image1.jpg', 'https://example.com/image2.jpg']
                    ]
                ]
            ],
            'variant_sample' => [
                'name' => 'Sample Variant',
                'price' => 99.99,
                'originalPrice' => 129.99,
                'stock' => 10,
                'color' => 'Red',
                'size' => 'M',
                'images' => ['https://example.com/image1.jpg', 'https://example.com/image2.jpg']
            ]
        ]);
    });
}
