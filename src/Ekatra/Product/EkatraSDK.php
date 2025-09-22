<?php

namespace Ekatra\Product;

use Ekatra\Product\Core\EkatraProduct;
use Ekatra\Product\Core\EkatraVariant;
use Ekatra\Product\Exceptions\EkatraValidationException;
use Ekatra\Product\Transformers\SmartTransformer;
use Ekatra\Product\Transformers\FlexibleSmartTransformer;
use Ekatra\Product\Validators\EducationalValidator;
use Ekatra\Product\Helpers\ManualSetupGuide;

/**
 * EkatraSDK
 * 
 * Main entry point for the Ekatra Product SDK
 * Provides convenient methods for product and variant management
 */
class EkatraSDK
{
    /**
     * Create a new product instance
     */
    public static function product(): EkatraProduct
    {
        return new EkatraProduct();
    }

    /**
     * Create a new variant instance
     */
    public static function variant(): EkatraVariant
    {
        return new EkatraVariant();
    }

    /**
     * Create product from customer data
     */
    public static function productFromData(array $data): EkatraProduct
    {
        return EkatraProduct::fromCustomerData($data);
    }

    /**
     * Create variant from customer data
     */
    public static function variantFromData(array $data): EkatraVariant
    {
        return EkatraVariant::fromCustomerData($data);
    }

    /**
     * Transform customer product data to Ekatra format
     * Returns both success status and data
     */
    public static function transformProduct(array $customerData): array
    {
        try {
            $product = EkatraProduct::fromCustomerData($customerData);
            return $product->toEkatraFormatWithValidation();
        } catch (EkatraValidationException $e) {
            return [
                'success' => false,
                'data' => null,
                'validation' => [
                    'valid' => false,
                    'errors' => $e->getErrors()
                ],
                'error' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'validation' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Transform customer variant data to Ekatra format
     * Returns both success status and data
     */
    public static function transformVariant(array $customerData): array
    {
        try {
            $variant = EkatraVariant::fromCustomerData($customerData);
            return $variant->toEkatraFormatWithValidation();
        } catch (EkatraValidationException $e) {
            return [
                'success' => false,
                'data' => null,
                'validation' => [
                    'valid' => false,
                    'errors' => $e->getErrors()
                ],
                'error' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'validation' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate customer product data
     * Returns validation result without transformation
     */
    public static function validateProduct(array $customerData): array
    {
        try {
            $product = EkatraProduct::fromCustomerData($customerData);
            return $product->validate();
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => ['Validation error: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Validate customer variant data
     * Returns validation result without transformation
     */
    public static function validateVariant(array $customerData): array
    {
        try {
            $variant = EkatraVariant::fromCustomerData($customerData);
            return $variant->validate();
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => ['Validation error: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Get SDK version
     */
    public static function version(): string
    {
        return '1.0.0';
    }

    /**
     * Get supported field mappings
     */
    public static function getFieldMappings(): array
    {
        return [
            'product' => [
                'productId' => ['id', 'sku', 'product_id', 'item_id', 'product_code'],
                'title' => ['name', 'title', 'productName', 'product_name', 'item_name'],
                'description' => ['desc', 'description', 'details', 'summary', 'content'],
                'existingUrl' => ['url', 'productUrl', 'product_url', 'existing_url', 'link'],
                'keywords' => ['keywords', 'searchKeywords', 'search_keywords', 'tags'],
            ],
            'variant' => [
                'name' => ['name', 'title', 'variant_name', 'variantName', 'item_name'],
                'color' => ['color', 'colour', 'variant_color', 'variantColor', 'item_color'],
                'size' => ['size', 'variant_size', 'variantSize', 'item_size'],
                'quantity' => ['quantity', 'stock', 'available', 'inventory', 'qty'],
                'mrp' => ['mrp', 'originalPrice', 'listPrice', 'price_original', 'original_price'],
                'sellingPrice' => ['price', 'salePrice', 'current_price', 'sale_price'],
            ],
        ];
    }

    /**
     * Smart transform product with educational validation
     */
    public static function smartTransformProduct(array $customerData): array
    {
        $smartTransformer = new SmartTransformer();
        $educationalValidator = new EducationalValidator();
        
        // First validate with educational guidance
        $validation = $educationalValidator->validateWithGuidance($customerData);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'data' => null,
                'validation' => $validation,
                'dataType' => $validation['dataType'],
                'canAutoTransform' => $validation['canAutoTransform'],
                'manualSetupRequired' => $validation['manualSetupRequired']
            ];
        }
        
        // Detect data type and transform accordingly
        $dataType = $smartTransformer->detectDataType($customerData);
        
        try {
            switch ($dataType) {
                case 'SIMPLE_SINGLE_VARIANT':
                    $transformedData = $smartTransformer->transformSimpleToComplex($customerData);
                    break;
                case 'SIMPLE_MULTI_VARIANT':
                    $transformedData = $smartTransformer->transformSimpleVariantsToComplex($customerData);
                    break;
                case 'COMPLEX_STRUCTURE':
                    $transformedData = $smartTransformer->transformComplexToEkatra($customerData);
                    break;
                case 'MIXED_STRUCTURE':
                    $transformedData = $smartTransformer->transformMixedToEkatra($customerData);
                    break;
                default:
                    throw new \Exception("Unknown data type: $dataType");
            }
            
            return [
                'success' => true,
                'data' => $transformedData,
                'validation' => $validation,
                'dataType' => $dataType,
                'autoTransformed' => in_array($dataType, ['SIMPLE_SINGLE_VARIANT', 'SIMPLE_MULTI_VARIANT'])
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'error' => $e->getMessage(),
                'validation' => $validation,
                'dataType' => $dataType
            ];
        }
    }

    /**
     * Get manual setup guide
     */
    public static function getManualSetupGuide(): array
    {
        $guide = new ManualSetupGuide();
        return $guide->getManualSetupInstructions();
    }

    /**
     * Get data structure examples
     */
    public static function getDataStructureExamples(): array
    {
        $guide = new ManualSetupGuide();
        return $guide->getDataStructureExamples();
    }

    /**
     * Get code examples
     */
    public static function getCodeExamples(): array
    {
        $guide = new ManualSetupGuide();
        return $guide->getCodeExamples();
    }

    /**
     * Get best practices
     */
    public static function getBestPractices(): array
    {
        $guide = new ManualSetupGuide();
        return $guide->getBestPractices();
    }

    /**
     * Get troubleshooting guide
     */
    public static function getTroubleshootingGuide(): array
    {
        $guide = new ManualSetupGuide();
        return $guide->getTroubleshootingGuide();
    }

    /**
     * Get educational validation
     */
    public static function getEducationalValidation(array $data): array
    {
        $validator = new EducationalValidator();
        return $validator->validateWithGuidance($data);
    }

    /**
     * Check if data can be auto-transformed
     */
    public static function canAutoTransform(array $data): bool
    {
        $validator = new EducationalValidator();
        return $validator->canAutoTransform($data);
    }

    /**
     * Get supported data formats
     */
    public static function getSupportedFormats(): array
    {
        $validator = new EducationalValidator();
        return $validator->getSupportedFormats();
    }

    // ========================================
    // NEW FLEXIBLE TRANSFORMATION METHODS
    // ========================================

    /**
     * Smart transform product with flexible field mapping (RECOMMENDED)
     * 
     * This method handles various API response formats including:
     * - Shopify format (variants array, images array)
     * - WooCommerce format (tags array, pricing fields)
     * - Magento format (custom_attributes)
     * - Generic e-commerce formats
     * - Minimal formats (just id, name, price)
     * 
     * @param array $customerData The customer's product data in any format
     * @return array Transformation result with success/error status
     */
    public static function smartTransformProductFlexible(array $customerData): array
    {
        $flexibleTransformer = new FlexibleSmartTransformer();
        return $flexibleTransformer->transformToEkatra($customerData);
    }

    /**
     * Transform product with flexible field mapping (ALIAS)
     * 
     * This is an alias for smartTransformProductFlexible() for convenience.
     * Use this method for maximum compatibility with different API formats.
     * 
     * @param array $customerData The customer's product data in any format
     * @return array Transformation result with success/error status
     */
    public static function transformProductFlexible(array $customerData): array
    {
        return self::smartTransformProductFlexible($customerData);
    }

    /**
     * Check if data can be auto-transformed with flexible transformer
     * 
     * @param array $customerData The customer's product data
     * @return bool True if data can be auto-transformed
     */
    public static function canAutoTransformFlexible(array $customerData): bool
    {
        $flexibleTransformer = new FlexibleSmartTransformer();
        $result = $flexibleTransformer->transformToEkatra($customerData);
        return $result['success'] && $result['canAutoTransform'];
    }

    /**
     * Get supported API formats for flexible transformer
     * 
     * @return array List of supported API formats
     */
    public static function getSupportedApiFormats(): array
    {
        return [
            'shopify' => 'Shopify API format (variants array, images array)',
            'woocommerce' => 'WooCommerce API format (tags array, pricing)',
            'magento' => 'Magento API format (custom_attributes)',
            'generic' => 'Generic e-commerce format',
            'minimal' => 'Minimal format (id, name, price only)'
        ];
    }
}
