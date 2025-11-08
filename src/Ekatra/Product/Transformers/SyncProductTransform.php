<?php

namespace Ekatra\Product\Transformers;

use Ekatra\Product\ResponseBuilder;
use Ekatra\Product\Exceptions\EkatraValidationException;

/**
 * SyncProductTransform
 * 
 * Transformer for syncing minimal product data to Ekatra format
 * Accepts only productId, title, currency, and imageUrl
 * Returns a standardized structure with default values
 */
class SyncProductTransform
{
    /**
     * Transform minimal product data to Ekatra sync format
     * 
     * @param array $data Must contain: productId, title, currency, imageUrl (in any format)
     * @return array Response with status, data, metadata, message
     */
    public function transformToEkatra($data): array
    {
        // Extract data from nested structures
        $extractedData = $this->extractData($data);
        
        // Validate required fields
        $validation = $this->validateData($extractedData);
        
        if (!$validation['valid']) {
            return ResponseBuilder::validationError($validation, 'Product sync transformation failed');
        }
        
        // Transform to Ekatra format
        $ekatraData = $this->buildEkatraStructure($extractedData);
        
        return ResponseBuilder::success(
            $ekatraData,
            [
                'validation' => $validation,
                'dataType' => 'SYNC_FORMAT',
                'canAutoTransform' => true,
                'manualSetupRequired' => false
            ],
            'Product synced successfully'
        );
    }

    /**
     * Transform minimal product data to Ekatra sync format (data only)
     * 
     * Returns just the product data array, throws exception on validation failure
     * 
     * @param array $data Must contain: productId, title, currency, imageUrl (in any format)
     * @return array Product data structure
     * @throws EkatraValidationException If validation fails
     */
    public function transformToEkatraData($data): array
    {
        // Extract data from nested structures
        $extractedData = $this->extractData($data);
        
        // Validate required fields
        $validation = $this->validateData($extractedData);
        
        if (!$validation['valid']) {
            $errorMessage = !empty($validation['errors']) 
                ? implode(' ', $validation['errors']) 
                : 'Product sync transformation failed';
            
            throw new EkatraValidationException(
                $errorMessage,
                $validation['errors']
            );
        }
        
        // Transform to Ekatra format and return just the data
        return $this->buildEkatraStructure($extractedData);
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
        
        // Handle nested structures like {data: {...}}
        if (isset($data['data']) && is_array($data['data']) && !isset($data['title'])) {
            return $data['data'];
        }
        
        // Handle nested structures like {result: {...}}
        if (isset($data['result']) && is_array($data['result']) && !isset($data['title'])) {
            return $data['result'];
        }
        
        return $data;
    }

    /**
     * Field mapping for flexible field names
     */
    private $fieldMappings = [
        'productId' => [
            'productId', 'product_id', 'id', 'item_id', 'sku', 'productId', 
            'productCode', 'product_code', 'itemId', 'item_id', 'productSKU', 'product_sku'
        ],
        'title' => [
            'title', 'name', 'product_name', 'product_title', 'productName', 
            'item_name', 'itemName', 'productTitle', 'product_title', 'label', 'productLabel'
        ],
        'currency' => [
            'currency', 'currency_code', 'currencyCode', 'currencyCode', 
            'curr', 'curr_code', 'currencyCode', 'currency_symbol'
        ],
        'imageUrl' => [
            'imageUrl', 'image_url', 'image_urls', 'thumbnailUrl', 'thumbnail_url', 
            'images', 'image', 'photo', 'photos', 'picture', 'pictures',
            'thumbnail', 'thumb', 'thumbUrl', 'thumb_url', 'mainImage', 'main_image',
            'primaryImage', 'primary_image', 'featuredImage', 'featured_image',
            'imageUrl', 'imageUrls', 'ImageURLs', 'imageUrls'
        ]
    ];

    /**
     * Validate input data
     */
    private function validateData($data): array
    {
        $errors = [];
        $suggestions = [];
        
        // Required fields with their possible field names
        $required = [
            'productId' => ['Product ID', $this->fieldMappings['productId']],
            'title' => ['Product title', $this->fieldMappings['title']],
            'currency' => ['Currency', $this->fieldMappings['currency']],
            'imageUrl' => ['Image URL', $this->fieldMappings['imageUrl']]
        ];
        
        foreach ($required as $field => $info) {
            list($label, $possibleFields) = $info;
            $found = false;
            
            // Try to find value using flexible field matching
            $value = $this->findValueByFields($data, $possibleFields);
            
            if ($value !== null && $value !== '') {
                $found = true;
            }
            
            if (!$found) {
                $errors[] = "❌ $label is required";
                $suggestions[] = "💡 Add one of these fields: " . implode(', ', array_slice($possibleFields, 0, 5)) . '...';
            }
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
     * Find value by trying multiple field names
     */
    private function findValueByFields($data, $fields)
    {
        foreach ($fields as $field) {
            // Case-insensitive key search
            $value = $this->getValueCaseInsensitive($data, $field);
            
            if ($value !== null && $value !== '') {
                // Handle array of images - extract first valid URL
                if (in_array(strtolower($field), ['images', 'image', 'photos', 'photo', 'pictures', 'picture'])) {
                    return $this->extractImageUrl($value);
                }
                
                // Handle comma-separated image URLs - take first one
                if (is_string($value) && strpos($value, ',') !== false) {
                    $urls = array_map('trim', explode(',', $value));
                    foreach ($urls as $url) {
                        if (!empty($url)) {
                            return $url;
                        }
                    }
                }
                
                return $value;
            }
        }
        return null;
    }

    /**
     * Get value from array with case-insensitive key matching
     */
    private function getValueCaseInsensitive($data, $key)
    {
        // First try exact match
        if (isset($data[$key])) {
            return $data[$key];
        }
        
        // Try case-insensitive match
        $keyLower = strtolower($key);
        foreach ($data as $dataKey => $value) {
            if (strtolower($dataKey) === $keyLower) {
                return $value;
            }
        }
        
        return null;
    }

    /**
     * Extract image URL from various formats
     */
    private function extractImageUrl($value)
    {
        // Handle array of image URLs
        if (is_array($value)) {
            foreach ($value as $item) {
                if (is_string($item) && !empty($item)) {
                    return $item;
                }
                // Handle array of objects with image properties
                if (is_array($item)) {
                    $imageFields = ['url', 'src', 'image', 'imageUrl', 'image_url', 'thumbnail', 'thumbnailUrl'];
                    foreach ($imageFields as $imgField) {
                        if (isset($item[$imgField]) && is_string($item[$imgField]) && !empty($item[$imgField])) {
                            return $item[$imgField];
                        }
                    }
                }
            }
            return null;
        }
        
        // Handle string (single URL or comma-separated)
        if (is_string($value)) {
            if (strpos($value, ',') !== false) {
                $urls = array_map('trim', explode(',', $value));
                return !empty($urls[0]) ? $urls[0] : null;
            }
            return $value;
        }
        
        return null;
    }

    /**
     * Build Ekatra structure for sync format
     */
    private function buildEkatraStructure($data): array
    {
        // Extract values using flexible field mapping
        $productId = (string) $this->findValueByFields($data, $this->fieldMappings['productId']);
        $title = (string) $this->findValueByFields($data, $this->fieldMappings['title']);
        $currency = (string) $this->findValueByFields($data, $this->fieldMappings['currency']);
        $imageUrl = (string) $this->findValueByFields($data, $this->fieldMappings['imageUrl']);
        
        // Build default variant
        $variant = $this->buildDefaultVariant($imageUrl);
        
        // Build sizes from variant
        $sizes = $this->generateSizes($variant);
        
        return [
            'productId' => $productId,
            'title' => $title,
            'currency' => $currency,
            'searchKeywords' => '',
            'specifications' => [],
            'offers' => [
                [
                    'productOfferDetails' => [
                        (object) []
                    ]
                ]
            ],
            'variants' => [$variant],
            'sizes' => $sizes
        ];
    }

    /**
     * Build default variant for sync format
     */
    private function buildDefaultVariant($imageUrl): array
    {
        $variantId = $this->generateId();
        $sizeId = $this->generateId();
        
        return [
            '_id' => $variantId,
            'color' => 'unknown',
            'variations' => [
                [
                    'sizeId' => $sizeId,
                    'mrp' => '0',
                    'sellingPrice' => '0',
                    'discountLabel' => '',
                    'discount' => '0',
                    'availability' => true,
                    'quantity' => 0,
                    'size' => 'freestyle',
                    'variantId' => $variantId
                ]
            ],
            'weight' => 1,
            'thumbnail' => $imageUrl,
            'mediaList' => $this->buildMediaList($imageUrl)
        ];
    }

    /**
     * Build media list from image URL
     */
    private function buildMediaList($imageUrl): array
    {
        if (empty($imageUrl)) {
            return [];
        }
        
        return [
            [
                'mediaType' => 'IMAGE',
                'thumbnailUrl' => $imageUrl,
                'playUrl' => $imageUrl,
                'weight' => 0,
                'duration' => 0,
                'size' => 0
            ]
        ];
    }

    /**
     * Generate sizes array from variant
     */
    private function generateSizes($variant): array
    {
        $sizes = [];
        
        foreach ($variant['variations'] as $variation) {
            $sizes[] = [
                '_id' => $variation['sizeId'],
                'name' => $variation['size']
            ];
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

