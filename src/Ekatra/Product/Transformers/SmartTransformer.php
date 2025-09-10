<?php

namespace Ekatra\Product\Transformers;

use Ekatra\Product\Core\EkatraProduct;
use Ekatra\Product\Core\EkatraVariant;

/**
 * SmartTransformer
 * 
 * Handles intelligent data structure detection and transformation
 * between different customer data formats and Ekatra format
 */
class SmartTransformer
{
    /**
     * Detect data structure complexity
     */
    public function detectDataType(array $data): string
    {
        // Check for simple single variant
        if (isset($data['variant_name']) && !isset($data['variants'])) {
            return 'SIMPLE_SINGLE_VARIANT';
        }
        
        // Check for simple variants array
        if (isset($data['variants']) && is_array($data['variants'])) {
            $firstVariant = $data['variants'][0] ?? [];
            if (isset($firstVariant['variant_name']) && !isset($firstVariant['variations'])) {
                return 'SIMPLE_MULTI_VARIANT';
            }
        }
        
        // Check for complex structure
        if (isset($data['variants']) && is_array($data['variants'])) {
            $firstVariant = $data['variants'][0] ?? [];
            if (isset($firstVariant['variations']) || isset($firstVariant['mediaList'])) {
                return 'COMPLEX_STRUCTURE';
            }
        }
        
        return 'MIXED_STRUCTURE';
    }
    
    /**
     * Transform simple single variant to complex structure
     */
    public function transformSimpleToComplex(array $data): array
    {
        $variantId = $this->generateId();
        $sizeId = $this->generateId();
        
        // Extract images
        $images = $this->extractImages($data);
        
        return [
            'productId' => $data['product_id'] ?? $this->generateId(),
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'currency' => $data['currency'] ?? 'INR',
            'existingProductUrl' => $data['existing_url'] ?? '',
            'searchKeywords' => $this->extractKeywords($data),
            'variants' => [
                [
                    'id' => $variantId,
                    'name' => $data['variant_name'] ?? 'default',
                    'color' => $data['variant_color'] ?? 'unknown',
                    'variations' => [
                        [
                            'sizeId' => $sizeId,
                            'mrp' => (float) ($data['variant_mrp'] ?? 0),
                            'sellingPrice' => (float) ($data['variant_selling_price'] ?? 0),
                            'availability' => ($data['variant_quantity'] ?? 0) > 0,
                            'quantity' => (int) ($data['variant_quantity'] ?? 0),
                            'size' => $data['variant_size'] ?? 'default',
                            'variantId' => $variantId
                        ]
                    ],
                    'mediaList' => $this->createMediaList($images),
                    'weight' => 0,
                    'thumbnail' => $images[0] ?? null
                ]
            ],
            'sizes' => [
                [
                    '_id' => $sizeId,
                    'name' => $data['variant_size'] ?? 'default'
                ]
            ]
        ];
    }
    
    /**
     * Transform simple variants array to complex structure
     */
    public function transformSimpleVariantsToComplex(array $data): array
    {
        $transformedVariants = [];
        $transformedSizes = [];
        
        foreach ($data['variants'] as $index => $variant) {
            $variantId = $this->generateId();
            $sizeId = $this->generateId();
            
            $images = $this->extractImages($variant);
            
            $transformedVariants[] = [
                'id' => $variantId,
                'name' => $variant['variant_name'] ?? $variant['name'] ?? "Variant $index",
                'color' => $variant['variant_color'] ?? $variant['color'] ?? 'unknown',
                'variations' => [
                    [
                        'sizeId' => $sizeId,
                        'mrp' => (float) ($variant['variant_mrp'] ?? $variant['mrp'] ?? 0),
                        'sellingPrice' => (float) ($variant['variant_selling_price'] ?? $variant['sellingPrice'] ?? 0),
                        'availability' => ($variant['variant_quantity'] ?? $variant['quantity'] ?? 0) > 0,
                        'quantity' => (int) ($variant['variant_quantity'] ?? $variant['quantity'] ?? 0),
                        'size' => $variant['variant_size'] ?? $variant['size'] ?? 'default',
                        'variantId' => $variantId
                    ]
                ],
                'mediaList' => $this->createMediaList($images),
                'weight' => 0,
                'thumbnail' => $images[0] ?? null
            ];
            
            $transformedSizes[] = [
                '_id' => $sizeId,
                'name' => $variant['variant_size'] ?? $variant['size'] ?? 'default'
            ];
        }
        
        return [
            'productId' => $data['product_id'] ?? $this->generateId(),
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'currency' => $data['currency'] ?? 'INR',
            'existingProductUrl' => $data['existing_url'] ?? '',
            'searchKeywords' => $this->extractKeywords($data),
            'variants' => $transformedVariants,
            'sizes' => $transformedSizes
        ];
    }
    
    /**
     * Transform complex structure to Ekatra format
     */
    public function transformComplexToEkatra(array $data): array
    {
        // Complex data should already be in the right format
        // Just ensure all required fields are present
        return [
            'productId' => $data['product_id'] ?? $data['productId'] ?? $this->generateId(),
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'currency' => $data['currency'] ?? 'INR',
            'existingProductUrl' => $data['existing_url'] ?? $data['existingProductUrl'] ?? '',
            'searchKeywords' => $this->extractKeywords($data),
            'variants' => $data['variants'] ?? [],
            'sizes' => $data['sizes'] ?? []
        ];
    }
    
    /**
     * Transform mixed structure to Ekatra format
     */
    public function transformMixedToEkatra(array $data): array
    {
        // Handle mixed data by combining approaches
        $baseData = [
            'productId' => $data['product_id'] ?? $data['productId'] ?? $this->generateId(),
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'currency' => $data['currency'] ?? 'INR',
            'existingProductUrl' => $data['existing_url'] ?? $data['existingProductUrl'] ?? '',
            'searchKeywords' => $this->extractKeywords($data)
        ];
        
        // Handle variants
        if (isset($data['variants'])) {
            $baseData['variants'] = $data['variants'];
        } else {
            // Create single variant from simple data
            $variantId = $this->generateId();
            $images = $this->extractImages($data);
            
            $baseData['variants'] = [
                [
                    'id' => $variantId,
                    'name' => $data['variant_name'] ?? 'default',
                    'color' => $data['variant_color'] ?? 'unknown',
                    'variations' => [
                        [
                            'sizeId' => $variantId,
                            'mrp' => (float) ($data['variant_mrp'] ?? 0),
                            'sellingPrice' => (float) ($data['variant_selling_price'] ?? 0),
                            'availability' => ($data['variant_quantity'] ?? 0) > 0,
                            'quantity' => (int) ($data['variant_quantity'] ?? 0),
                            'size' => $data['variant_size'] ?? 'default',
                            'variantId' => $variantId
                        ]
                    ],
                    'mediaList' => $this->createMediaList($images)
                ]
            ];
        }
        
        // Handle sizes
        if (isset($data['sizes'])) {
            $baseData['sizes'] = $data['sizes'];
        } else {
            $baseData['sizes'] = [
                [
                    '_id' => $this->generateId(),
                    'name' => 'default'
                ]
            ];
        }
        
        return $baseData;
    }
    
    /**
     * Extract images from various possible fields
     */
    private function extractImages(array $data): array
    {
        $images = [];
        
        // Check multiple possible image fields
        $imageFields = ['image_urls', 'images', 'imageUrls', 'photos', 'pictures'];
        
        foreach ($imageFields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];
                
                if (is_string($value)) {
                    // Comma-separated string
                    $images = array_merge($images, array_filter(array_map('trim', explode(',', $value))));
                } elseif (is_array($value)) {
                    // Array of URLs
                    $images = array_merge($images, array_filter($value));
                }
            }
        }
        
        return array_unique($images);
    }
    
    /**
     * Extract keywords from various possible fields
     */
    private function extractKeywords(array $data): array
    {
        $keywords = [];
        
        $keywordFields = ['product_keywords', 'keywords', 'tags', 'search_keywords'];
        
        foreach ($keywordFields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];
                
                if (is_string($value)) {
                    $keywords = array_merge($keywords, array_filter(array_map('trim', explode(',', $value))));
                } elseif (is_array($value)) {
                    $keywords = array_merge($keywords, array_filter($value));
                }
            }
        }
        
        return array_unique($keywords);
    }
    
    /**
     * Create mediaList from image URLs
     */
    private function createMediaList(array $images): array
    {
        $mediaList = [];
        
        foreach ($images as $index => $imageUrl) {
            $mediaList[] = [
                'mediaType' => 'IMAGE',
                'playUrl' => $imageUrl,
                'mimeType' => $this->detectMimeType($imageUrl),
                'playerTypeEnum' => 'IMAGE',
                'weight' => $index,
                'duration' => 0,
                'size' => 0
            ];
        }
        
        return $mediaList;
    }
    
    /**
     * Detect MIME type from URL
     */
    private function detectMimeType(string $url): string
    {
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml'
        ];
        
        return $mimeTypes[$extension] ?? 'image/jpeg';
    }
    
    /**
     * Generate unique ID
     */
    private function generateId(): string
    {
        return 'auto-' . uniqid() . '-' . mt_rand(1000, 9999);
    }
}
