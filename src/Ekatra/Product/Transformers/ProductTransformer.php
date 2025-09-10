<?php

namespace Ekatra\Product\Transformers;

use Ekatra\Product\Core\EkatraProduct;

/**
 * ProductTransformer
 * 
 * Handles smart mapping and transformation of product data
 */
class ProductTransformer
{
    /**
     * Field mapping configuration for customer data
     */
    private array $fieldMappings = [
        'productId' => ['productId', 'id', 'sku', 'product_id', 'item_id', 'product_code'],
        'title' => ['title', 'name', 'productName', 'product_name', 'item_name', 'product_title'],
        'description' => ['description', 'desc', 'details', 'summary', 'content', 'product_desc'],
        'moreDetails' => ['moreDetails', 'more_details', 'additional_info', 'extended_description'],
        'currency' => ['currency', 'curr', 'currency_code'],
        'existingUrl' => ['existingUrl', 'url', 'productUrl', 'product_url', 'existing_url', 'link'],
        'keywords' => ['keywords', 'searchKeywords', 'search_keywords', 'tags', 'search_terms'],
        'handle' => ['handle', 'slug', 'product_handle', 'productHandle', 'url_slug'],
        'categories' => ['categories', 'category', 'product_categories', 'productCategories', 'category_list'],
        'tags' => ['tags', 'product_tags', 'productTags', 'keywords', 'tag_list'],
        'countryCode' => ['countryCode', 'country_code', 'country', 'country_code', 'origin_country'],
        'additionalInfo' => ['additionalInfo', 'additional_info', 'metadata', 'extra_info', 'custom_fields'],
        'specification' => ['specification', 'specs', 'product_specs', 'technical_specs'],
        'specifications' => ['specifications', 'specs_list', 'spec_list', 'product_specifications'],
        'offer' => ['offer', 'current_offer', 'active_offer'],
        'offers' => ['offers', 'offer_list', 'promotions', 'discounts'],
        'variants' => ['variants', 'variations', 'product_variants', 'options'],
        'sizes' => ['sizes', 'size_list', 'available_sizes', 'size_options'],
        'supportedCurrency' => ['supportedCurrency', 'supported_currency', 'currencies', 'currency_list'],
        'aspectRatio' => ['aspectRatio', 'aspect_ratio', 'ratio', 'image_ratio'],
        'metadata' => ['metadata', 'meta', 'product_metadata', 'system_info']
    ];

    /**
     * Map customer data to standardized format
     */
    public function mapCustomerData(array $customerProduct): array
    {
        $mappedData = [];
        
        foreach ($this->fieldMappings as $targetField => $sourceFields) {
            $value = $this->findValueByFields($customerProduct, $sourceFields);
            
            if ($value !== null) {
                $mappedData[$targetField] = $this->transformValue($targetField, $value);
            }
        }
        
        return $mappedData;
    }

    /**
     * Find value by checking multiple possible field names
     */
    private function findValueByFields(array $data, array $fields): mixed
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                return $data[$field];
            }
            
            // Check nested fields (e.g., 'product.basic_info.title')
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
    private function getNestedValue(array $data, string $path): mixed
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
    private function transformValue(string $field, mixed $value): mixed
    {
        switch ($field) {
            case 'keywords':
                return $this->transformKeywords($value);
                
            case 'specifications':
                return $this->transformSpecifications($value);
                
            case 'offers':
                return $this->transformOffers($value);
                
            case 'sizes':
                return $this->transformSizes($value);
                
            case 'supportedCurrency':
                return $this->transformSupportedCurrency($value);
                
            case 'additionalInfo':
            case 'metadata':
                return $this->transformAdditionalInfo($value);
                
            case 'variants':
                return $this->transformVariants($value);
                
            default:
                return $value;
        }
    }

    /**
     * Transform keywords
     */
    private function transformKeywords(mixed $value): array
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
     * Transform specifications
     */
    private function transformSpecifications(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        
        $specifications = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $specifications[] = $this->transformSpecification($item);
            }
        }
        
        return $specifications;
    }

    /**
     * Transform individual specification
     */
    private function transformSpecification(array $item): array
    {
        $transformed = [];
        
        // Map common field names
        $fieldMappings = [
            'key' => ['key', 'name', 'title', 'label', 'spec_name'],
            'value' => ['value', 'val', 'description', 'spec_value', 'content']
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
     * Transform offers
     */
    private function transformOffers(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        
        $offers = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $offers[] = $this->transformOffer($item);
            }
        }
        
        return $offers;
    }

    /**
     * Transform individual offer
     */
    private function transformOffer(array $item): array
    {
        $transformed = [];
        
        // Map common field names
        $fieldMappings = [
            'title' => ['title', 'name', 'offer_title', 'promotion_name'],
            'productOfferDetails' => ['productOfferDetails', 'details', 'offer_details', 'description', 'terms']
        ];
        
        foreach ($fieldMappings as $targetField => $sourceFields) {
            $value = $this->findValueByFields($item, $sourceFields);
            if ($value !== null) {
                if ($targetField === 'productOfferDetails' && is_array($value)) {
                    $transformed[$targetField] = $this->transformOfferDetails($value);
                } else {
                    $transformed[$targetField] = $value;
                }
            }
        }
        
        return $transformed;
    }

    /**
     * Transform offer details
     */
    private function transformOfferDetails(array $details): array
    {
        $transformed = [];
        foreach ($details as $item) {
            if (is_array($item)) {
                $transformed[] = $this->transformOfferDetail($item);
            }
        }
        return $transformed;
    }

    /**
     * Transform individual offer detail
     */
    private function transformOfferDetail(array $item): array
    {
        $transformed = [];
        
        // Map common field names
        $fieldMappings = [
            'title' => ['title', 'name', 'code', 'offer_code', 'promo_code'],
            'description' => ['description', 'desc', 'details', 'terms', 'conditions']
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
     * Transform sizes
     */
    private function transformSizes(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        
        $sizes = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $sizes[] = $this->transformSize($item);
            }
        }
        
        return $sizes;
    }

    /**
     * Transform individual size
     */
    private function transformSize(array $item): array
    {
        $transformed = [];
        
        // Map common field names
        $fieldMappings = [
            'id' => ['id', 'size_id', 'sizeId', 'variant_id'],
            'name' => ['name', 'size_name', 'sizeName', 'label', 'display_name']
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
     * Transform supported currency
     */
    private function transformSupportedCurrency(mixed $value): array
    {
        if (is_string($value)) {
            return array_filter(array_map('trim', explode(',', $value)));
        }
        
        if (is_array($value)) {
            return array_filter($value);
        }
        
        return ['INR', 'USD', 'EUR', 'GBP']; // Default
    }

    /**
     * Transform additional info
     */
    private function transformAdditionalInfo(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            // Try to decode JSON
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        return [];
    }

    /**
     * Transform variants
     */
    private function transformVariants(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        
        return $value; // Variants are handled by VariantTransformer
    }

    /**
     * Transform product to Ekatra format
     */
    public function toEkatraFormat(EkatraProduct $product): array
    {
        $data = [
            'productId' => $product->productId,
            'title' => $product->title,
            'handle' => $product->handle,
            'description' => $product->description,
            'moreDetails' => $product->moreDetails,
            'currency' => $product->currency,
            'existingProductUrl' => $product->existingUrl,
            'searchKeywords' => $product->keywords,
            'categories' => $product->categories,
            'tags' => $product->tags,
            'countryCode' => $product->countryCode,
            'aspectRatio' => $product->aspectRatio,
            'specifications' => $product->specifications,
            'offers' => $product->offers,
            'variants' => array_map(function($variant) {
                return $variant instanceof \Ekatra\Product\Core\EkatraVariant 
                    ? $variant->toEkatraFormat() 
                    : $variant;
            }, $product->variants),
            'sizes' => $product->sizes,
            'supportedCurrency' => $product->supportedCurrency,
            'metadata' => $product->metadata
        ];
        
        // Remove null values
        return array_filter($data, function($value) {
            return $value !== null && $value !== [];
        });
    }
}
