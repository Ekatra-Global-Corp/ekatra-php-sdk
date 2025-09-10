<?php

namespace Ekatra\Product\Validators;

use Ekatra\Product\Core\EkatraProduct;

/**
 * ProductValidator
 * 
 * Handles validation of EkatraProduct objects
 */
class ProductValidator
{
    /**
     * Validate product data
     */
    public function validate(EkatraProduct $product): array
    {
        $errors = [];
        
        // Required fields
        if (empty($product->productId)) {
            $errors[] = 'Product ID is required';
        }
        
        if (empty($product->title)) {
            $errors[] = 'Title is required';
        }
        
        if (empty($product->description)) {
            $errors[] = 'Description is required';
        }
        
        if (empty($product->existingUrl)) {
            $errors[] = 'Existing URL is required';
        }
        
        if (empty($product->keywords)) {
            $errors[] = 'At least one keyword is required';
        }
        
        if (empty($product->variants)) {
            $errors[] = 'At least one variant is required';
        }
        
        // Validate currency
        $validCurrencies = ['INR', 'USD', 'EUR', 'GBP'];
        if (!in_array($product->currency, $validCurrencies)) {
            $errors[] = 'Currency must be one of: ' . implode(', ', $validCurrencies);
        }
        
        // Validate new fields
        if (!empty($product->handle) && !is_string($product->handle)) {
            $errors[] = 'Handle must be a string';
        }
        
        if (!empty($product->categories) && !is_array($product->categories)) {
            $errors[] = 'Categories must be an array';
        }
        
        if (!empty($product->tags) && !is_array($product->tags)) {
            $errors[] = 'Tags must be an array';
        }
        
        if (!empty($product->countryCode) && !is_string($product->countryCode)) {
            $errors[] = 'Country code must be a string';
        }
        
        // Validate country code format
        if (!empty($product->countryCode) && strlen($product->countryCode) !== 2) {
            $errors[] = 'Country code must be a 2-letter ISO code';
        }
        
        // Validate existing URL
        if (!empty($product->existingUrl) && !filter_var($product->existingUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'Existing URL is not valid';
        }
        
        // Validate keywords array
        if (!is_array($product->keywords)) {
            $errors[] = 'Keywords must be an array';
        }
        
        // Validate specifications
        if (!empty($product->specifications) && !is_array($product->specifications)) {
            $errors[] = 'Specifications must be an array';
        }
        
        // Validate offers
        if (!empty($product->offers) && !is_array($product->offers)) {
            $errors[] = 'Offers must be an array';
        }
        
        // Validate sizes
        if (!empty($product->sizes) && !is_array($product->sizes)) {
            $errors[] = 'Sizes must be an array';
        }
        
        // Validate supported currency
        if (!empty($product->supportedCurrency) && !is_array($product->supportedCurrency)) {
            $errors[] = 'Supported currency must be an array';
        }
        
        // Validate aspect ratio format
        if (!empty($product->aspectRatio) && !preg_match('/^\d+:\d+$/', $product->aspectRatio)) {
            $errors[] = 'Aspect ratio must be in format "width:height" (e.g., "16:9")';
        }
        
        // Validate all variants
        foreach ($product->variants as $index => $variant) {
            if ($variant instanceof \Ekatra\Product\Core\EkatraVariant) {
                $variantValidation = $variant->validate();
                if (!$variantValidation['valid']) {
                    $errors[] = "Variant $index: " . implode(', ', $variantValidation['errors']);
                }
            } else {
                $errors[] = "Variant $index: Invalid variant object";
            }
        }
        
        // Validate specifications structure
        if (!empty($product->specifications)) {
            foreach ($product->specifications as $index => $spec) {
                if (!is_array($spec) || !isset($spec['key']) || !isset($spec['value'])) {
                    $errors[] = "Specification at index $index must have 'key' and 'value' fields";
                }
            }
        }
        
        // Validate offers structure
        if (!empty($product->offers)) {
            foreach ($product->offers as $index => $offer) {
                if (!is_array($offer) || !isset($offer['title'])) {
                    $errors[] = "Offer at index $index must have 'title' field";
                }
                
                if (isset($offer['productOfferDetails']) && !is_array($offer['productOfferDetails'])) {
                    $errors[] = "Offer at index $index: productOfferDetails must be an array";
                }
            }
        }
        
        // Validate sizes structure
        if (!empty($product->sizes)) {
            foreach ($product->sizes as $index => $size) {
                if (!is_array($size) || !isset($size['id']) || !isset($size['name'])) {
                    $errors[] = "Size at index $index must have 'id' and 'name' fields";
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
