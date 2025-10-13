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
        
        // Calculate discount and handle discountLabel
        $mrp = (float) ($data['variant_mrp'] ?? 0);
        $sellingPrice = (float) ($data['variant_selling_price'] ?? 0);
        $discountValue = $data['discount'] ?? null;
        $discountLabelValue = $data['discountLabel'] ?? $data['discount_label'] ?? null;
        
        // Always calculate percentage for display purposes
        $calculatedDiscount = 0.0;
        if ($mrp > 0 && $sellingPrice < $mrp) {
            $calculatedDiscount = round((($mrp - $sellingPrice) / $mrp) * 100, 2, PHP_ROUND_HALF_UP);
        }
        
        // Determine discount and discountLabel based on input
        $discount = null;
        $discountLabel = null;
        
        if ($discountValue !== null) {
            if (is_numeric($discountValue)) {
                // Numeric discount provided - use as percentage
                $discount = (float) $discountValue;
                $discountLabel = $discountLabelValue ? (string) $discountLabelValue : null;
            } else {
                // String discount provided - use as label, keep calculated percentage
                $discount = $calculatedDiscount;
                $discountLabel = (string) $discountValue;
            }
        } else {
            // No discount provided - use calculated percentage
            $discount = $calculatedDiscount;
            $discountLabel = $discountLabelValue ? (string) $discountLabelValue : null;
        }
        
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
                    'color' => $data['variant_color'] ?? 'unknown',
                    'variations' => [
                        [
                            'sizeId' => $sizeId,
                            'mrp' => (float) ($data['variant_mrp'] ?? 0),
                            'sellingPrice' => (float) ($data['variant_selling_price'] ?? 0),
                            'discount' => $discount,
                            'discountLabel' => $discountLabel,
                            'availability' => ($data['variant_quantity'] ?? 0) > 0,
                            'quantity' => (int) ($data['variant_quantity'] ?? 0),
                            'size' => $data['variant_size'] ?? 'freestyle',
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
                    'name' => $data['variant_size'] ?? 'freestyle'
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
            
            // Calculate discount and handle discountLabel for each variant
            $mrp = (float) ($variant['variant_mrp'] ?? $variant['mrp'] ?? 0);
            $sellingPrice = (float) ($variant['variant_selling_price'] ?? $variant['sellingPrice'] ?? 0);
            $discountValue = $variant['discount'] ?? null;
            $discountLabelValue = $variant['discountLabel'] ?? $variant['discount_label'] ?? null;
            
            // Always calculate percentage for display purposes
            $calculatedDiscount = 0.0;
            if ($mrp > 0 && $sellingPrice < $mrp) {
                $calculatedDiscount = round((($mrp - $sellingPrice) / $mrp) * 100, 2, PHP_ROUND_HALF_UP);
            }
            
            // Determine discount and discountLabel based on input
            $discount = null;
            $discountLabel = null;
            
            if ($discountValue !== null) {
                if (is_numeric($discountValue)) {
                    // Numeric discount provided - use as percentage
                    $discount = (float) $discountValue;
                    $discountLabel = $discountLabelValue ? (string) $discountLabelValue : null;
                } else {
                    // String discount provided - use as label, keep calculated percentage
                    $discount = $calculatedDiscount;
                    $discountLabel = (string) $discountValue;
                }
            } else {
                // No discount provided - use calculated percentage
                $discount = $calculatedDiscount;
                $discountLabel = $discountLabelValue ? (string) $discountLabelValue : null;
            }
            
            $transformedVariants[] = [
                '_id' => $variantId,
                'name' => $variant['variant_name'] ?? $variant['name'] ?? "Variant $index",
                'color' => $variant['variant_color'] ?? $variant['color'] ?? 'unknown',
                'variations' => [
                    [
                        'sizeId' => $sizeId,
                        'mrp' => $mrp,
                        'sellingPrice' => $sellingPrice,
                        'discount' => $discount,
                        'discountLabel' => $discountLabel,
                        'availability' => ($variant['variant_quantity'] ?? $variant['quantity'] ?? 0) > 0,
                        'quantity' => (int) ($variant['variant_quantity'] ?? $variant['quantity'] ?? 0),
                        'size' => $variant['variant_size'] ?? $variant['size'] ?? 'freestyle',
                        'variantId' => $variantId
                    ]
                ],
                'mediaList' => $this->createMediaList($images),
                'weight' => 0,
                'thumbnail' => $images[0] ?? null
            ];
            
            $transformedSizes[] = [
                '_id' => $sizeId,
                'name' => $variant['variant_size'] ?? $variant['size'] ?? 'freestyle'
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
        $result = [
            'productId' => $data['product_id'] ?? $data['productId'] ?? $this->generateId(),
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'currency' => $data['currency'] ?? 'INR',
            'existingProductUrl' => $data['existing_url'] ?? $data['existingProductUrl'] ?? '',
            'searchKeywords' => $this->extractKeywords($data),
            'variants' => $data['variants'] ?? [],
            'sizes' => $data['sizes'] ?? []
        ];
        
        // Auto-calculate discount for all variations
        if (isset($result['variants'])) {
            foreach ($result['variants'] as &$variant) {
                if (isset($variant['variations'])) {
                    foreach ($variant['variations'] as &$variation) {
                        if (!isset($variation['discount']) && isset($variation['mrp']) && isset($variation['sellingPrice'])) {
                            $mrp = (float) $variation['mrp'];
                            $sellingPrice = (float) $variation['sellingPrice'];
                            
                            if ($mrp > 0 && $sellingPrice < $mrp) {
                                // Use HALF_UP rounding (same as Java BigDecimal)
                                $discount = (($mrp - $sellingPrice) / $mrp) * 100;
                                $variation['discount'] = round($discount, 2, PHP_ROUND_HALF_UP);
                            }
                        }
                    }
                }
            }
        }
        
        return $result;
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
            // Check if variants need to be transformed to include variations
            $transformedVariants = [];
            foreach ($data['variants'] as $variant) {
                if (!isset($variant['variations'])) {
                    // This is a simple variant, transform it to complex structure
                    $variantId = $variant['variant_id'] ?? $variant['id'] ?? uniqid('variant-', true);
                    $sizeId = $this->generateId();
                    
                    $transformedVariant = $variant;
                    $transformedVariant['variations'] = [
                        [
                            'sizeId' => $sizeId,
                            'mrp' => (float) ($variant['mrp'] ?? $variant['original_price'] ?? 0),
                            'sellingPrice' => (float) ($variant['selling_price'] ?? $variant['price'] ?? 0),
                            'availability' => ($variant['quantity'] ?? 0) > 0,
                            'quantity' => (int) ($variant['quantity'] ?? 0),
                            'size' => $variant['size'] ?? 'freestyle',
                            'variantId' => $variantId
                        ]
                    ];
                    
                    // Create mediaList from images
                    if (isset($variant['images'])) {
                        $transformedVariant['mediaList'] = $this->createMediaList($variant['images']);
                    }
                    
                    $transformedVariants[] = $transformedVariant;
                } else {
                    // Already has variations, keep as is
                    $transformedVariants[] = $variant;
                }
            }
            $baseData['variants'] = $transformedVariants;
            
            // Auto-calculate discount for all variations
            foreach ($baseData['variants'] as &$variant) {
                if (isset($variant['variations'])) {
                    foreach ($variant['variations'] as &$variation) {
                        if (!isset($variation['discount']) && isset($variation['mrp']) && isset($variation['sellingPrice'])) {
                            $mrp = (float) $variation['mrp'];
                            $sellingPrice = (float) $variation['sellingPrice'];
                            
                            if ($mrp > 0 && $sellingPrice < $mrp) {
                                // Use HALF_UP rounding (same as Java BigDecimal)
                                $discount = (($mrp - $sellingPrice) / $mrp) * 100;
                                $variation['discount'] = round($discount, 2, PHP_ROUND_HALF_UP);
                            }
                        }
                    }
                }
            }
        } else {
            // Create single variant from simple data
            $variantId = $this->generateId();
            $images = $this->extractImages($data);
            
            $baseData['variants'] = [
                [
                    'id' => $variantId,
                    'color' => $data['variant_color'] ?? 'unknown',
                    'variations' => [
                        [
                            'sizeId' => $variantId,
                            'mrp' => (float) ($data['variant_mrp'] ?? 0),
                            'sellingPrice' => (float) ($data['variant_selling_price'] ?? 0),
                            'availability' => ($data['variant_quantity'] ?? 0) > 0,
                            'quantity' => (int) ($data['variant_quantity'] ?? 0),
                            'size' => $data['variant_size'] ?? 'freestyle',
                            'variantId' => $variantId
                        ]
                    ],
                    'mediaList' => $this->createMediaList($images)
                ]
            ];
            
            // Auto-calculate discount for simple variant
            if (isset($baseData['variants'][0]['variations'][0])) {
                $variation = &$baseData['variants'][0]['variations'][0];
                if (!isset($variation['discount']) && isset($variation['mrp']) && isset($variation['sellingPrice'])) {
                    $mrp = (float) $variation['mrp'];
                    $sellingPrice = (float) $variation['sellingPrice'];
                    
                    if ($mrp > 0 && $sellingPrice < $mrp) {
                        // Use HALF_UP rounding (same as Java BigDecimal)
                        $discount = (($mrp - $sellingPrice) / $mrp) * 100;
                        $variation['discount'] = round($discount, 2, PHP_ROUND_HALF_UP);
                    }
                }
            }
        }
        
        // Handle sizes
        if (isset($data['sizes'])) {
            $baseData['sizes'] = $data['sizes'];
            
            // If customer provided sizes but variants don't have proper sizeId linking,
            // we need to update the variations to use existing size IDs
            if (isset($baseData['variants'])) {
                foreach ($baseData['variants'] as &$variant) {
                    if (isset($variant['variations'])) {
                        foreach ($variant['variations'] as &$variation) {
                            // Find matching size by name
                            $sizeName = $variation['size'] ?? 'freestyle';
                            $matchingSize = null;
                            
                            foreach ($baseData['sizes'] as $size) {
                                if ($size['name'] === $sizeName) {
                                    $matchingSize = $size;
                                    break;
                                }
                            }
                            
                            if ($matchingSize) {
                                $variation['sizeId'] = $matchingSize['_id'];
                            } else {
                                // If no matching size found, create a new one
                                $newSizeId = $this->generateId();
                                $baseData['sizes'][] = [
                                    '_id' => $newSizeId,
                                    'name' => $sizeName
                                ];
                                $variation['sizeId'] = $newSizeId;
                            }
                        }
                    }
                }
            }
        } else {
            // Collect all unique sizeIds from variations
            $sizeIds = [];
            if (isset($baseData['variants'])) {
                foreach ($baseData['variants'] as $variant) {
                    if (isset($variant['variations'])) {
                        foreach ($variant['variations'] as $variation) {
                            if (isset($variation['sizeId'])) {
                                $sizeIds[$variation['sizeId']] = $variation['size'] ?? 'freestyle';
                            }
                        }
                    }
                }
            }
            
            // Generate sizes array from collected sizeIds
            if (!empty($sizeIds)) {
                $baseData['sizes'] = [];
                foreach ($sizeIds as $sizeId => $sizeName) {
                    $baseData['sizes'][] = [
                        '_id' => $sizeId,
                        'name' => $sizeName
                    ];
                }
            } else {
                // Fallback: generate freestyle size
                $sizeId = $this->generateId();
                $baseData['sizes'] = [
                    [
                        '_id' => $sizeId,
                        'name' => 'freestyle'
                    ]
                ];
            }
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
     * Generate unique ID using UUID4
     */
    private function generateId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
