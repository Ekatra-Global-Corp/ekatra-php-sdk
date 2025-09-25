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
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('validation', $result);
        $this->assertTrue($result['success']);
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
        $this->assertNotNull($result['data']);
        $this->assertArrayHasKey('variants', $result['data']);
        $this->assertCount(1, $result['data']['variants']);
        
        $variant = $result['data']['variants'][0];
        $this->assertArrayHasKey('variations', $variant);
        $this->assertCount(1, $variant['variations']);
        
        $variation = $variant['variations'][0];
        $this->assertArrayHasKey('discount', $variation);
        $this->assertEquals(20.0, $variation['discount']);
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
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('canAutoTransform', $result);
        
        if ($result['success']) {
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
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('canAutoTransform', $result);
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
}