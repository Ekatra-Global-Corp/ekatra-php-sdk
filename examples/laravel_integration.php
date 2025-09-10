<?php

/**
 * Laravel Integration Examples
 * 
 * This file shows how to integrate the Ekatra SDK with Laravel applications
 */

// In your Laravel controller or service class

use Ekatra\Product\EkatraSDK;
use Ekatra\Product\Core\EkatraProduct;
use Ekatra\Product\Core\EkatraVariant;

class ProductController extends Controller
{
    /**
     * Example 1: Basic API endpoint that returns Ekatra format
     */
    public function getProduct($id)
    {
        try {
            // Get your product data from database
            $myProduct = Product::findOrFail($id);
            
            // Transform to Ekatra format
            $ekatraProduct = EkatraSDK::productFromData($myProduct->toArray());
            
            // Validate before returning
            $validation = $ekatraProduct->validate();
            if (!$validation['valid']) {
                Log::warning('Product validation failed', [
                    'product_id' => $id,
                    'errors' => $validation['errors']
                ]);
            }
            
            // Return in Ekatra format
            return response()->json([
                'success' => true,
                'data' => $ekatraProduct->toEkatraFormat()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Product transformation failed', [
                'product_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Product transformation failed'
            ], 500);
        }
    }

    /**
     * Example 2: Bulk product transformation
     */
    public function getProducts()
    {
        try {
            $products = Product::all();
            $ekatraProducts = [];
            
            foreach ($products as $product) {
                $ekatraProduct = EkatraSDK::productFromData($product->toArray());
                $result = $ekatraProduct->toEkatraFormatWithValidation();
                
                if ($result['success']) {
                    $ekatraProducts[] = $result['data'];
                } else {
                    Log::warning('Product transformation failed', [
                        'product_id' => $product->id,
                        'errors' => $result['validation']['errors'] ?? []
                    ]);
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $ekatraProducts,
                'count' => count($ekatraProducts)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Bulk transformation failed'
            ], 500);
        }
    }

    /**
     * Example 3: Using Laravel Facade
     */
    public function getProductWithFacade($id)
    {
        // Using the Ekatra facade
        $product = \Ekatra::productFromData(Product::findOrFail($id)->toArray());
        
        return response()->json([
            'success' => true,
            'data' => $product->toEkatraFormat()
        ]);
    }

    /**
     * Example 4: Custom field mapping
     */
    public function getProductWithCustomMapping($id)
    {
        $myProduct = Product::findOrFail($id);
        
        // Your custom data structure
        $customData = [
            'productId' => $myProduct->sku,
            'title' => $myProduct->product_name,
            'description' => $myProduct->long_description,
            'existingUrl' => $myProduct->product_url,
            'keywords' => explode(',', $myProduct->tags),
            'variants' => $myProduct->variants->map(function($variant) {
                return [
                    'name' => $variant->variant_name,
                    'price' => $variant->current_price,
                    'originalPrice' => $variant->original_price,
                    'stock' => $variant->inventory_count,
                    'color' => $variant->color_name,
                    'size' => $variant->size_name,
                    'images' => $variant->images->pluck('url')->toArray()
                ];
            })->toArray()
        ];
        
        $ekatraProduct = EkatraSDK::productFromData($customData);
        
        return response()->json([
            'success' => true,
            'data' => $ekatraProduct->toEkatraFormat()
        ]);
    }

    /**
     * Example 5: Error handling with validation
     */
    public function getProductWithErrorHandling($id)
    {
        try {
            $myProduct = Product::findOrFail($id);
            $ekatraProduct = EkatraSDK::productFromData($myProduct->toArray());
            
            // Get validation result
            $validation = $ekatraProduct->validate();
            
            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Product validation failed',
                    'validation_errors' => $validation['errors']
                ], 400);
            }
            
            return response()->json([
                'success' => true,
                'data' => $ekatraProduct->toEkatraFormat()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Product API error', [
                'product_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Internal server error'
            ], 500);
        }
    }
}

/**
 * Example 6: Using in a Service Class
 */
class ProductTransformationService
{
    public function transformProduct(Product $product): array
    {
        $ekatraProduct = EkatraSDK::productFromData($product->toArray());
        
        // Log transformation
        Log::info('Product transformed', [
            'product_id' => $product->id,
            'ekatra_product_id' => $ekatraProduct->productId
        ]);
        
        return $ekatraProduct->toEkatraFormat();
    }
    
    public function validateProduct(Product $product): array
    {
        $ekatraProduct = EkatraSDK::productFromData($product->toArray());
        return $ekatraProduct->validate();
    }
    
    public function transformProducts(Collection $products): array
    {
        $results = [];
        
        foreach ($products as $product) {
            try {
                $ekatraProduct = EkatraSDK::productFromData($product->toArray());
                $result = $ekatraProduct->toEkatraFormatWithValidation();
                
                $results[] = [
                    'product_id' => $product->id,
                    'success' => $result['success'],
                    'data' => $result['data'],
                    'validation' => $result['validation']
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'product_id' => $product->id,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}

/**
 * Example 7: Using in a Job for async processing
 */
class TransformProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $productId;
    
    public function __construct($productId)
    {
        $this->productId = $productId;
    }
    
    public function handle()
    {
        $product = Product::find($this->productId);
        if (!$product) {
            Log::error('Product not found for transformation', ['product_id' => $this->productId]);
            return;
        }
        
        try {
            $ekatraProduct = EkatraSDK::productFromData($product->toArray());
            $result = $ekatraProduct->toEkatraFormatWithValidation();
            
            if ($result['success']) {
                // Store transformed data or send to external API
                $this->storeTransformedData($result['data']);
            } else {
                Log::warning('Product transformation failed in job', [
                    'product_id' => $this->productId,
                    'errors' => $result['validation']['errors'] ?? []
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Product transformation job failed', [
                'product_id' => $this->productId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function storeTransformedData(array $data)
    {
        // Your logic to store or send the transformed data
        Log::info('Product transformed successfully', ['product_id' => $this->productId]);
    }
}

/**
 * Example 8: Using in a Middleware for automatic transformation
 */
class EkatraTransformationMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        // Only transform product-related responses
        if ($request->is('api/products/*') && $response->getStatusCode() === 200) {
            $data = $response->getData(true);
            
            if (isset($data['data']) && is_array($data['data'])) {
                try {
                    $ekatraProduct = EkatraSDK::productFromData($data['data']);
                    $transformedData = $ekatraProduct->toEkatraFormat();
                    
                    $response->setData([
                        'success' => true,
                        'data' => $transformedData
                    ]);
                } catch (\Exception $e) {
                    Log::error('Middleware transformation failed', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        return $response;
    }
}
