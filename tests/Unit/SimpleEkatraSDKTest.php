<?php

namespace Ekatra\Product\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ekatra\Product\EkatraSDK;

class SimpleEkatraSDKTest extends TestCase
{
    public function testSmartTransformProductWithValidData()
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
        $this->assertArrayHasKey('additionalInfo', $result);
        $this->assertEquals('success', $result['status']);
        $this->assertIsArray($result['data']);
        $this->assertEquals('123', $result['data']['productId']);
        $this->assertEquals('Test Product', $result['data']['title']);
    }

    public function testSmartTransformProductWithInvalidData()
    {
        $customerData = [
            'product_id' => '123',
            'title' => 'Test Product'
            // Missing required fields
        ];
        
        $result = EkatraSDK::smartTransformProduct($customerData);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('additionalInfo', $result);
        $this->assertEquals('error', $result['status']);
        $this->assertArrayHasKey('errors', $result['additionalInfo']['validation']);
        $this->assertNotEmpty($result['additionalInfo']['validation']['errors']);
    }

    public function testCanAutoTransformWithValidData()
    {
        $simpleData = [
            'product_id' => '123',
            'title' => 'Test Product',
            'description' => 'Test Description',
            'currency' => 'USD',
            'existing_url' => 'https://example.com/product',
            'keywords' => ['test'],
            'variant_name' => 'Test Variant',
            'variant_mrp' => '100.00',
            'variant_selling_price' => '80.00'
        ];
        
        $this->assertTrue(EkatraSDK::canAutoTransform($simpleData));
    }

    public function testCanAutoTransformWithInvalidData()
    {
        $invalidData = [
            'product_id' => '123',
            'title' => 'Test Product'
            // Missing variant data
        ];
        
        $this->assertFalse(EkatraSDK::canAutoTransform($invalidData));
    }

    public function testGetSupportedFormats()
    {
        $formats = EkatraSDK::getSupportedFormats();
        
        $this->assertIsArray($formats);
        $this->assertNotEmpty($formats);
        $this->assertArrayHasKey('SIMPLE_SINGLE_VARIANT', $formats);
        $this->assertArrayHasKey('COMPLEX_STRUCTURE', $formats);
        $this->assertArrayHasKey('SIMPLE_MULTI_VARIANT', $formats);
    }

    public function testEkatraSDKClassExists()
    {
        $this->assertTrue(class_exists(EkatraSDK::class));
    }

    public function testEkatraSDKHasRequiredMethods()
    {
        $this->assertTrue(method_exists(EkatraSDK::class, 'smartTransformProduct'));
        $this->assertTrue(method_exists(EkatraSDK::class, 'canAutoTransform'));
        $this->assertTrue(method_exists(EkatraSDK::class, 'getSupportedFormats'));
    }
}
