<?php

namespace Ekatra\Product\Transformers;

use Ekatra\Product\Core\EkatraVariant;

/**
 * VariantTransformer
 * 
 * Handles smart mapping and transformation of variant data
 */
class VariantTransformer
{
    /**
     * Field mapping configuration for customer data
     */
    private array $fieldMappings = [
        'name' => ['name', 'title', 'variant_name', 'variantName', 'item_name'],
        'color' => ['color', 'colour', 'variant_color', 'variantColor', 'item_color'],
        'size' => ['size', 'variant_size', 'variantSize', 'item_size'],
        'quantity' => ['quantity', 'stock', 'available', 'inventory', 'qty'],
        'mrp' => ['mrp', 'originalPrice', 'listPrice', 'price_original', 'original_price'],
        'sellingPrice' => ['sellingPrice', 'price', 'salePrice', 'current_price', 'sale_price'],
        'discountPercent' => ['discountPercent', 'discount', 'discount_percent', 'discount_percentage'],
        'id' => ['id', 'variant_id', 'variantId', 'item_id'],
        'weight' => ['weight', 'item_weight', 'variant_weight'],
        'thumbnail' => ['thumbnail', 'thumb', 'thumbnail_url', 'thumb_url'],
        'images' => ['images', 'image_urls', 'imageUrls', 'photos', 'pictures'],
        'videoUrls' => ['videos', 'videoUrls', 'video_urls', 'video_urls'],
        'mediaList' => ['mediaList', 'media_list', 'media', 'mediaItems'],
        'variations' => ['variations', 'sizes', 'size_variants', 'sizeVariants']
    ];

    /**
     * Map customer data to standardized format
     */
    public function mapCustomerData(array $customerVariant): array
    {
        // Check if this is simple single variant data
        $isSimpleVariant = !isset($customerVariant['variations']) && 
                           !isset($customerVariant['sizes']) && 
                           !isset($customerVariant['size_variants']) &&
                           (isset($customerVariant['variant_name']) || isset($customerVariant['name']));
        
        if ($isSimpleVariant) {
            return $this->mapSimpleVariantToComplex($customerVariant);
        }
        
        // Use existing mapping for complex data
        $mappedData = [];
        
        foreach ($this->fieldMappings as $targetField => $sourceFields) {
            $value = $this->findValueByFields($customerVariant, $sourceFields);
            
            if ($value !== null) {
                $mappedData[$targetField] = $this->transformValue($targetField, $value);
            }
        }
        
        return $mappedData;
    }

    /**
     * Map simple single variant data to complex structure
     */
    private function mapSimpleVariantToComplex(array $customerVariant): array
    {
        $mappedData = [];
        
        // Map basic variant fields
        $mappedData['name'] = $this->findValueByFields($customerVariant, ['name', 'title', 'variant_name', 'variantName']);
        $mappedData['color'] = $this->findValueByFields($customerVariant, ['color', 'colour', 'variant_color']) ?: 'unknown';
        $mappedData['id'] = $this->findValueByFields($customerVariant, ['id', 'variant_id', 'variantId', '_id']);
        $mappedData['weight'] = $this->findValueByFields($customerVariant, ['weight', 'item_weight']);
        $mappedData['thumbnail'] = $this->findValueByFields($customerVariant, ['thumbnail', 'thumb', 'thumbnail_url']);
        
        // Handle images
        $images = $this->findValueByFields($customerVariant, ['images', 'image_urls', 'imageUrls']);
        if ($images) {
            if (is_string($images)) {
                $images = array_filter(array_map('trim', explode(',', $images)));
            }
            $mappedData['images'] = $images;
        }
        
        // Create variations array from single variant data
        $variationId = $mappedData['id'] ?: 'default-' . uniqid();
        $sizeId = $this->generateSizeId(); // Generate proper size ID
        $variation = [
            'sizeId' => $sizeId, // This should reference the _id in sizes array
            'mrp' => (float) $this->findValueByFields($customerVariant, ['mrp', 'variant_mrp', 'originalPrice', 'listPrice']) ?: 0,
            'sellingPrice' => (float) $this->findValueByFields($customerVariant, ['sellingPrice', 'variant_selling_price', 'price', 'salePrice']) ?: 0,
            'availability' => true,
            'quantity' => (int) $this->findValueByFields($customerVariant, ['quantity', 'variant_quantity', 'stock', 'available']) ?: 0,
            'size' => $this->findValueByFields($customerVariant, ['size', 'variant_size', 'item_size']) ?: 'freestyle',
            'variantId' => $variationId
        ];
        
        $mappedData['variations'] = [$variation];
        
        // Create mediaList from images
        if (!empty($mappedData['images'])) {
            $mappedData['mediaList'] = $this->createMediaListFromImages($mappedData['images']);
        }
        
        return $mappedData;
    }

    /**
     * Create mediaList from image URLs
     */
    private function createMediaListFromImages(array $images): array
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
     * Generate a unique size ID using UUID4
     */
    private function generateSizeId(): string
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
     * Find value by checking multiple possible field names
     */
    private function findValueByFields(array $data, array $fields)
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                return $data[$field];
            }
            
            // Check nested fields (e.g., 'variant.name')
            if (strpos($field, '.') !== false) {
                $value = $this->getNestedValue($data, $field);
                if ($value !== null) {
                    return $value;
                }
            }
        }
        
        return null;
    }

    /**
     * Get nested value from array
     */
    private function getNestedValue(array $data, string $path)
    {
        $keys = explode('.', $path);
        $current = $data;
        
        foreach ($keys as $key) {
            if (!is_array($current) || !isset($current[$key])) {
                return null;
            }
            $current = $current[$key];
        }
        
        return $current;
    }

    /**
     * Transform value based on field type
     */
    private function transformValue(string $field, $value)
    {
        switch ($field) {
            case 'images':
            case 'videoUrls':
                return $this->transformMediaArray($value);
                
            case 'mediaList':
                return $this->transformMediaList($value);
                
            case 'variations':
                return $this->transformVariations($value);
                
            case 'quantity':
                return (int) $value;
                
            case 'mrp':
            case 'sellingPrice':
            case 'discountPercent':
            case 'weight':
                return (float) $value;
                
            default:
                return $value;
        }
    }

    /**
     * Transform media array (images/videos)
     */
    private function transformMediaArray($value): array
    {
        if (is_string($value)) {
            // Handle comma-separated strings
            return array_filter(array_map('trim', explode(',', $value)));
        }
        
        if (is_array($value)) {
            return array_filter($value);
        }
        
        return [];
    }

    /**
     * Transform media list
     */
    private function transformMediaList($value): array
    {
        if (!is_array($value)) {
            return [];
        }
        
        $mediaList = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $mediaList[] = $this->transformMediaItem($item);
            }
        }
        
        return $mediaList;
    }

    /**
     * Transform individual media item
     */
    private function transformMediaItem(array $item): array
    {
        $transformed = [];
        
        // Map common field names
        $fieldMappings = [
            'mediaType' => ['mediaType', 'type', 'media_type'],
            'thumbnailUrl' => ['thumbnailUrl', 'thumbnail', 'thumb_url', 'thumbnail_url'],
            'playUrl' => ['playUrl', 'url', 'play_url', 'media_url'],
            'mimeType' => ['mimeType', 'mime', 'mime_type', 'content_type'],
            'playerTypeEnum' => ['playerTypeEnum', 'playerType', 'player_type'],
            'weight' => ['weight', 'order', 'sort_order'],
            'duration' => ['duration', 'length', 'time'],
            'size' => ['size', 'file_size', 'bytes'],
            'aspectRatio' => ['aspectRatio', 'aspect_ratio', 'ratio']
        ];
        
        foreach ($fieldMappings as $targetField => $sourceFields) {
            $value = $this->findValueByFields($item, $sourceFields);
            if ($value !== null) {
                $transformed[$targetField] = $value;
            }
        }
        
        return $transformed;
    }

    /**
     * Transform variations
     */
    private function transformVariations($value): array
    {
        if (!is_array($value)) {
            return [];
        }
        
        $variations = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $variations[] = $this->transformVariation($item);
            }
        }
        
        return $variations;
    }

    /**
     * Transform individual variation
     */
    private function transformVariation(array $item): array
    {
        $transformed = [];
        
        // Map common field names
        $fieldMappings = [
            'sizeId' => ['sizeId', 'size_id', 'id', 'variant_id'],
            'mrp' => ['mrp', 'originalPrice', 'listPrice', 'price_original'],
            'sellingPrice' => ['sellingPrice', 'price', 'salePrice', 'current_price'],
            'discount' => ['discount', 'discount_amount', 'discountAmount'],
            'availability' => ['availability', 'available', 'in_stock', 'inStock'],
            'quantity' => ['quantity', 'stock', 'available', 'qty'],
            'size' => ['size', 'size_name', 'sizeName', 'variant_size']
        ];
        
        foreach ($fieldMappings as $targetField => $sourceFields) {
            $value = $this->findValueByFields($item, $sourceFields);
            if ($value !== null) {
                // Transform boolean values
                if ($targetField === 'availability') {
                    $transformed[$targetField] = (bool) $value;
                } elseif ($targetField === 'discount') {
                    // Store discount as-is (always as string)
                    $transformed[$targetField] = (string) $value;
                } elseif (in_array($targetField, ['mrp', 'sellingPrice', 'quantity'])) {
                    $transformed[$targetField] = (float) $value;
                } else {
                    $transformed[$targetField] = $value;
                }
            }
        }
        
        // Auto-calculate discount if not provided and MRP > SellingPrice
        if (!isset($transformed['discount']) && isset($transformed['mrp']) && isset($transformed['sellingPrice'])) {
            $mrp = (float) $transformed['mrp'];
            $sellingPrice = (float) $transformed['sellingPrice'];
            
            if ($mrp > 0 && $sellingPrice < $mrp) {
                // Use HALF_UP rounding (same as Java BigDecimal)
                $discount = (($mrp - $sellingPrice) / $mrp) * 100;
                $transformed['discount'] = (string) round($discount, 2, PHP_ROUND_HALF_UP);
            }
        }
        
        return $transformed;
    }

    /**
     * Transform variant to Ekatra format
     */
    public function toEkatraFormat(EkatraVariant $variant): array
    {
        $data = [
            'id' => $variant->id,
            'name' => $variant->name,
            'color' => $variant->color,
            'size' => $variant->size,
            'quantity' => $variant->quantity,
            'mrp' => $variant->mrp,
            'sellingPrice' => $variant->sellingPrice,
            'discountPercent' => $variant->discountPercent,
            'videoUrls' => array_values(array_filter($variant->videoUrls)),
            'images' => array_values(array_filter($variant->images)),
            'weight' => $variant->weight,
            'thumbnail' => $variant->thumbnail,
            'mediaList' => $variant->mediaList,
            'variations' => $variant->variations
        ];
        
        // Remove null values
        return array_filter($data, function($value) {
            return $value !== null;
        });
    }
}
