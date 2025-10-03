# Ekatra Product SDK for PHP

A powerful PHP SDK for transforming your product data into the standardized Ekatra format. This SDK allows you to easily convert your existing product data structures into the Ekatra product format, making it simple to integrate with Ekatra's ecosystem.

## Features

- 🚀 **Smart Auto-Transformation** - Automatically converts simple data to complex Ekatra structure
- 🧠 **Intelligent Detection** - Detects data complexity and applies appropriate transformation
- 🔄 **Robust Field Mapping** - Maps 50+ field variations across different naming conventions
- ✅ **Educational Validation** - Clear error messages with actionable suggestions
- 🛠️ **Manual Setup Guide** - Step-by-step instructions for complex requirements
- 🎯 **Laravel Integration** - Full Laravel support with facades, commands, and service providers
- 🧪 **Customer Testing Tools** - Easy verification tools for customers
- 📚 **Comprehensive Documentation** - Complete guides, examples, and troubleshooting

## Installation

### Via Composer

```bash
composer require ekatra/product-sdk
```

### Laravel Integration

The SDK automatically registers with Laravel. If you're using Laravel 5.5+, the service provider will be auto-discovered.

For older Laravel versions, add the service provider to `config/app.php`:

```php
'providers' => [
    // ...
    Ekatra\Product\Laravel\ServiceProvider::class,
],
```

## Quick Start

### 🚀 Smart Flexible Transformation (v2.0.0 - RECOMMENDED)

```php
use Ekatra\Product\EkatraSDK;

// Your simple product data (any format)
$customerData = [
    'product_id' => 20051,
    'title' => 'Multicolor Gemstone Orb Earrings',
    'description' => 'Beautiful earrings with gemstones',
    'currency' => 'USD',
    'existing_url' => 'https://mystore.com/earrings',
    'product_keywords' => 'earrings,studs',
    'variant_name' => 'earrings',
    'variant_quantity' => 15,
    'variant_mrp' => 2257,
    'variant_selling_price' => 2118,
    'max_quantity' => 1,  // ✨ NEW: Set quantity limit for ready-to-ship products
    'image_urls' => 'url1,url2,url3'
];

// One line transformation with new v2.0.0 structure!
$result = EkatraSDK::smartTransformProductFlexible($customerData);

if ($result['status'] === 'success') {
    // Get your perfectly formatted Ekatra product
    $ekatraProduct = $result['data'];
    $maxQuantity = $result['additionalInfo']['maxQuantity'];
    echo "✅ Auto-transformed successfully!";
    echo $maxQuantity ? "Max quantity: $maxQuantity" : "No quantity limit";
} else {
    // Get clear error guidance
    foreach ($result['additionalInfo']['validation']['errors'] as $error) {
        echo "❌ $error";
    }
}
```

### ⚠️ Legacy Method (DEPRECATED - Will be removed in v3.0.0)

```php
// ❌ DEPRECATED: Use smartTransformProductFlexible() instead
$result = EkatraSDK::smartTransformProduct($customerData);
```

### Manual Setup (For Complex Requirements)

```php
use Ekatra\Product\EkatraSDK;

// Create product manually
$product = EkatraSDK::product();
$product->setBasicInfo("PROD001", "My Product", "Description", "USD");
$product->setUrl("https://mystore.com/product");
$product->setKeywords(["jewelry", "ring"]);

// Create variant with variations
$variant = EkatraSDK::variant();
$variant->setBasicInfo("Gold Ring", 10, 1000, 800);
$variant->setColor("gold");

// Add variation
$variant->addVariation([
    'sizeId' => 'size-s',
    'mrp' => 1000,
    'sellingPrice' => 800,
    'availability' => true,
    'quantity' => 10,
    'size' => 'Small',
    'variantId' => 'var-001'
]);

// Add media
$variant->addMedia([
    'mediaType' => 'IMAGE',
    'playUrl' => 'https://example.com/image.jpg',
    'mimeType' => 'image/jpeg',
    'playerTypeEnum' => 'IMAGE'
]);

$product->addVariant($variant);
$product->addSize("size-s", "Small");

// Transform to Ekatra format
$result = $product->toEkatraFormatWithValidation();
```

### Basic Usage (Legacy)

```php
use Ekatra\Product\EkatraSDK;

// Transform your product data
$customerData = [
    'id' => 'PROD001',
    'name' => 'Silver Ring',
    'description' => 'Beautiful silver ring',
    'url' => 'https://mystore.com/products/ring001',
    'keywords' => 'rings,silver,jewelry',
    'variants' => [
        [
            'name' => 'Silver Ring - Size 6',
            'price' => 850,
            'originalPrice' => 1000,
            'stock' => 25,
            'color' => 'Silver',
            'size' => '6',
            'images' => ['img1.jpg', 'img2.jpg']
        ]
    ]
];

$result = EkatraSDK::transformProduct($customerData);

if ($result['success']) {
    echo "Transformation successful!";
    $ekatraFormat = $result['data'];
} else {
    echo "Transformation failed: " . $result['error'];
}
```

### Laravel Usage

```php
use Ekatra\Product\EkatraSDK;

class ProductController extends Controller
{
    public function getProduct($id)
    {
        $myProduct = Product::findOrFail($id);
        $ekatraProduct = EkatraSDK::productFromData($myProduct->toArray());
        
        return response()->json([
            'success' => true,
            'data' => $ekatraProduct->toEkatraFormat()
        ]);
    }
}
```

## 🚀 Flexible API Transformation (NEW in v1.1.0)

The SDK now supports **flexible field mapping** that works with any API response format! This is the **recommended approach** for maximum compatibility.

### Supported API Formats

- **Shopify**: `{id, title, variants: [...], images: [...]}`
- **WooCommerce**: `{id, name, price, regular_price, tags: [...]}`
- **Magento**: `{id, name, custom_attributes: [...]}`
- **Generic E-commerce**: `{item_id, product_name, price, images: [...]}`
- **Minimal**: `{id, name, price}` (just the basics!)

### Flexible Transformation Methods

#### `smartTransformProductFlexible()` - RECOMMENDED

```php
use Ekatra\Product\EkatraSDK;

// Works with ANY API format!
$result = EkatraSDK::smartTransformProductFlexible($customerData);

if ($result['success']) {
    echo "✅ Transformation successful!";
    echo json_encode($result['data'], JSON_PRETTY_PRINT);
} else {
    echo "❌ Transformation failed:";
    foreach ($result['validation']['errors'] as $error) {
        echo "- " . $error;
    }
}
```

#### `transformProductFlexible()` - ALIAS

```php
// Same as smartTransformProductFlexible() but shorter
$result = EkatraSDK::transformProductFlexible($customerData);
```

### Real-World Examples

#### Shopify API Format
```php
$shopifyData = [
    'id' => 123456789,
    'title' => 'Classic T-Shirt',
    'body_html' => '<p>Comfortable cotton t-shirt</p>',
    'price' => '29.99',
    'compare_at_price' => '39.99',
    'variants' => [
        [
            'id' => 987654321,
            'title' => 'Small / Red',
            'price' => '29.99',
            'compare_at_price' => '39.99',
            'inventory_quantity' => 10
        ]
    ],
    'images' => [
        ['src' => 'https://cdn.shopify.com/tshirt-red.jpg']
    ]
];

$result = EkatraSDK::smartTransformProductFlexible($shopifyData);
// ✅ Works perfectly!
```

#### Minimal Format
```php
$minimalData = [
    'id' => 999,
    'name' => 'Basic Product',
    'price' => 50.00
];

$result = EkatraSDK::smartTransformProductFlexible($minimalData);
// ✅ Works perfectly! Creates default variant automatically
```

### Migration from Old Methods

**⚡ Old way (DEPRECATED):**
```php
$result = EkatraSDK::smartTransformProduct($customerData);
if ($result['success']) { // Old boolean format
    $data = $result['data'];
}
```

**✅ New way (RECOMMENDED):**
```php
$result = EkatraSDK::smartTransformProductFlexible($customerData);
if ($result['status'] === 'success') { // New string format
    $data = $result['data'];
    $validation = $result['additionalInfo']['validation'];
    $maxQuantity = $result['additionalInfo']['maxQuantity'];
}
```

## 🛠️ Response Modification (Option B)

You can easily customize the response for your API needs:

### Basic Response Modification

```php
use Ekatra\Product\EkatraSDK;

$result = EkatraSDK::smartTransformProductFlexible($customerData);

if ($result['status'] === 'success') {
    // Modify the response as needed
    $result['message'] = "Custom success message for API";
    $result['additionalInfo']['maxQuantity'] = 1; // Override quantity limit
    $result['additionalInfo']['customField'] = "Custom value";
    
    return $result; // Return modified response
} else {
    return [
        'status' => 'error',
        'message' => 'Custom error message for API',
        'additionalInfo' => $result['additionalInfo']
    ];
}
```

### Advanced Response Customization

```php
function transformProductForApi($customerData, $maxQuantity = null, $customMessage = null) {
    $result = EkatraSDK::smartTransformProductFlexible($customerData);
    
    // Extract core data
    $data = $result['data'];
    $validation = $result['additionalInfo']['validation'];
    $dataType = $result['additionalInfo']['dataType'];
    
    // Build custom response
    return [
        'status' => 'success',
        'data' => $data,
        'additionalInfo' => [
            'validation' => $validation,
            'dataType' => $dataType,
            'maxQuantity' => $maxQuantity ?? $result['additionalInfo']['maxQuantity'],
            'apiVersion' => '2.0.0',
            'transformedAt' => date('Y-m-d H:i:s'),
            'customField' => 'Your custom value'
        ],
        'message' => $customMessage ?? $result['message']
    ];
}

// Usage
$apiResponse = transformProductForApi($customerData, 1, "Product ready for shipping");
```

### Utility Methods

```php
// Check if data can be auto-transformed
$canTransform = EkatraSDK::canAutoTransformFlexible($customerData);

// Get supported API formats
$formats = EkatraSDK::getSupportedApiFormats();
// Returns: ['shopify' => '...', 'WooCom' => '...', etc.]
```

## API Reference

### Smart Transformation Methods

#### Flexible API Transformation (RECOMMENDED for v1.1.0+)

```php
// NEW: Flexible transformation - works with ANY API format
$result = EkatraSDK::smartTransformProductFlexible($customerData);

// Alias for convenience
$result = EkatraSDK::transformProductFlexible($customerData);

// Check if data can be auto-transformed with flexible transformer
$canTransform = EkatraSDK::canAutoTransformFlexible($customerData);

// Get supported API formats
$formats = EkatraSDK::getSupportedApiFormats();
```

#### Legacy Methods (Still Supported)

```php
// ❌ DEPRECATED: Original smart transformation (legacy)
$result = EkatraSDK::smartTransformProduct($customerData);

// ❌ DEPRECATED: Check if data can be auto-transformed with old transformer
$canTransform = EkatraSDK::canAutoTransform($customerData);

// ❌ DEPRECATED: Get educational validation
$validation = EkatraSDK::getEducationalValidation($customerData);

// ❌ DEPRECATED: Get manual setup guide
$guide = EkatraSDK::getManualSetupGuide();

// ❌ DEPRECATED: Get code examples
$examples = EkatraSDK::getCodeExamples();

// ❌ DEPRECATED: Get troubleshooting guide
$troubleshooting = EkatraSDK::getTroubleshootingGuide();
```

### Core Classes

#### EkatraProduct

The main product class for handling complete product information.

```php
use Ekatra\Product\Core\EkatraProduct;

$product = new EkatraProduct();
$product->setBasicInfo('PROD001', 'Product Title', 'Description', 'INR')
        ->setUrl('https://example.com/product')
        ->setKeywords(['keyword1', 'keyword2'])
        ->addVariant($variant);
```

#### EkatraVariant

Handles individual product variants.

```php
use Ekatra\Product\Core\EkatraVariant;

$variant = new EkatraVariant();
$variant->setBasicInfo('Variant Name', 10, 100, 90)
        ->setAttributes('Red', 'M')
        ->setMedia(['image1.jpg'], ['video1.mp4']);
```

### SDK Methods

#### EkatraSDK::transformProduct(array $data)

Transforms customer product data to Ekatra format.

**Parameters:**
- `$data` (array) - Customer product data

**Returns:**
```php
[
    'success' => true|false,
    'data' => array|null,
    'validation' => [
        'valid' => true|false,
        'errors' => array
    ],
    'error' => string|null
]
```

#### EkatraSDK::validateProduct(array $data)

Validates customer product data without transformation.

**Parameters:**
- `$data` (array) - Customer product data

**Returns:**
```php
[
    'valid' => true|false,
    'errors' => array
]
```

## Field Mapping

The SDK automatically maps various field names to the Ekatra format:

### Product Fields

| Ekatra Field | Mapped From |
|--------------|-------------|
| `productId` | `id`, `sku`, `product_id`, `item_id` |
| `title` | `name`, `title`, `product_name`, `item_name` |
| `description` | `desc`, `description`, `details`, `summary` |
| `existingUrl` | `url`, `productUrl`, `product_url`, `link` |
| `keywords` | `keywords`, `searchKeywords`, `tags` |

### Variant Fields

| Ekatra Field | Mapped From |
|--------------|-------------|
| `name` | `name`, `title`, `variant_name`, `item_name` |
| `color` | `color`, `colour`, `variant_color`, `item_color` |
| `size` | `size`, `variant_size`, `item_size` |
| `quantity` | `quantity`, `stock`, `available`, `inventory` |
| `mrp` | `mrp`, `originalPrice`, `listPrice`, `price_original` |
| `sellingPrice` | `price`, `salePrice`, `current_price`, `sale_price` |

## Testing

The SDK includes comprehensive testing tools for customers.

### Quick Test

```bash
# Test with sample data using the customer guide
php customer_test_guide.md
```

### Customer Testing Guide

The `customer_test_guide.md` file provides comprehensive testing instructions:

1. **Simple Data Test** - Tests auto-transformation
2. **Complex Data Test** - Tests complex structure handling  
3. **Invalid Data Test** - Tests error handling
4. **Manual Setup Test** - Tests manual product creation

### Laravel Testing

```bash
# Test mapping with Artisan command
php artisan ekatra:test-mapping

# Test with custom data file
php artisan ekatra:test-mapping --file=path/to/test-data.json

# Test with JSON string
php artisan ekatra:test-mapping --data='{"id":"PROD001","name":"Test Product"}'

# Test only variants
php artisan ekatra:test-mapping --variant

# Test with validation
php artisan ekatra:test-mapping --validate

# Show formatted output
php artisan ekatra:test-mapping --format
```

### Test Endpoints

The SDK provides test endpoints for easy testing:

```bash
# Test product mapping
POST /ekatra/test/product
Content-Type: application/json

{
    "id": "PROD001",
    "name": "Test Product",
    "description": "Test Description",
    "variants": [...]
}

# Test variant mapping
POST /ekatra/test/variant
Content-Type: application/json

{
    "name": "Test Variant",
    "price": 100,
    "stock": 10
}

# Get sample data
GET /ekatra/test/sample
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=ekatra-config
```

This creates `config/ekatra.php` with customizable settings:

```php
return [
    'default_currency' => 'INR',
    'supported_currencies' => ['INR', 'USD', 'EUR', 'GBP'],
    'validation' => [
        'strict_mode' => false,
        'log_errors' => true,
    ],
    // ... more options
];
```

## Error Handling

The SDK provides flexible error handling:

### Validation Errors

```php
$result = EkatraSDK::transformProduct($data);

if (!$result['success']) {
    if (isset($result['validation']['errors'])) {
        foreach ($result['validation']['errors'] as $error) {
            echo "Validation Error: $error\n";
        }
    }
}
```

### Exceptions

```php
use Ekatra\Product\Exceptions\EkatraValidationException;

try {
    $product = EkatraSDK::productFromData($data);
    $ekatraFormat = $product->toEkatraFormat();
} catch (EkatraValidationException $e) {
    echo "Validation failed: " . $e->getMessage();
    $errors = $e->getErrors();
}
```

## Examples

### Complete Product Example

```php
use Ekatra\Product\EkatraSDK;

$customerData = [
    'id' => 'PROD001',
    'name' => 'Premium Silver Ring',
    'description' => 'High-quality silver ring with premium finish',
    'url' => 'https://mystore.com/products/ring001',
    'keywords' => 'rings,silver,jewelry,premium',
    'currency' => 'INR',
    'specifications' => [
        ['key' => 'Material', 'value' => 'Premium Silver Alloy'],
        ['key' => 'Weight', 'value' => '250g']
    ],
    'offers' => [
        [
            'title' => 'Special Offer',
            'productOfferDetails' => [
                [
                    'title' => 'SAVE20',
                    'description' => '20% off on all silver jewelry'
                ]
            ]
        ]
    ],
    'variants' => [
        [
            'name' => 'Silver Ring - Size 6',
            'price' => 850,
            'originalPrice' => 1000,
            'stock' => 25,
            'color' => 'Silver',
            'size' => '6',
            'images' => ['img1.jpg', 'img2.jpg'],
            'videos' => ['video1.mp4']
        ]
    ]
];

$result = EkatraSDK::transformProduct($customerData);

if ($result['success']) {
    $ekatraFormat = $result['data'];
    // Use the transformed data
} else {
    // Handle errors
    $errors = $result['validation']['errors'] ?? [];
}
```

### Laravel Controller Example

```php
class ProductController extends Controller
{
    public function getProduct($id)
    {
        try {
            $product = Product::findOrFail($id);
            $ekatraProduct = EkatraSDK::productFromData($product->toArray());
            
            return response()->json([
                'success' => true,
                'data' => $ekatraProduct->toEkatraFormat()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Product transformation failed'
            ], 500);
        }
    }
}
```

## Requirements

- PHP 8.1 or higher
- Composer
- Laravel 9+ (for Laravel integration)

## Dependencies

- Guzzle HTTP (for future API features)
- Illuminate Support (for Laravel integration)
- Illuminate Validation (for validation features)

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This SDK is licensed under the MIT License. See the LICENSE file for details.

## Support

For support and questions:

- Email: support@ekatraglobal.com

## Changelog

### Version 1.0.0

- Initial release
- Core product and variant classes
- Smart field mapping
- Comprehensive validation
- Laravel integration
- Testing tools
- Documentation and examples

### Version 1.0.1
- Fixed default size to 'freestyle' for consistency
- Improved sizeId linking between variations and sizes arrays
- Enhanced SmartTransformer size matching logic

### Version 1.0.2
- Fixed discount calculation for simple variant data
- Added missing discount logic to transformSimpleToComplex method
- Ensures all transformation paths calculate discounts correctly

### Version 2.0.0 - V2 API STRUCTURE & QUANTITY FIXES 🚀

#### ✨ New Features
- **Flattened Response Structure**: Moved validation metadata into `additionalInfo` object
- **Status Field Enhancement**: Changed `success: boolean` → `status: string`
- **MaxQuantity Support**: New `maxQuantity` field in `additionalInfo` for quantity limits
- **Media Object Alignment**: Updated `mediaList` → `media`, `mediaType` → `type`, `playUrl` → `url`
- **Thumbnail Enhancement**: Added `thumbnailUrl` field within media objects
- **Quantity Bug Fix**: Fixed issue where `quantity` was showing 0 instead of actual input values

#### 🛠️ Response Structure Changes
- **Old Structure**: `{success: true, data: {...}, validation: {...}}`
- **New Structure**: `{status: "success", data: {...}, additionalInfo: {validation: {...}, maxQuantity: number}}`

#### 🔧 New Methods
- `extractMaxQuantity()` - Extracts quantity limits from input data
- Enhanced `smartTransformProductFlexible()` - Now returns v2.0.0 structure

### Version 1.0.5 - FLEXIBLE API TRANSFORMATION (PREVIOUS)

#### ✨ New Features
- **Flexible API Transformation**: New `smartTransformProductFlexible()` method that works with ANY API format
- **Multi-Platform Support**: Handles Shopify, WooCommerce, Magento, and generic e-commerce formats
- **Smart Field Mapping**: Automatically maps 50+ field variations across different naming conventions
- **Nested Data Extraction**: Handles complex nested structures like `{success: true, product_details: {...}}`
- **Default Variant Creation**: Creates sensible defaults for minimal data formats
- **Backward Compatibility**: All existing methods continue to work unchanged

#### 🔧 New Methods
- `EkatraSDK::smartTransformProductFlexible($data)` - **RECOMMENDED** for new implementations
- `EkatraSDK::transformProductFlexible($data)` - Alias for convenience
- `EkatraSDK::canAutoTransformFlexible($data)` - Check transformation capability
- `EkatraSDK::getSupportedApiFormats()` - List supported API formats

#### 🛠️ Technical Improvements
- **PSR-4 Autoloading**: Fixed autoloader configuration for better compatibility
- **Enhanced Validation**: More flexible validation with smart defaults
- **Better Error Handling**: Clearer error messages and suggestions
- **CI/CD Pipeline**: Added GitHub Actions for automated testing

#### 📚 Documentation
- Updated README with comprehensive examples for all supported formats
- Added migration guide for existing users
- Real-world examples for each API format

#### 🔧 Technical Improvements
- **PSR-4 Autoloading**: Fixed autoloader configuration for better compatibility
  - **Migration**: Run `composer dump-autoload` after updating
  - **Impact**: None - backward compatible, just better autoloading