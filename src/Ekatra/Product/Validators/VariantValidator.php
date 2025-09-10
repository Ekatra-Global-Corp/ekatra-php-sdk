<?php

namespace Ekatra\Product\Validators;

use Ekatra\Product\Core\EkatraVariant;

/**
 * VariantValidator
 * 
 * Handles validation of EkatraVariant objects
 */
class VariantValidator
{
    /**
     * Validate variant data
     */
    public function validate(EkatraVariant $variant): array
    {
        $errors = [];
        
        // Required fields
        if (empty($variant->name)) {
            $errors[] = 'Variant name is required';
        }
        
        if ($variant->quantity < 0) {
            $errors[] = 'Quantity must be >= 0';
        }
        
        if ($variant->mrp <= 0) {
            $errors[] = 'MRP must be > 0';
        }
        
        if ($variant->sellingPrice <= 0) {
            $errors[] = 'Selling price must be > 0';
        }
        
        if ($variant->sellingPrice > $variant->mrp) {
            $errors[] = 'Selling price cannot be greater than MRP';
        }
        
        // At least one image is required
        if (empty($variant->images) && empty($variant->mediaList)) {
            $errors[] = 'At least one image is required';
        }
        
        // Validate variations
        if (!empty($variant->variations)) {
            foreach ($variant->variations as $index => $variation) {
                if (!is_array($variation)) {
                    $errors[] = "Variation at index $index must be an array";
                    continue;
                }
                
                if (empty($variation['sizeId'])) {
                    $errors[] = "Variation at index $index must have 'sizeId' field";
                }
                
                if (empty($variation['mrp']) || $variation['mrp'] <= 0) {
                    $errors[] = "Variation at index $index MRP must be > 0";
                }
                
                if (empty($variation['sellingPrice']) || $variation['sellingPrice'] <= 0) {
                    $errors[] = "Variation at index $index selling price must be > 0";
                }
                
                if (isset($variation['sellingPrice']) && isset($variation['mrp']) && $variation['sellingPrice'] > $variation['mrp']) {
                    $errors[] = "Variation at index $index selling price cannot be greater than MRP";
                }
                
                if (isset($variation['quantity']) && $variation['quantity'] < 0) {
                    $errors[] = "Variation at index $index quantity must be >= 0";
                }
            }
        }
        
        // Validate mediaList
        if (!empty($variant->mediaList)) {
            foreach ($variant->mediaList as $index => $media) {
                if (!is_array($media)) {
                    $errors[] = "Media at index $index must be an array";
                    continue;
                }
                
                if (empty($media['mediaType'])) {
                    $errors[] = "Media at index $index must have 'mediaType' field";
                }
                
                if (empty($media['playUrl'])) {
                    $errors[] = "Media at index $index must have 'playUrl' field";
                } elseif (!filter_var($media['playUrl'], FILTER_VALIDATE_URL)) {
                    $errors[] = "Media at index $index playUrl is not a valid URL";
                }
                
                if (empty($media['mimeType'])) {
                    $errors[] = "Media at index $index must have 'mimeType' field";
                }
            }
        }
        
        // Validate images URLs
        foreach ($variant->images as $index => $image) {
            if (!empty($image) && !filter_var($image, FILTER_VALIDATE_URL)) {
                $errors[] = "Image URL at index $index is not valid";
            }
        }
        
        // Validate video URLs
        foreach ($variant->videoUrls as $index => $video) {
            if (!empty($video) && !filter_var($video, FILTER_VALIDATE_URL)) {
                $errors[] = "Video URL at index $index is not valid";
            }
        }
        
        // Validate thumbnail URL
        if (!empty($variant->thumbnail) && !filter_var($variant->thumbnail, FILTER_VALIDATE_URL)) {
            $errors[] = 'Thumbnail URL is not valid';
        }
        
        // Validate discount percentage
        if ($variant->discountPercent !== null && ($variant->discountPercent < 0 || $variant->discountPercent > 100)) {
            $errors[] = 'Discount percentage must be between 0 and 100';
        }
        
        // Validate weight
        if ($variant->weight !== null && $variant->weight <= 0) {
            $errors[] = 'Weight must be > 0';
        }
        
        // Validate variations
        foreach ($variant->variations as $index => $variation) {
            if (!isset($variation['sizeId']) || empty($variation['sizeId'])) {
                $errors[] = "Variation at index $index must have sizeId";
            }
            
            if (!isset($variation['mrp']) || $variation['mrp'] <= 0) {
                $errors[] = "Variation at index $index must have valid MRP";
            }
            
            if (!isset($variation['sellingPrice']) || $variation['sellingPrice'] <= 0) {
                $errors[] = "Variation at index $index must have valid selling price";
            }
            
            if (isset($variation['sellingPrice']) && isset($variation['mrp']) && $variation['sellingPrice'] > $variation['mrp']) {
                $errors[] = "Variation at index $index: selling price cannot be greater than MRP";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
