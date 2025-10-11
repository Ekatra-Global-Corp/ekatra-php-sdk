<?php

namespace Ekatra\Product;

/**
 * ResponseBuilder - Single source of truth for SDK response structure
 * 
 * This class ensures all SDK methods return consistent response structures
 * with the same format: {status, data, metadata, message}
 */
class ResponseBuilder
{
    /**
     * Create a success response
     */
    public static function success($data, $metadata = [], $message = 'Product details retrieved successfully'): array
    {
        return [
            'status' => 'success',
            'data' => $data,
            'metadata' => array_merge([
                'sdkVersion' => self::getSdkVersion()
            ], $metadata),
            'message' => $message
        ];
    }

    /**
     * Create an error response
     */
    public static function error($message, $metadata = []): array
    {
        return [
            'status' => 'error',
            'data' => null,
            'metadata' => array_merge([
                'sdkVersion' => self::getSdkVersion()
            ], $metadata),
            'message' => $message
        ];
    }

    /**
     * Create a validation error response
     */
    public static function validationError($validation, $message = 'Product validation failed'): array
    {
        return self::error($message, [
            'validation' => $validation,
            'canAutoTransform' => false,
            'manualSetupRequired' => true,
            'maxQuantity' => null
        ]);
    }

    /**
     * Create a transformation error response
     */
    public static function transformationError($message, $metadata = []): array
    {
        return self::error($message, array_merge([
            'validation' => null,
            'canAutoTransform' => false,
            'manualSetupRequired' => true,
            'maxQuantity' => null
        ], $metadata));
    }

    /**
     * Get SDK version
     */
    private static function getSdkVersion(): string
    {
        // Try to read from composer.json first
        $composerPath = __DIR__ . '/../../../composer.json';
        if (file_exists($composerPath)) {
            try {
                $composer = json_decode(file_get_contents($composerPath), true);
                if (isset($composer['version']) && !empty($composer['version'])) {
                    return $composer['version'];
                }
            } catch (\Exception $e) {
                // Continue to fallback
            }
        }
        
        // Fallback version
        return '2.0.4';
    }
}
