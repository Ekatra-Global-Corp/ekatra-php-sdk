<?php

namespace Ekatra\Product\Transformers;

use Ekatra\Product\Core\EkatraProduct;
use Ekatra\Product\Core\EkatraVariant;

/**
 * FlexibleSmartTransformer
 * 
 * Enhanced transformer that handles various API response formats
 * with flexible field mapping and nested data extraction
 */
class FlexibleSmartTransformer
{
    /**
     * Field mapping for different API formats
     */
    private $fieldMappings = [
        'product_id' => ['product_id', 'id', 'item_id', 'sku', 'productId'],
        'title' => ['title', 'name', 'product_name', 'product_title'],
        'description' => ['description', 'body_html', 'product_description', 'short_description', 'meta_description'],
        'currency' => ['currency', 'currency_code', 'currencyCode'],
        'url' => ['existing_url', 'url', 'permalink', 'link', 'product_url'],
        'keywords' => ['keywords', 'product_keywords', 'tags', 'meta_keyword'],
        'variant_name' => ['variant_name', 'variant_title', 'title', 'name'],
        'variant_mrp' => ['variant_mrp', 'mrp', 'compare_at_price', 'regular_price', 'original_price', 'base_price'],
        'variant_selling_price' => ['variant_selling_price', 'selling_price', 'price', 'sale_price'],
        'variant_quantity' => ['variant_quantity', 'quantity', 'stock_quantity', 'inventory_quantity'],
        'images' => ['image_urls', 'images', 'media_gallery_entries', 'media']
    ];

    /**
     * Default values for missing fields
     */
    private $defaults = [
        'currency' => 'USD',
        'description' => 'No description available',
        'url' => 'https://example.com/product',
        'keywords' => 'product',
        'variant_name' => 'Default Variant',
        'variant_mrp' => 0,
        'variant_selling_price' => 0,
        'variant_quantity' => 0
    ];

    /**
     * Transform any API response format to Ekatra format
     */
    public function transformToEkatra($data): array
    {
        // Extract data from nested structures
        $extractedData = $this->extractData($data);
        
        // Map fields using flexible mapping
        $mappedData = $this->mapFields($extractedData);
        
        // Validate required fields
        $validation = $this->validateData($mappedData);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'data' => null,
                'validation' => $validation,
                'dataType' => $this->detectDataType($extractedData),
                'canAutoTransform' => false,
                'manualSetupRequired' => true
            ];
        }
        
        // Transform to Ekatra format
        $ekatraData = $this->buildEkatraStructure($mappedData);
        
        return [
            'success' => true,
            'data' => $ekatraData,
            'validation' => $validation,
            'dataType' => $this->detectDataType($extractedData),
            'canAutoTransform' => true,
            'manualSetupRequired' => false
        ];
    }

    /**
     * Extract data from nested structures
     */
    private function extractData($data): array
    {
        // Handle nested structures like {success: true, product_details: {...}}
        if (isset($data['product_details']) && is_array($data['product_details'])) {
            return $data['product_details'];
        }
        
        // Handle nested structures like {product: {...}}
        if (isset($data['product']) && is_array($data['product'])) {
            return $data['product'];
        }
        
        // Handle Shopify variants structure
        if (isset($data['variants']) && is_array($data['variants']) && !isset($data['title'])) {
            // This might be a variants-only response, extract first variant
            $firstVariant = $data['variants'][0] ?? [];
            return array_merge($data, $firstVariant);
        }
        
        return $data;
    }

    /**
     * Map fields using flexible field mapping
     */
    private function mapFields($data): array
    {
        $mapped = [];
        
        foreach ($this->fieldMappings as $targetField => $sourceFields) {
            $value = $this->findValueByFields($data, $sourceFields);
            $mapped[$targetField] = $value !== null ? $value : ($this->defaults[$targetField] ?? null);
        }
        
        // Handle special cases
        $mapped = $this->handleSpecialCases($data, $mapped);
        
        return $mapped;
    }

    /**
     * Find value by trying multiple field names
     */
    private function findValueByFields($data, $fields): mixed
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                return $data[$field];
            }
        }
        return null;
    }

    /**
     * Handle special cases for different formats
     */
    private function handleSpecialCases($originalData, $mappedData): array
    {
        // Handle Shopify format
        if (isset($originalData['variants']) && is_array($originalData['variants'])) {
            $mappedData['variants'] = $this->transformShopifyVariants($originalData['variants']);
        }
        
        // Handle WooCommerce format
        if (isset($originalData['price']) && isset($originalData['regular_price'])) {
            $mappedData['variant_mrp'] = $originalData['regular_price'];
            $mappedData['variant_selling_price'] = $originalData['price'];
        }
        
        // Handle Magento format
        if (isset($originalData['custom_attributes'])) {
            foreach ($originalData['custom_attributes'] as $attr) {
                if ($attr['attribute_code'] === 'description' && !$mappedData['description']) {
                    $mappedData['description'] = $attr['value'];
                }
                if ($attr['attribute_code'] === 'meta_keyword' && !$mappedData['keywords']) {
                    $mappedData['keywords'] = $attr['value'];
                }
            }
        }
        
        // Handle images from different formats
        $mappedData['images'] = $this->extractImages($originalData);
        
        return $mappedData;
    }

    /**
     * Transform Shopify variants
     */
    private function transformShopifyVariants($variants): array
    {
        $transformed = [];
        
        foreach ($variants as $variant) {
            $transformed[] = [
                'variant_name' => $variant['title'] ?? 'Default',
                'variant_mrp' => $variant['compare_at_price'] ?? $variant['price'] ?? 0,
                'variant_selling_price' => $variant['price'] ?? 0,
                'variant_quantity' => $variant['inventory_quantity'] ?? 0,
                'color' => $this->extractColorFromTitle($variant['title'] ?? ''),
                'size' => $this->extractSizeFromTitle($variant['title'] ?? '')
            ];
        }
        
        return $transformed;
    }

    /**
     * Extract images from various formats
     */
    private function extractImages($data): string
    {
        // Handle comma-separated string
        if (isset($data['image_urls']) && is_string($data['image_urls'])) {
            return $data['image_urls'];
        }
        
        // Handle array of images
        if (isset($data['images']) && is_array($data['images'])) {
            $urls = [];
            foreach ($data['images'] as $image) {
                if (is_string($image)) {
                    $urls[] = $image;
                } elseif (is_array($image) && isset($image['src'])) {
                    $urls[] = $image['src'];
                } elseif (is_array($image) && isset($image['file'])) {
                    $urls[] = $image['file'];
                }
            }
            return implode(',', $urls);
        }
        
        // Handle Shopify images
        if (isset($data['images']) && is_array($data['images'])) {
            $urls = [];
            foreach ($data['images'] as $image) {
                if (isset($image['src'])) {
                    $urls[] = $image['src'];
                }
            }
            return implode(',', $urls);
        }
        
        return '';
    }

    /**
     * Extract color from variant title (e.g., "Small / Red" -> "Red")
     */
    private function extractColorFromTitle($title): string
    {
        if (preg_match('/\/([^\/]+)$/', $title, $matches)) {
            return trim($matches[1]);
        }
        return 'unknown';
    }

    /**
     * Extract size from variant title (e.g., "Small / Red" -> "Small")
     */
    private function extractSizeFromTitle($title): string
    {
        if (preg_match('/^([^\/]+)\//', $title, $matches)) {
            return trim($matches[1]);
        }
        return 'freestyle';
    }

    /**
     * Validate mapped data
     */
    private function validateData($data): array
    {
        $errors = [];
        $suggestions = [];
        
        // Critical required fields (no defaults allowed)
        $critical = [
            'product_id' => 'Product ID',
            'title' => 'Product title'
        ];
        
        foreach ($critical as $field => $label) {
            if (empty($data[$field])) {
                $errors[] = "❌ $label is required";
                $suggestions[] = "💡 Add '$field' field to your data";
            }
        }
        
        // Check for variant data (but allow default variant creation for minimal formats)
        $hasVariantData = !empty($data['variant_mrp']) || !empty($data['variants']) || !empty($data['variant_selling_price']) || !empty($data['price']);
        $isMinimalFormat = empty($data['description']) && empty($data['currency']) && empty($data['url']);
        
        if (!$hasVariantData && !$isMinimalFormat) {
            $errors[] = "❌ No variant data found";
            $suggestions[] = "💡 Add variant data using one of these methods:";
            $suggestions[] = "   Method 1: Add 'variant_mrp', 'variant_selling_price' fields";
            $suggestions[] = "   Method 2: Add 'variants' array with variant objects";
            $suggestions[] = "   Method 3: Add 'price' field for minimal format";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'suggestions' => $suggestions,
            'canAutoTransform' => empty($errors),
            'manualSetupRequired' => !empty($errors)
        ];
    }

    /**
     * Detect data type
     */
    private function detectDataType($data): string
    {
        if (isset($data['variants']) && is_array($data['variants'])) {
            return 'COMPLEX_STRUCTURE';
        }
        
        if (isset($data['variant_name']) || isset($data['variant_mrp'])) {
            return 'SIMPLE_SINGLE_VARIANT';
        }
        
        return 'MIXED_STRUCTURE';
    }

    /**
     * Build Ekatra structure
     */
    private function buildEkatraStructure($data): array
    {
        $productId = $data['product_id'] ?? $this->generateId();
        $title = $data['title'] ?? 'Untitled Product';
        $description = $data['description'] ?? '';
        $currency = $data['currency'] ?? 'USD';
        $url = $data['url'] ?? '';
        $keywords = $data['keywords'] ?? '';
        
        // Handle keywords (convert array to string if needed)
        if (is_array($keywords)) {
            $keywords = implode(',', array_column($keywords, 'name'));
        }
        
        $ekatraData = [
            'productId' => (string) $productId,
            'title' => $title,
            'description' => $description,
            'currency' => $currency,
            'existingProductUrl' => $url,
            'keywords' => $keywords,
            'variants' => [],
            'sizes' => []
        ];
        
        // Handle single variant
        if (isset($data['variant_mrp']) && isset($data['variant_selling_price'])) {
            $variant = $this->buildSingleVariant($data);
            $ekatraData['variants'][] = $variant;
        }
        
        // Handle multiple variants
        if (isset($data['variants']) && is_array($data['variants'])) {
            foreach ($data['variants'] as $variantData) {
                $variant = $this->buildSingleVariant($variantData);
                $ekatraData['variants'][] = $variant;
            }
        }
        
        // Handle minimal format - create default variant if no variant data
        if (empty($ekatraData['variants'])) {
            $defaultVariant = $this->buildDefaultVariant($data);
            $ekatraData['variants'][] = $defaultVariant;
        }
        
        // Generate sizes
        $ekatraData['sizes'] = $this->generateSizes($ekatraData['variants']);
        
        return $ekatraData;
    }

    /**
     * Build default variant for minimal data
     */
    private function buildDefaultVariant($data): array
    {
        $variantId = $this->generateId();
        $sizeId = $this->generateId();
        
        // Try to extract price from various fields
        $price = $this->findValueByFields($data, ['price', 'variant_selling_price', 'selling_price', 'sale_price']);
        $mrp = $this->findValueByFields($data, ['mrp', 'variant_mrp', 'compare_at_price', 'regular_price', 'original_price']);
        
        // If no price found, use 0
        $price = $price ?: 0;
        $mrp = $mrp ?: $price;
        
        return [
            '_id' => $variantId,
            'color' => 'unknown',
            'variations' => [
                [
                    'sizeId' => $sizeId,
                    'mrp' => (string) $mrp,
                    'sellingPrice' => (string) $price,
                    'discount' => $mrp > $price ? round((($mrp - $price) / $mrp) * 100, 2, PHP_ROUND_HALF_UP) : 0,
                    'availability' => true,
                    'quantity' => 1,
                    'size' => 'freestyle',
                    'variantId' => $variantId
                ]
            ],
            'weight' => 0,
            'thumbnail' => '',
            'mediaList' => []
        ];
    }

    /**
     * Build single variant
     */
    private function buildSingleVariant($data): array
    {
        $variantId = $this->generateId();
        $color = $data['color'] ?? 'unknown';
        $mrp = $data['variant_mrp'] ?? 0;
        $sellingPrice = $data['variant_selling_price'] ?? 0;
        $quantity = $data['variant_quantity'] ?? 0;
        $size = $data['size'] ?? 'freestyle';
        
        // Calculate discount
        $discount = 0;
        if ($mrp > 0 && $sellingPrice < $mrp) {
            $discount = (($mrp - $sellingPrice) / $mrp) * 100;
            $discount = round($discount, 2, PHP_ROUND_HALF_UP);
        }
        
        $sizeId = $this->generateId();
        
        return [
            '_id' => $variantId,
            'color' => $color,
            'variations' => [
                [
                    'sizeId' => $sizeId,
                    'mrp' => (string) $mrp,
                    'sellingPrice' => (string) $sellingPrice,
                    'discount' => $discount,
                    'availability' => $quantity > 0,
                    'quantity' => (int) $quantity,
                    'size' => $size,
                    'variantId' => $variantId
                ]
            ],
            'weight' => $data['weight'] ?? 0,
            'thumbnail' => $this->extractThumbnail($data),
            'mediaList' => $this->buildMediaList($data)
        ];
    }

    /**
     * Extract thumbnail from images
     */
    private function extractThumbnail($data): string
    {
        $images = $this->extractImages($data);
        if ($images) {
            $imageArray = explode(',', $images);
            return trim($imageArray[0]);
        }
        return '';
    }

    /**
     * Build media list
     */
    private function buildMediaList($data): array
    {
        $images = $this->extractImages($data);
        if (!$images) {
            return [];
        }
        
        $mediaList = [];
        $imageArray = explode(',', $images);
        
        foreach ($imageArray as $imageUrl) {
            $imageUrl = trim($imageUrl);
            if ($imageUrl) {
                $mediaList[] = [
                    'mediaType' => 'IMAGE',
                    'playUrl' => $imageUrl,
                    'mimeType' => 'image/jpeg',
                    'playerTypeEnum' => 'IMAGE',
                    'weight' => 0,
                    'duration' => 0,
                    'size' => 0
                ];
            }
        }
        
        return $mediaList;
    }

    /**
     * Generate sizes array
     */
    private function generateSizes($variants): array
    {
        $sizes = [];
        $sizeMap = [];
        
        foreach ($variants as $variant) {
            foreach ($variant['variations'] as $variation) {
                $sizeId = $variation['sizeId'];
                $sizeName = $variation['size'];
                
                if (!isset($sizeMap[$sizeId])) {
                    $sizeMap[$sizeId] = $sizeName;
                    $sizes[] = [
                        '_id' => $sizeId,
                        'name' => $sizeName
                    ];
                }
            }
        }
        
        return $sizes;
    }

    /**
     * Generate UUID4
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
