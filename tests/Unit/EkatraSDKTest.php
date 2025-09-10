<?php

namespace Ekatra\Product\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ekatra\Product\EkatraSDK;
use Ekatra\Product\Core\EkatraProduct;
use Ekatra\Product\Core\EkatraVariant;

class EkatraSDKTest extends TestCase
{
    public function testCanCreateProduct()
    {
        $product = EkatraSDK::product();
        $this->assertInstanceOf(EkatraProduct::class, $product);
    }

    public function testCanCreateVariant()
    {
        $variant = EkatraSDK::variant();
        $this->assertInstanceOf(EkatraVariant::class, $variant);
    }

    public function testCanTransformValidProduct()
    {
        $customerData = [
            'id' => 'PROD001',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'url' => 'https://example.com/product',
            'keywords' => 'test,product',
            'variants' => [
                [
                    'name' => 'Test Variant',
                    'price' => 100,
                    'originalPrice' => 120,
                    'stock' => 10,
                    'color' => 'Red',
                    'size' => 'M',
                    'images' => ['image1.jpg', 'image2.jpg']
                ]
            ]
        ];

        $result = EkatraSDK::transformProduct($customerData);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('PROD001', $result['data']['productId']);
        $this->assertEquals('Test Product', $result['data']['title']);
    }

    public function testHandlesInvalidProduct()
    {
        $invalidData = [
            'id' => '', // Invalid: empty ID
            'name' => 'Test Product',
            'description' => '', // Invalid: empty description
            'variants' => [
                [
                    'name' => 'Test Variant',
                    'price' => -100, // Invalid: negative price
                    'stock' => 10
                ]
            ]
        ];

        $result = EkatraSDK::transformProduct($invalidData);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('validation', $result);
        $this->assertFalse($result['validation']['valid']);
        $this->assertNotEmpty($result['validation']['errors']);
    }

    public function testCanValidateProduct()
    {
        $customerData = [
            'id' => 'PROD001',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'url' => 'https://example.com/product',
            'keywords' => 'test,product',
            'variants' => [
                [
                    'name' => 'Test Variant',
                    'price' => 100,
                    'originalPrice' => 120,
                    'stock' => 10,
                    'images' => ['image1.jpg']
                ]
            ]
        ];

        $validation = EkatraSDK::validateProduct($customerData);
        
        $this->assertArrayHasKey('valid', $validation);
        $this->assertArrayHasKey('errors', $validation);
    }

    public function testCanTransformVariant()
    {
        $variantData = [
            'name' => 'Test Variant',
            'price' => 100,
            'originalPrice' => 120,
            'stock' => 10,
            'color' => 'Red',
            'size' => 'M',
            'images' => ['image1.jpg', 'image2.jpg']
        ];

        $result = EkatraSDK::transformVariant($variantData);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('Test Variant', $result['data']['name']);
        $this->assertEquals(100, $result['data']['sellingPrice']);
    }

    public function testGetFieldMappings()
    {
        $mappings = EkatraSDK::getFieldMappings();
        
        $this->assertArrayHasKey('product', $mappings);
        $this->assertArrayHasKey('variant', $mappings);
        $this->assertArrayHasKey('productId', $mappings['product']);
        $this->assertArrayHasKey('name', $mappings['variant']);
    }

    public function testGetVersion()
    {
        $version = EkatraSDK::version();
        $this->assertIsString($version);
        $this->assertNotEmpty($version);
    }
}
