<?php

namespace Ekatra\Product\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ekatra\Product\Core\EkatraProduct;
use Ekatra\Product\Core\EkatraVariant;

class LaravelRoutesTest extends TestCase
{
    public function testEkatraProductToEkatraFormatWithValidationReturnsV2Structure()
    {
        $product = new EkatraProduct();
        $product->setBasicInfo('123', 'Test Product', 'Test Description', 'USD');
        
        $result = $product->toEkatraFormatWithValidation();
        
        // Test v2.0.0 structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('additionalInfo', $result);
        $this->assertArrayHasKey('message', $result);
        
        // Should NOT have old v1.0.0 structure
        $this->assertArrayNotHasKey('success', $result);
        
        // Status should be string, not boolean
        $this->assertIsString($result['status']);
        $this->assertContains($result['status'], ['success', 'error']);
    }
    
    public function testEkatraVariantToEkatraFormatWithValidationReturnsV2Structure()
    {
        $variant = new EkatraVariant();
        $variant->setBasicInfo('Test Variant', 5, 100.0, 80.0);
        
        $result = $variant->toEkatraFormatWithValidation();
        
        // Test v2.0.0 structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('additionalInfo', $result);
        $this->assertArrayHasKey('message', $result);
        
        // Should NOT have old v1.0.0 structure
        $this->assertArrayNotHasKey('success', $result);
        
        // Status should be string, not boolean
        $this->assertIsString($result['status']);
        $this->assertContains($result['status'], ['success', 'error']);
    }
    
    public function testCoreClassesReturnConsistentStructure()
    {
        // Test EkatraProduct
        $product = new EkatraProduct();
        $product->setBasicInfo('123', 'Test Product', 'Test Description', 'USD');
        $productResult = $product->toEkatraFormatWithValidation();
        
        // Test EkatraVariant
        $variant = new EkatraVariant();
        $variant->setBasicInfo('Test Variant', 5, 100.0, 80.0);
        $variantResult = $variant->toEkatraFormatWithValidation();
        
        // Both should have same structure
        $this->assertEquals(array_keys($productResult), array_keys($variantResult));
        
        // Both should have v2.0.0 structure
        foreach ([$productResult, $variantResult] as $result) {
            $this->assertArrayHasKey('status', $result);
            $this->assertArrayHasKey('data', $result);
            $this->assertArrayHasKey('additionalInfo', $result);
            $this->assertArrayHasKey('message', $result);
            $this->assertArrayNotHasKey('success', $result);
        }
    }
}
