<?php

namespace Ekatra\Product\Transformers;

use Ekatra\Product\Core\EkatraProduct;
use Ekatra\Product\Core\EkatraVariant;
use Ekatra\Product\ResponseBuilder;

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
        'images' => ['image_urls', 'ImageURLs', 'imageUrls', 'images', 'Images', 'media_gallery_entries', 'media'],
        'variants' => ['variants'],
        'max_quantity' => ['max_quantity', 'maxQuantity', 'max_purchase_quantity', 'quantity_limit'],
        'discount' => ['discount', 'discount_percent', 'discountPercentage', 'discountAmount', 'discount_text'],
        'discountLabel' => ['discountLabel', 'discount_label', 'discountText', 'discount_description', 'offer_text'],
        'countryCode' => ['countryCode', 'country_code', 'country', 'origin_country']
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
        'variant_quantity' => 0,
        'max_quantity' => null
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
            return ResponseBuilder::validationError($validation, 'Product transformation failed');
        }
        
        // Transform to Ekatra format
        $ekatraData = $this->buildEkatraStructure($mappedData);
        
        return ResponseBuilder::success(
            $ekatraData,
            [
                'validation' => $validation,
                'dataType' => $this->detectDataType($extractedData),
                'canAutoTransform' => true,
                'manualSetupRequired' => false,
                'maxQuantity' => $this->extractMaxQuantity($mappedData)
            ],
            'Product details retrieved successfully'
        );
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
    private function findValueByFields($data, $fields)
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
        // Handle Shopify format (only for actual Shopify data)
        if (isset($originalData['variants']) && is_array($originalData['variants']) && $this->isShopifyFormat($originalData)) {
            $mappedData['variants'] = $this->transformShopifyVariants($originalData['variants']);
        }
        
        // Handle WooCommerce format (only if no variants array exists to avoid conflicts)
        if (isset($originalData['price']) && isset($originalData['regular_price']) && !isset($originalData['variants'])) {
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
                'variant_name' => $variant['title'] ?? $variant['name'] ?? 'Default',
                'variant_mrp' => $this->findValueByFields($variant, ['compare_at_price', 'mrp', 'price']) ?? 0,
                'variant_selling_price' => $this->findValueByFields($variant, ['price', 'selling_price']) ?? 0,
                'variant_quantity' => $this->findValueByFields($variant, ['inventory_quantity', 'quantity', 'variant_quantity', 'stock_quantity']) ?? 0,
                'color' => $this->extractColorFromTitle($variant['title'] ?? ''),
                'size' => $this->extractSizeFromTitle($variant['title'] ?? '')
            ];
        }
        
        return $transformed;
    }

    /**
     * Check if data looks like Shopify format
     */
    private function isShopifyFormat($data): bool
    {
        // Shopify typically has variants with 'title', 'compare_at_price', 'inventory_quantity'
        if (isset($data['variants']) && is_array($data['variants']) && !empty($data['variants'])) {
            $firstVariant = $data['variants'][0];
            return isset($firstVariant['compare_at_price']) || isset($firstVariant['inventory_quantity']);
        }
        return false;
    }

    /**
     * Extract images from various formats
     */
    private function extractImages($data): string
    {
        // Handle various image field names (case-insensitive)
        $imageFields = ['image_urls', 'ImageURLs', 'imageUrls', 'images', 'Images', 'photos', 'pictures'];
        
        foreach ($imageFields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];
                
                // Handle comma-separated string
                if (is_string($value)) {
                    return $value;
                }
                
                // Handle array of images
                if (is_array($value)) {
                    $urls = [];
                    foreach ($value as $image) {
                        if (is_string($image)) {
                            $urls[] = $image;
                        } elseif (is_array($image) && isset($image['src'])) {
                            $urls[] = $image['src'];
                        } elseif (is_array($image) && isset($image['file'])) {
                            $urls[] = $image['file'];
                        }
                    }
                    if (!empty($urls)) {
                        return implode(',', $urls);
                    }
                }
            }
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
            'sizes' => [],
            'offers' => $this->buildOffers($data),
            'handle' => $this->generateHandle($title),
            'countryCode' => $this->extractCountryCode($data)
        ];
        
        // Handle multiple variants (COMPLEX_STRUCTURE format) - PRIORITY
        if (isset($data['variants']) && is_array($data['variants']) && !empty($data['variants'])) {
            foreach ($data['variants'] as $variantData) {
                $variant = $this->buildSingleVariant($variantData);
                $ekatraData['variants'][] = $variant;
            }
        }
        // Handle single variant (SIMPLE_SINGLE_VARIANT format)
        elseif (!empty($data['variant_mrp']) && !empty($data['variant_selling_price'])) {
            $variant = $this->buildSingleVariant($data);
            $ekatraData['variants'][] = $variant;
        }
        // Handle minimal format - create default variant if no variant data
        else {
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
        
        // Try to extract discount from various fields
        $discountValue = $this->findValueByFields($data, ['discount', 'discount_percent', 'discountPercentage', 'discountAmount']);
        $discountLabelValue = $this->findValueByFields($data, ['discountLabel', 'discount_label', 'discountText', 'discount_description', 'offer_text']);
        
        // Try to extract quantity from various fields
        $quantity = $this->findValueByFields($data, ['variant_quantity', 'quantity', 'stock_quantity', 'inventory_quantity']) ?? 0;
        
        // If no price found, use 0
        $price = $price ?: 0;
        $mrp = $mrp ?: $price;
        
        // Always calculate percentage for display purposes
        if ($mrp > 0 && $price < $mrp) {
            $calculatedDiscount = (($mrp - $price) / $mrp) * 100;
            $calculatedPercentage = round($calculatedDiscount, 2, PHP_ROUND_HALF_UP);
        } else {
            $calculatedPercentage = 0.0;
        }
        
        // Determine discount and discountLabel based on input
        if ($discountValue !== null) {
            if (is_numeric($discountValue)) {
                // Numeric discount provided - use as percentage
                $discount = (float) $discountValue;
                $discountLabel = $discountLabelValue ? (string) $discountLabelValue : null;
            } else {
                // String discount provided - use as label, keep calculated percentage
                $discount = $calculatedPercentage;
                $discountLabel = (string) $discountValue;
            }
        } else {
            // No discount provided - use calculated percentage
            $discount = $calculatedPercentage;
            $discountLabel = $discountLabelValue ? (string) $discountLabelValue : null;
        }
        
        return [
            'id' => $variantId,
            'color' => 'unknown',
            'variations' => [
                [
                    'sizeId' => $sizeId,
                    'mrp' => (string) $mrp,
                    'sellingPrice' => (string) $price,
                    'discount' => $discount,
                    'discountLabel' => $discountLabel,
                    'availability' => $quantity > 0,
                    'quantity' => (int) $quantity,
                    'size' => 'freestyle',
                    'variantId' => $variantId
                ]
            ],
            'weight' => 0,
            'thumbnail' => $this->extractThumbnail($data),
            'mediaList' => $this->buildMediaList($data)
        ];
    }

    /**
     * Build single variant
     */
    private function buildSingleVariant($data): array
    {
        $variantId = $this->generateId();
        $color = $data['color'] ?? 'unknown';
        $mrp = $data['variant_mrp'] ?? $this->findValueByFields($data, ['mrp', 'compare_at_price', 'regular_price', 'original_price']) ?? 0;
        $sellingPrice = $data['variant_selling_price'] ?? $this->findValueByFields($data, ['selling_price', 'price', 'sale_price']) ?? 0;
        $quantity = $this->findValueByFields($data, ['variant_quantity', 'quantity', 'stock_quantity', 'inventory_quantity']) ?? 0;
        $size = $data['size'] ?? $this->findValueByFields($data, ['size']) ?? 'freestyle';
        
        // Enhanced discount logic: Handle both numeric and string discounts
        $discountValue = $this->findValueByFields($data, ['discount', 'discount_percent', 'discountPercentage', 'discountAmount']);
        $discountLabelValue = $this->findValueByFields($data, ['discountLabel', 'discount_label', 'discountText', 'discount_description', 'offer_text']);
        
        // Always calculate percentage for display purposes
        if ($mrp > 0 && $sellingPrice < $mrp) {
            $calculatedDiscount = (($mrp - $sellingPrice) / $mrp) * 100;
            $calculatedPercentage = round($calculatedDiscount, 2, PHP_ROUND_HALF_UP);
        } else {
            $calculatedPercentage = 0.0;
        }
        
        // Determine discount and discountLabel based on input
        if ($discountValue !== null) {
            if (is_numeric($discountValue)) {
                // Numeric discount provided - use as percentage
                $discount = (float) $discountValue;
                $discountLabel = $discountLabelValue ? (string) $discountLabelValue : null;
            } else {
                // String discount provided - use as label, keep calculated percentage
                $discount = $calculatedPercentage;
                $discountLabel = (string) $discountValue;
            }
        } else {
            // No discount provided - use calculated percentage
            $discount = $calculatedPercentage;
            $discountLabel = $discountLabelValue ? (string) $discountLabelValue : null;
        }
        
        $sizeId = $this->generateId();
        
        return [
            'id' => $variantId,
            'color' => $color,
            'variations' => [
                [
                    'sizeId' => $sizeId,
                    'mrp' => (string) $mrp,
                    'sellingPrice' => (string) $sellingPrice,
                    'discount' => $discount,
                    'discountLabel' => $discountLabel,
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
                    'thumbnailUrl' => $imageUrl,
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
                        'id' => $sizeId,
                        'name' => $sizeName
                    ];
                }
            }
        }
        
        return $sizes;
    }

    /**
     * Extract maxQuantity from data
     * This allows Kirtilals to manually specify quantity limits for certain products
     */
    private function extractMaxQuantity($data): ?int
    {
        $maxQuantity = $this->findValueByFields($data, ['max_quantity', 'maxQuantity', 'max_purchase_quantity', 'quantity_limit']);
        
        if ($maxQuantity !== null && is_numeric($maxQuantity)) {
            return (int) $maxQuantity;
        }
        
        // If no maxQuantity specified, return null (no limit)
        return null;
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

    /**
     * Build offers array
     */
    private function buildOffers($data): array
    {
        // Check if offers are provided in the data
        $offers = $this->findValueByFields($data, ['offers', 'offer_list', 'promotions', 'discounts']);
        
        if ($offers && is_array($offers)) {
            return $offers;
        }
        
        // Return empty array if no offers provided
        return [];
    }

    /**
     * Generate handle from title
     */
    private function generateHandle(string $title): string
    {
        // Convert to lowercase, replace spaces and special chars with hyphens
        $handle = strtolower($title);
        $handle = preg_replace('/[^a-z0-9\s-]/', '', $handle);
        $handle = preg_replace('/[\s-]+/', '-', $handle);
        $handle = trim($handle, '-');
        
        return $handle;
    }

    /**
     * Extract country code
     */
    private function extractCountryCode($data): ?string
    {
        return $this->findValueByFields($data, $this->fieldMappings['countryCode']);
    }
}
