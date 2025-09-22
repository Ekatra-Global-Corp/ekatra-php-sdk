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
            'currency' => 'USD',
            'variant_mrp' => '100.00',
            'variant_selling_price' => '80.00'
        ];
        
        $result = EkatraSDK::smartTransformProduct($customerData);
        
        $this->assertArrayHasKey('variants', $result);
        $this->assertCount(1, $result['variants']);
        
        $variant = $result['variants'][0];
        $this->assertArrayHasKey('variations', $variant);
        $this->assertCount(1, $variant['variations']);
        
        $variation = $variant['variations'][0];
        $this->assertArrayHasKey('discount', $variation);
        $this->assertEquals(20.0, $variation['discount']);
    }

    public function testGetManualSetupGuide()
    {
        $guide = EkatraSDK::getManualSetupGuide();
        
        $this->assertIsString($guide);
        $this->assertNotEmpty($guide);
        $this->assertStringContainsString('Manual Setup Guide', $guide);
    }

    public function testGetDataStructureExamples()
    {
        $examples = EkatraSDK::getDataStructureExamples();
        
        $this->assertIsString($examples);
        $this->assertNotEmpty($examples);
        $this->assertStringContainsString('Data Structure Examples', $examples);
    }

    public function testGetCodeExamples()
    {
        $examples = EkatraSDK::getCodeExamples();
        
        $this->assertIsString($examples);
        $this->assertNotEmpty($examples);
        $this->assertStringContainsString('Code Examples', $examples);
    }

    public function testGetBestPractices()
    {
        $practices = EkatraSDK::getBestPractices();
        
        $this->assertIsString($practices);
        $this->assertNotEmpty($practices);
        $this->assertStringContainsString('Best Practices', $practices);
    }

    public function testGetTroubleshootingGuide()
    {
        $guide = EkatraSDK::getTroubleshootingGuide();
        
        $this->assertIsString($guide);
        $this->assertNotEmpty($guide);
        $this->assertStringContainsString('Troubleshooting', $guide);
    }

    public function testGetEducationalValidation()
    {
        $validation = EkatraSDK::getEducationalValidation();
        
        $this->assertIsString($validation);
        $this->assertNotEmpty($validation);
        $this->assertStringContainsString('Educational Validation', $validation);
    }

    public function testCanAutoTransform()
    {
        $simpleData = [
            'product_id' => '123',
            'title' => 'Test Product',
            'variant_mrp' => '100.00',
            'variant_selling_price' => '80.00'
        ];
        
        $this->assertTrue(EkatraSDK::canAutoTransform($simpleData));
        
        $complexData = [
            'product_id' => '123',
            'title' => 'Test Product',
            'variants' => [
                [
                    '_id' => 'v1',
                    'color' => 'red',
                    'variations' => [
                        [
                            'sizeId' => 's1',
                            'mrp' => '100.00',
                            'sellingPrice' => '80.00'
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
        $this->assertContains('SIMPLE_SINGLE_VARIANT', $formats);
        $this->assertContains('COMPLEX_STRUCTURE', $formats);
        $this->assertContains('MIXED_STRUCTURE', $formats);
    }
}