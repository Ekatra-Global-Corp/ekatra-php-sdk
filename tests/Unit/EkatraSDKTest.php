<?php

namespace Ekatra\Product\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ekatra\Product\EkatraSDK;

class EkatraSDKTest extends TestCase
{
    public function testSmartTransformProduct()
    {
        $customerData = [
            'product_id' => '123',
            'title' => 'Test Product',
            'description' => 'Test Description',
            'currency' => 'USD',
            'existing_url' => 'https://example.com/product',
            'keywords' => ['test', 'product'],
            'variant_name' => 'Test Variant',
            'variant_mrp' => '100.00',
            'variant_selling_price' => '80.00',
            'image_urls' => 'https://example.com/image.jpg'
        ];
        
        $result = EkatraSDK::smartTransformProduct($customerData);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertEquals('success', $result['status']);
        $this->assertIsArray($result['data']);
        $this->assertEquals('123', $result['data']['productId']);
        $this->assertEquals('Test Product', $result['data']['title']);
    }
    
    public function testDiscountCalculation()
    {
        $customerData = [
            'product_id' => '123',
            'title' => 'Test Product',
            'description' => 'Test description',
            'currency' => 'USD',
            'existing_url' => 'https://example.com',
            'keywords' => 'test,product',
            'variant_name' => 'test',
            'variant_mrp' => '100.00',
            'variant_selling_price' => '80.00'
        ];
        
        $result = EkatraSDK::smartTransformProduct($customerData);
        
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertNotNull($result['data']);
        $this->assertArrayHasKey('variants', $result['data']);
        $this->assertCount(1, $result['data']['variants']);
        
        $variant = $result['data']['variants'][0];
        $this->assertArrayHasKey('variations', $variant);
        $this->assertCount(1, $variant['variations']);
        
        $variation = $variant['variations'][0];
        $this->assertArrayHasKey('discount', $variation);
        $this->assertArrayHasKey('discountLabel', $variation);
        $this->assertEquals(20.0, $variation['discount']);
        $this->assertNull($variation['discountLabel']);
    }

    public function testDiscountLabelScenarios()
    {
        // Test 1: Numeric discount provided
        $customerData1 = [
            'product_id' => '123',
            'title' => 'Test Product',
            'description' => 'Test description',
            'currency' => 'USD',
            'existing_url' => 'https://example.com',
            'keywords' => 'test,product',
            'variant_name' => 'test',
            'variant_mrp' => '100.00',
            'variant_selling_price' => '80.00',
            'discount' => 15.5
        ];
        
        $result1 = EkatraSDK::smartTransformProductFlexible($customerData1);
        $variation1 = $result1['data']['variants'][0]['variations'][0];
        $this->assertEquals(15.5, $variation1['discount']);
        $this->assertNull($variation1['discountLabel']);
        
        // Test 2: String discount provided
        $customerData2 = [
            'product_id' => '123',
            'title' => 'Test Product',
            'description' => 'Test description',
            'currency' => 'USD',
            'existing_url' => 'https://example.com',
            'keywords' => 'test,product',
            'variant_name' => 'test',
            'variant_mrp' => '100.00',
            'variant_selling_price' => '80.00',
            'discount' => '20% OFF on DIA'
        ];
        
        $result2 = EkatraSDK::smartTransformProductFlexible($customerData2);
        $variation2 = $result2['data']['variants'][0]['variations'][0];
        $this->assertEquals(20.0, $variation2['discount']); // Auto-calculated
        $this->assertEquals('20% OFF on DIA', $variation2['discountLabel']);
        
        // Test 3: Both numeric discount and discountLabel provided
        $customerData3 = [
            'product_id' => '123',
            'title' => 'Test Product',
            'description' => 'Test description',
            'currency' => 'USD',
            'existing_url' => 'https://example.com',
            'keywords' => 'test,product',
            'variant_name' => 'test',
            'variant_mrp' => '100.00',
            'variant_selling_price' => '80.00',
            'discount' => 25.0,
            'discountLabel' => 'Special Offer'
        ];
        
        $result3 = EkatraSDK::smartTransformProductFlexible($customerData3);
        $variation3 = $result3['data']['variants'][0]['variations'][0];
        $this->assertEquals(25.0, $variation3['discount']);
        $this->assertEquals('Special Offer', $variation3['discountLabel']);
    }

    public function testGetManualSetupGuide()
    {
        $guide = EkatraSDK::getManualSetupGuide();
        
        $this->assertIsArray($guide);
        $this->assertArrayHasKey('title', $guide);
        $this->assertArrayHasKey('steps', $guide);
        $this->assertStringContainsString('Manual Setup Guide', $guide['title']);
    }

    public function testGetDataStructureExamples()
    {
        $examples = EkatraSDK::getDataStructureExamples();
        
        $this->assertIsArray($examples);
        $this->assertArrayHasKey('title', $examples);
        $this->assertArrayHasKey('examples', $examples);
        $this->assertStringContainsString('Data Structure Examples', $examples['title']);
    }

    public function testGetCodeExamples()
    {
        $examples = EkatraSDK::getCodeExamples();
        
        $this->assertIsArray($examples);
        $this->assertArrayHasKey('title', $examples);
        $this->assertArrayHasKey('examples', $examples);
        $this->assertStringContainsString('Code Examples', $examples['title']);
    }

    public function testGetBestPractices()
    {
        $practices = EkatraSDK::getBestPractices();
        
        $this->assertIsArray($practices);
        $this->assertArrayHasKey('title', $practices);
        $this->assertArrayHasKey('practices', $practices);
        $this->assertStringContainsString('Best Practices', $practices['title']);
    }

    public function testGetTroubleshootingGuide()
    {
        $guide = EkatraSDK::getTroubleshootingGuide();
        
        $this->assertIsArray($guide);
        $this->assertArrayHasKey('title', $guide);
        $this->assertArrayHasKey('issues', $guide);
        $this->assertStringContainsString('Troubleshooting', $guide['title']);
    }

    public function testGetEducationalValidation()
    {
        $testData = ['product_id' => 'TEST123', 'title' => 'Test Product'];
        $validation = EkatraSDK::getEducationalValidation($testData);
        
        $this->assertIsArray($validation);
        $this->assertArrayHasKey('valid', $validation);
        $this->assertArrayHasKey('errors', $validation);
    }

    public function testCanAutoTransform()
    {
        $simpleData = [
            'product_id' => '123',
            'title' => 'Test Product',
            'description' => 'Test description',
            'currency' => 'USD',
            'existing_url' => 'https://example.com',
            'keywords' => 'test,product',
            'variant_name' => 'test',
            'variant_mrp' => '100.00',
            'variant_selling_price' => '80.00'
        ];
        
        $this->assertTrue(EkatraSDK::canAutoTransform($simpleData));
        
        $complexData = [
            'product_id' => '123',
            'title' => 'Test Product',
            'description' => 'Test description',
            'currency' => 'USD',
            'existing_url' => 'https://example.com',
            'keywords' => 'test,product',
            'variants' => [
                [
                    '_id' => 'v1',
                    'color' => 'red',
                    'variations' => [
                        [
                            'sizeId' => 's1',
                            'mrp' => '100.00',
                            'sellingPrice' => '80.00',
                            'availability' => true,
                            'quantity' => 10,
                            'size' => 'M',
                            'variantId' => 'var-1'
                        ]
                    ]
                ]
            ]
        ];
        
        $this->assertTrue(EkatraSDK::canAutoTransform($complexData));
    }

    public function testGetSupportedFormats()
    {
        $formats = EkatraSDK::getSupportedFormats();
        
        $this->assertIsArray($formats);
        $this->assertNotEmpty($formats);
        // Check that it contains expected format keys
        $this->assertArrayHasKey('SIMPLE_SINGLE_VARIANT', $formats);
        $this->assertArrayHasKey('COMPLEX_STRUCTURE', $formats);
        // Check that each format has description and example
        $this->assertArrayHasKey('description', $formats['SIMPLE_SINGLE_VARIANT']);
        $this->assertArrayHasKey('example', $formats['SIMPLE_SINGLE_VARIANT']);
    }

    // ========================================
    // NEW FLEXIBLE TRANSFORMATION TESTS
    // ========================================

    public function testSmartTransformProductFlexible()
    {
        $testData = [
            'id' => 123,
            'name' => 'Test Product',
            'price' => 99.99
        ];
        
        $result = EkatraSDK::smartTransformProductFlexible($testData);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('canAutoTransform', $result['metadata']);
        
        if ($result['status'] === 'success') {
            $this->assertArrayHasKey('productId', $result['data']);
            $this->assertArrayHasKey('title', $result['data']);
            $this->assertArrayHasKey('variants', $result['data']);
        }
    }

    public function testTransformProductFlexible()
    {
        $testData = [
            'id' => 456,
            'name' => 'Flexible Test',
            'price' => 149.99
        ];
        
        $result = EkatraSDK::transformProductFlexible($testData);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('canAutoTransform', $result['metadata']);
    }

    public function testCanAutoTransformFlexible()
    {
        $simpleData = [
            'id' => 789,
            'name' => 'Simple Product',
            'price' => 50.00
        ];
        
        $canTransform = EkatraSDK::canAutoTransformFlexible($simpleData);
        $this->assertIsBool($canTransform);
    }

    public function testGetSupportedApiFormats()
    {
        $formats = EkatraSDK::getSupportedApiFormats();
        
        $this->assertIsArray($formats);
        $this->assertNotEmpty($formats);
        
        // Check for expected API formats
        $expectedFormats = ['shopify', 'woocommerce', 'magento', 'generic', 'minimal'];
        foreach ($expectedFormats as $format) {
            $this->assertArrayHasKey($format, $formats);
            $this->assertIsString($formats[$format]);
        }
    }

    public function testFlexibleMethodsExist()
    {
        $this->assertTrue(method_exists(EkatraSDK::class, 'smartTransformProductFlexible'));
        $this->assertTrue(method_exists(EkatraSDK::class, 'transformProductFlexible'));
        $this->assertTrue(method_exists(EkatraSDK::class, 'canAutoTransformFlexible'));
        $this->assertTrue(method_exists(EkatraSDK::class, 'getSupportedApiFormats'));
    }

    /**
     * Test discount auto-calculation when no discount field is provided
     */
    public function testDiscountAutoCalculation()
    {
        $customerData = [
            'product_id' => 'auto-calc-test',
            'title' => 'Auto Calculate Test',
            'description' => 'Product with no discount field',
            'currency' => 'USD',
            'existing_url' => 'https://example.com',
            'keywords' => 'test,product',
            'variant_name' => 'test',
            'variant_mrp' => 100000,      // ₹1,00,000
            'variant_selling_price' => 80000,  // ₹80,000
            'variant_quantity' => 1,
            'size' => 'freestyle'
            // NO discount field - should auto-calculate
        ];
        
        $result = EkatraSDK::smartTransformProductFlexible($customerData);
        
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('variants', $result['data']);
        $this->assertCount(1, $result['data']['variants']);
        
        $variant = $result['data']['variants'][0];
        $this->assertArrayHasKey('variations', $variant);
        $this->assertCount(1, $variant['variations']);
        
        $variation = $variant['variations'][0];
        $this->assertArrayHasKey('discount', $variation);
        
        // Should auto-calculate: (100000-80000)/100000 * 100 = 20%
        $this->assertEquals(20.0, $variation['discount']);
        $this->assertIsFloat($variation['discount']);
    }

    /**
     * Test discount string preservation when discount field is provided
     */
    public function testDiscountStringPreservation()
    {
        $customerData = [
            'product_id' => 'kirtilals-test',
            'title' => 'Kirtilals Test',
            'description' => 'Product with discount field',
            'currency' => 'USD',
            'existing_url' => 'https://example.com',
            'keywords' => 'test,product',
            'variant_name' => 'test',
            'variant_mrp' => 100000,      // ₹1,00,000
            'variant_selling_price' => 80000,  // ₹80,000
            'variant_quantity' => 1,
            'size' => 'freestyle',
            'discount' => '15% Off on Dia'  // Discount field provided
        ];
        
        $result = EkatraSDK::smartTransformProductFlexible($customerData);
        
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('variants', $result['data']);
        $this->assertCount(1, $result['data']['variants']);
        
        $variant = $result['data']['variants'][0];
        $this->assertArrayHasKey('variations', $variant);
        $this->assertCount(1, $variant['variations']);
        
        $variation = $variant['variations'][0];
        $this->assertArrayHasKey('discount', $variation);
        $this->assertArrayHasKey('discountLabel', $variation);
        
        // Should auto-calculate percentage: (100000-80000)/100000 * 100 = 20%
        $this->assertEquals(20.0, $variation['discount']);
        $this->assertIsFloat($variation['discount']);
        // Should preserve the exact string provided in discountLabel
        $this->assertEquals('15% Off on Dia', $variation['discountLabel']);
        $this->assertIsString($variation['discountLabel']);
    }

    /**
     * Test discount string preservation with different formats
     */
    public function testDiscountStringFormats()
    {
        $testCases = [
            '50% off on VA',
            '25% discount',
            'Upto 30% off',
            'Buy 2 Get 1',
            'Flat ₹100 off',
            '20%',
            '15.5% off special'
        ];
        
        foreach ($testCases as $discountText) {
            $customerData = [
                'product_id' => 'format-test-' . md5($discountText),
                'title' => 'Format Test',
                'description' => 'Testing discount format',
                'currency' => 'USD',
                'existing_url' => 'https://example.com',
                'keywords' => 'test,product',
                'variant_name' => 'test',
                'variant_mrp' => 100000,
                'variant_selling_price' => 80000,
                'variant_quantity' => 1,
                'size' => 'freestyle',
                'discount' => $discountText
            ];
            
            $result = EkatraSDK::smartTransformProductFlexible($customerData);
            
            $variation = $result['data']['variants'][0]['variations'][0];
            
            // Should auto-calculate percentage: (100000-80000)/100000 * 100 = 20%
            $this->assertEquals(20.0, $variation['discount'], "Failed for discount: $discountText");
            $this->assertIsFloat($variation['discount'], "Discount should be float for: $discountText");
            // Should preserve the exact string provided in discountLabel
            $this->assertEquals($discountText, $variation['discountLabel'], "Failed for discountLabel: $discountText");
            $this->assertIsString($variation['discountLabel'], "DiscountLabel should be string for: $discountText");
        }
    }

    /**
     * Test numeric discount conversion to string
     */
    public function testNumericDiscountConversion()
    {
        $customerData = [
            'product_id' => 'numeric-test',
            'title' => 'Numeric Discount Test',
            'description' => 'Product with numeric discount',
            'currency' => 'USD',
            'existing_url' => 'https://example.com',
            'keywords' => 'test,product',
            'variant_name' => 'test',
            'variant_mrp' => 100000,
            'variant_selling_price' => 80000,
            'variant_quantity' => 1,
            'size' => 'freestyle',
            'discount' => 20.0  // Numeric discount
        ];
        
        $result = EkatraSDK::smartTransformProductFlexible($customerData);
        
        $variation = $result['data']['variants'][0]['variations'][0];
        
        // Should keep numeric as float
        $this->assertEquals(20.0, $variation['discount']);
        $this->assertIsFloat($variation['discount']);
        $this->assertNull($variation['discountLabel']);
    }

    /**
     * Test discount logic with zero MRP (no auto-calculation)
     */
    public function testDiscountWithZeroMRP()
    {
        $customerData = [
            'product_id' => 'zero-mrp-test',
            'title' => 'Zero MRP Test',
            'description' => 'Product with zero MRP',
            'currency' => 'USD',
            'existing_url' => 'https://example.com',
            'keywords' => 'test,product',
            'variant_name' => 'test',
            'variant_mrp' => 0,  // Zero MRP
            'variant_selling_price' => 80000,
            'variant_quantity' => 1,
            'size' => 'freestyle'
            // NO discount field
        ];
        
        $result = EkatraSDK::smartTransformProductFlexible($customerData);
        
        $variation = $result['data']['variants'][0]['variations'][0];
        
        // Should default to 0 when no discount and MRP is 0
        $this->assertEquals(0, $variation['discount']);
        $this->assertIsFloat($variation['discount']);
        $this->assertNull($variation['discountLabel']);
    }

    /**
     * Test that products with both MRP and selling price as 0 are accepted
     * This covers the Kirtilals use case where prices are set to 0
     */
    public function testProductWithZeroPricesAccepted()
    {
        $customerData = [
            'product_id' => 20051,
            'title' => 'Multicolor Gemstone Orb Earrings',
            'description' => 'These opulent earrings showcase a vibrant palette of gemstones.',
            'currency' => 'INR',
            'existing_url' => 'https://staging-3.kirtilals.com/42',
            'product_keywords' => 'earrings,studs',
            'variant_name' => 'earrings',
            'variant_quantity' => 0,
            'variant_mrp' => 0.0,  // Zero MRP
            'variant_selling_price' => 0.0,  // Zero selling price
            'image_urls' => 'https://example.com/image.jpg'
        ];
        
        $result = EkatraSDK::smartTransformProductFlexible($customerData);
        
        // Should succeed (not throw "No variant data found" error)
        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('variants', $result['data']);
        $this->assertNotEmpty($result['data']['variants']);
        
        $variation = $result['data']['variants'][0]['variations'][0];
        
        // Should preserve 0 values
        $this->assertEquals(0.0, $variation['mrp']);
        $this->assertEquals(0.0, $variation['sellingPrice']);
        $this->assertIsFloat($variation['mrp']);
        $this->assertIsFloat($variation['sellingPrice']);
    }
}