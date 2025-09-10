# Ekatra Product SDK - Customer Testing Guide

## 🚀 Quick Start Testing

### 1. Install the SDK
```bash
composer require ekatra/product-sdk
```

### 2. Run the Test Script
```bash
php test_customer_sdk.php
```

### 3. Expected Output
```
🧪 Ekatra Product SDK - Customer Testing Tool
=============================================

1. Testing Simple Single Variant Data...
✅ Simple data transformation: SUCCESS
   - Product ID: PROD001
   - Title: Test Product
   - Variants: 1
   - Variations: 1
   - Media List: 2

2. Testing Complex Data Structure...
✅ Complex data transformation: SUCCESS
   - Product ID: PROD002
   - Handle: complex-product
   - Categories: electronics, gadgets
   - Variants: 1
   - Total Variations: 2

3. Testing Invalid Data Handling...
✅ Invalid data handling: SUCCESS (caught errors)
   Errors found: 3
   - Product ID is required
   - Currency is required
   - At least one variant is required

4. Testing Manual Setup...
✅ Manual setup: SUCCESS
   - Product ID: MANUAL001
   - Handle: manual-product
   - Variants: 1
   - Variations: 1

🎉 Testing Complete!
===================
✅ The Ekatra Product SDK is working correctly!
✅ Auto-transformation: WORKING
✅ Complex data handling: WORKING
✅ Error handling: WORKING
✅ Manual setup: WORKING
```

## 📋 Test Scenarios

### Scenario 1: Simple Data (Auto-Transformation)
**Your Data:**
```json
{
  "product_id": "PROD001",
  "title": "My Product",
  "variant_name": "Red",
  "variant_mrp": 1000,
  "variant_selling_price": 800,
  "image_urls": "url1,url2,url3"
}
```

**Expected Result:**
- ✅ Auto-generates `variations` array
- ✅ Auto-generates `mediaList` from images
- ✅ Auto-generates `sizes` array
- ✅ Maps all fields correctly

### Scenario 2: Complex Data (Direct Mapping)
**Your Data:**
```json
{
  "product_id": "PROD002",
  "title": "Complex Product",
  "variants": [
    {
      "name": "Blue Variant",
      "variations": [
        {
          "sizeId": "size-s",
          "mrp": 1200,
          "sellingPrice": 1000,
          "availability": true,
          "quantity": 5,
          "size": "Small"
        }
      ],
      "mediaList": [
        {
          "mediaType": "IMAGE",
          "playUrl": "https://example.com/image.jpg",
          "mimeType": "image/jpeg"
        }
      ]
    }
  ]
}
```

**Expected Result:**
- ✅ Maps directly to Ekatra format
- ✅ Preserves all existing structure
- ✅ No auto-transformation needed

### Scenario 3: Invalid Data (Error Handling)
**Your Data:**
```json
{
  "title": "Invalid Product"
  // Missing required fields
}
```

**Expected Result:**
- ❌ Clear error messages
- 💡 Suggestions for fixes
- 📚 Manual setup instructions

## 🛠️ Manual Setup Testing

### Step 1: Create Product
```php
$product = EkatraSDK::product();
$product->setBasicInfo("PROD001", "My Product", "Description", "USD");
$product->setHandle("my-product");
$product->addCategory("electronics");
```

### Step 2: Create Variant
```php
$variant = EkatraSDK::variant();
$variant->setBasicInfo("Red Variant", 10, 1000, 800);
$variant->setColor("red");
```

### Step 3: Add Variations
```php
$variant->addVariation([
    "sizeId" => "size-s",
    "mrp" => 1000,
    "sellingPrice" => 800,
    "availability" => true,
    "quantity" => 10,
    "size" => "Small",
    "variantId" => "var-001"
]);
```

### Step 4: Add Media
```php
$variant->addMedia([
    "mediaType" => "IMAGE",
    "playUrl" => "https://example.com/image.jpg",
    "mimeType" => "image/jpeg",
    "playerTypeEnum" => "IMAGE"
]);
```

### Step 5: Combine and Transform
```php
$product->addVariant($variant);
$product->addSize("size-s", "Small");

$result = $product->toEkatraFormatWithValidation();
if ($result['success']) {
    echo json_encode($result['data'], JSON_PRETTY_PRINT);
}
```

## 🔍 Validation Testing

### Test Validation Results
```php
$validation = EkatraSDK::validateProduct($yourData);
if (!$validation['valid']) {
    echo "❌ Validation failed:\n";
    foreach ($validation['errors'] as $error) {
        echo "- " . $error . "\n";
    }
    echo "\n💡 Suggestions:\n";
    foreach ($validation['suggestions'] as $suggestion) {
        echo "- " . $suggestion . "\n";
    }
}
```

## 📊 Performance Testing

### Test with Large Data
```php
$largeData = [
    "product_id" => "PERF001",
    "title" => "Performance Test Product",
    "variants" => []
];

// Add 100 variants
for ($i = 0; $i < 100; $i++) {
    $largeData['variants'][] = [
        "name" => "Variant $i",
        "variations" => [
            [
                "sizeId" => "size-$i",
                "mrp" => 1000 + $i,
                "sellingPrice" => 800 + $i,
                "availability" => true,
                "quantity" => 10,
                "size" => "Size $i"
            ]
        ]
    ];
}

$start = microtime(true);
$result = EkatraSDK::transformProduct($largeData);
$end = microtime(true);

echo "⏱️ Performance: " . ($end - $start) . " seconds for 100 variants\n";
```

## 🐛 Debugging

### Enable Debug Mode
```php
// Set debug mode for detailed output
EkatraSDK::setDebugMode(true);

$result = EkatraSDK::transformProduct($yourData);
// Will show detailed transformation steps
```

### Check Transformation Steps
```php
$steps = EkatraSDK::getTransformationSteps($yourData);
foreach ($steps as $step) {
    echo "Step: " . $step['action'] . "\n";
    echo "Input: " . json_encode($step['input']) . "\n";
    echo "Output: " . json_encode($step['output']) . "\n\n";
}
```

## ✅ Success Criteria

Your SDK is working correctly if:

1. **✅ Simple data auto-transforms** to complex structure
2. **✅ Complex data maps directly** without issues
3. **✅ Invalid data shows clear errors** with suggestions
4. **✅ Manual setup works** for custom requirements
5. **✅ Validation catches** missing required fields
6. **✅ Performance is acceptable** for your data size

## 🆘 Troubleshooting

### Common Issues:

1. **"Class not found" errors**
   - Run `composer install`
   - Check autoloader configuration

2. **"Validation failed" errors**
   - Check required fields are present
   - Use validation suggestions to fix issues

3. **"Auto-transformation failed" errors**
   - Check data structure matches expected format
   - Use manual setup for complex requirements

4. **Performance issues**
   - Check data size and complexity
   - Consider batch processing for large datasets

## 📞 Support

If you encounter issues:

1. **Check this guide** for common solutions
2. **Run the test script** to verify SDK functionality
3. **Use debug mode** to see detailed transformation steps
4. **Contact support** with specific error messages and data examples

---

**🎉 Happy Testing! The Ekatra Product SDK is designed to make your integration as smooth as possible.**
