<?php

namespace Ekatra\Product\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ekatra\Product\EkatraSDK;
use Ekatra\Product\Transformers\SyncProductTransform;
use Ekatra\Product\Exceptions\EkatraValidationException;

class SyncProductTransformTest extends TestCase
{
    public function testSyncProductWithMinimalData()
    {
        $data = [
            'productId' => '6429',
            'title' => 'Floral Diamond Bracelet',
            'currency' => 'INR',
            'imageUrl' => 'https://www.kirtilals.com/images/media/products/6429/500x500/floral-diamond-bracelet-6429-1.jpg'
        ];
        
        $result = EkatraSDK::syncProduct($data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('success', $result['status']);
        
        $productData = $result['data'];
        $this->assertEquals('6429', $productData['productId']);
        $this->assertEquals('Floral Diamond Bracelet', $productData['title']);
        $this->assertEquals('INR', $productData['currency']);
        $this->assertEquals('', $productData['searchKeywords']);
        $this->assertIsArray($productData['specifications']);
        $this->assertEmpty($productData['specifications']);
        $this->assertIsArray($productData['offers']);
        $this->assertNotEmpty($productData['offers']);
        $this->assertArrayHasKey('productOfferDetails', $productData['offers'][0]);
    }

    public function testSyncProductVariantStructure()
    {
        $data = [
            'productId' => '6429',
            'title' => 'Floral Diamond Bracelet',
            'currency' => 'INR',
            'imageUrl' => 'https://www.kirtilals.com/images/media/products/6429/500x500/floral-diamond-bracelet-6429-1.jpg'
        ];
        
        $result = EkatraSDK::syncProduct($data);
        $productData = $result['data'];
        
        $this->assertIsArray($productData['variants']);
        $this->assertCount(1, $productData['variants']);
        
        $variant = $productData['variants'][0];
        $this->assertArrayHasKey('_id', $variant);
        $this->assertEquals('unknown', $variant['color']);
        $this->assertEquals(1, $variant['weight']);
        $this->assertIsString($variant['thumbnail']);
        $this->assertEquals($data['imageUrl'], $variant['thumbnail']);
        
        $this->assertIsArray($variant['variations']);
        $this->assertCount(1, $variant['variations']);
        
        $variation = $variant['variations'][0];
        $this->assertArrayHasKey('sizeId', $variation);
        $this->assertEquals('0', $variation['mrp']);
        $this->assertEquals('0', $variation['sellingPrice']);
        $this->assertEquals('0', $variation['discount']);
        $this->assertEquals('', $variation['discountLabel']);
        $this->assertTrue($variation['availability']);
        $this->assertEquals(0, $variation['quantity']);
        $this->assertEquals('freestyle', $variation['size']);
    }

    public function testSyncProductSizesStructure()
    {
        $data = [
            'productId' => '6429',
            'title' => 'Floral Diamond Bracelet',
            'currency' => 'INR',
            'imageUrl' => 'https://www.kirtilals.com/images/media/products/6429/500x500/floral-diamond-bracelet-6429-1.jpg'
        ];
        
        $result = EkatraSDK::syncProduct($data);
        $productData = $result['data'];
        
        $this->assertIsArray($productData['sizes']);
        $this->assertCount(1, $productData['sizes']);
        
        $size = $productData['sizes'][0];
        $this->assertArrayHasKey('_id', $size);
        $this->assertEquals('freestyle', $size['name']);
    }

    public function testSyncProductMediaList()
    {
        $data = [
            'productId' => '6429',
            'title' => 'Floral Diamond Bracelet',
            'currency' => 'INR',
            'imageUrl' => 'https://www.kirtilals.com/images/media/products/6429/500x500/floral-diamond-bracelet-6429-1.jpg'
        ];
        
        $result = EkatraSDK::syncProduct($data);
        $productData = $result['data'];
        $variant = $productData['variants'][0];
        
        $this->assertIsArray($variant['mediaList']);
        $this->assertCount(1, $variant['mediaList']);
        
        $media = $variant['mediaList'][0];
        $this->assertEquals('IMAGE', $media['mediaType']);
        $this->assertEquals($data['imageUrl'], $media['thumbnailUrl']);
        $this->assertEquals($data['imageUrl'], $media['playUrl']);
        $this->assertEquals(0, $media['weight']);
        $this->assertEquals(0, $media['duration']);
        $this->assertEquals(0, $media['size']);
    }

    public function testSyncProductOffersStructure()
    {
        $data = [
            'productId' => '6429',
            'title' => 'Floral Diamond Bracelet',
            'currency' => 'INR',
            'imageUrl' => 'https://www.kirtilals.com/images/media/products/6429/500x500/floral-diamond-bracelet-6429-1.jpg'
        ];
        
        $result = EkatraSDK::syncProduct($data);
        $productData = $result['data'];
        
        $this->assertIsArray($productData['offers']);
        $this->assertCount(1, $productData['offers']);
        $this->assertArrayHasKey('productOfferDetails', $productData['offers'][0]);
        $this->assertIsArray($productData['offers'][0]['productOfferDetails']);
        $this->assertCount(1, $productData['offers'][0]['productOfferDetails']);
    }

    public function testSyncProductMissingRequiredFields()
    {
        $data = [
            'productId' => '6429',
            'title' => 'Floral Diamond Bracelet'
            // Missing currency and imageUrl
        ];
        
        $result = EkatraSDK::syncProduct($data);
        
        $this->assertEquals('error', $result['status']);
        $this->assertNull($result['data']);
        $this->assertArrayHasKey('validation', $result['metadata']);
        $this->assertFalse($result['metadata']['validation']['valid']);
    }

    public function testSyncProductWithEmptyImageUrl()
    {
        $data = [
            'productId' => '6429',
            'title' => 'Floral Diamond Bracelet',
            'currency' => 'INR',
            'imageUrl' => ''
        ];
        
        $result = EkatraSDK::syncProduct($data);
        
        $this->assertEquals('error', $result['status']);
    }

    public function testSyncProductDirectTransformer()
    {
        $data = [
            'productId' => '6429',
            'title' => 'Floral Diamond Bracelet',
            'currency' => 'INR',
            'imageUrl' => 'https://www.kirtilals.com/images/media/products/6429/500x500/floral-diamond-bracelet-6429-1.jpg'
        ];
        
        $transformer = new SyncProductTransform();
        $result = $transformer->transformToEkatra($data);
        
        $this->assertEquals('success', $result['status']);
        $this->assertIsArray($result['data']);
        $this->assertEquals('6429', $result['data']['productId']);
    }

    public function testSyncProductStringTypes()
    {
        $data = [
            'productId' => '6429',
            'title' => 'Floral Diamond Bracelet',
            'currency' => 'INR',
            'imageUrl' => 'https://www.kirtilals.com/images/media/products/6429/500x500/floral-diamond-bracelet-6429-1.jpg'
        ];
        
        $result = EkatraSDK::syncProduct($data);
        $productData = $result['data'];
        $variation = $productData['variants'][0]['variations'][0];
        
        // Verify that mrp, sellingPrice, and discount are strings
        $this->assertIsString($variation['mrp']);
        $this->assertIsString($variation['sellingPrice']);
        $this->assertIsString($variation['discount']);
        $this->assertEquals('0', $variation['mrp']);
        $this->assertEquals('0', $variation['sellingPrice']);
        $this->assertEquals('0', $variation['discount']);
    }

    public function testSyncProductAvailabilityIsTrue()
    {
        $data = [
            'productId' => '6429',
            'title' => 'Floral Diamond Bracelet',
            'currency' => 'INR',
            'imageUrl' => 'https://www.kirtilals.com/images/media/products/6429/500x500/floral-diamond-bracelet-6429-1.jpg'
        ];
        
        $result = EkatraSDK::syncProduct($data);
        $productData = $result['data'];
        $variation = $productData['variants'][0]['variations'][0];
        
        // Verify availability is true even when quantity is 0
        $this->assertTrue($variation['availability']);
        $this->assertEquals(0, $variation['quantity']);
    }

    public function testSyncProductWithSnakeCaseFields()
    {
        // Test with snake_case field names (like Kirtilals format)
        $data = [
            'product_id' => '20051',
            'title' => 'Multicolor Gemstone Orb Earrings',
            'currency' => 'INR',
            'image_urls' => 'https://staging-3.kirtilals.com/images/media/products/20051/500x500/multicolor-gemstone-orb-earrings-20051-1.jpg,https://staging-3.kirtilals.com/images/media/products/20051/500x500/multicolor-gemstone-orb-earrings-20051-2.jpg'
        ];
        
        $result = EkatraSDK::syncProduct($data);
        
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('20051', $result['data']['productId']);
        $this->assertEquals('Multicolor Gemstone Orb Earrings', $result['data']['title']);
        $this->assertEquals('INR', $result['data']['currency']);
        // Should use first image from comma-separated string
        $this->assertStringContainsString('20051-1.jpg', $result['data']['variants'][0]['thumbnail']);
    }

    public function testSyncProductWithImagesArray()
    {
        // Test with images as array
        $data = [
            'product_id' => '20051',
            'title' => 'Multicolor Gemstone Orb Earrings',
            'currency' => 'INR',
            'images' => [
                'https://staging-3.kirtilals.com/images/media/products/20051/500x500/multicolor-gemstone-orb-earrings-20051-1.jpg',
                'https://staging-3.kirtilals.com/images/media/products/20051/500x500/multicolor-gemstone-orb-earrings-20051-2.jpg'
            ]
        ];
        
        $result = EkatraSDK::syncProduct($data);
        
        $this->assertEquals('success', $result['status']);
        // Should use first image from array
        $this->assertStringContainsString('20051-1.jpg', $result['data']['variants'][0]['thumbnail']);
    }

    public function testSyncProductWithAlternativeFieldNames()
    {
        // Test with various alternative field names
        $testCases = [
            [
                'id' => '123',
                'name' => 'Test Product',
                'currency_code' => 'USD',
                'thumbnail_url' => 'https://example.com/image.jpg'
            ],
            [
                'sku' => '456',
                'product_name' => 'Another Product',
                'currency' => 'EUR',
                'image_url' => 'https://example.com/image2.jpg'
            ]
        ];
        
        foreach ($testCases as $data) {
            $result = EkatraSDK::syncProduct($data);
            $this->assertEquals('success', $result['status'], 'Failed for data: ' . json_encode($data));
            $this->assertIsArray($result['data']);
            $this->assertArrayHasKey('productId', $result['data']);
            $this->assertArrayHasKey('title', $result['data']);
        }
    }

    public function testSyncProductDataReturnsDataOnly()
    {
        $data = [
            'productId' => '6429',
            'title' => 'Floral Diamond Bracelet',
            'currency' => 'INR',
            'imageUrl' => 'https://www.kirtilals.com/images/media/products/6429/500x500/floral-diamond-bracelet-6429-1.jpg'
        ];
        
        $result = EkatraSDK::syncProductData($data);
        
        // Should return array directly (not wrapped in ResponseBuilder format)
        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('status', $result);
        $this->assertArrayNotHasKey('metadata', $result);
        $this->assertArrayNotHasKey('message', $result);
        
        // Should have product data fields
        $this->assertArrayHasKey('productId', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('currency', $result);
        $this->assertArrayHasKey('variants', $result);
        $this->assertArrayHasKey('sizes', $result);
        
        $this->assertEquals('6429', $result['productId']);
        $this->assertEquals('Floral Diamond Bracelet', $result['title']);
    }

    public function testSyncProductDataThrowsExceptionOnFailure()
    {
        $this->expectException(EkatraValidationException::class);
        
        $invalidData = [
            'title' => 'Missing required fields'
            // Missing productId, currency, imageUrl
        ];
        
        EkatraSDK::syncProductData($invalidData);
    }

    public function testSyncProductDataWithFlexibleFieldNames()
    {
        $testCases = [
            [
                'product_id' => '123',
                'name' => 'Test Product',
                'currency_code' => 'USD',
                'thumbnail_url' => 'https://example.com/image.jpg'
            ],
            [
                'id' => '456',
                'product_name' => 'Another Product',
                'currency' => 'EUR',
                'images' => ['https://example.com/img1.jpg', 'https://example.com/img2.jpg']
            ],
            [
                'sku' => '789',
                'product_title' => 'Third Product',
                'currencyCode' => 'GBP',
                'image_urls' => 'https://example.com/img1.jpg,https://example.com/img2.jpg'
            ]
        ];
        
        foreach ($testCases as $data) {
            $result = EkatraSDK::syncProductData($data);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('productId', $result);
            $this->assertArrayHasKey('title', $result);
            $this->assertArrayHasKey('currency', $result);
        }
    }

    public function testSyncProductDataWithNestedStructures()
    {
        $nestedData = [
            'product_details' => [
                'product_id' => '123',
                'title' => 'Nested Product',
                'currency' => 'INR',
                'image_url' => 'https://example.com/image.jpg'
            ]
        ];
        
        $result = EkatraSDK::syncProductData($nestedData);
        
        $this->assertEquals('123', $result['productId']);
        $this->assertEquals('Nested Product', $result['title']);
    }

    public function testSyncProductsBatchWithSuccessfulProducts()
    {
        $products = [
            [
                'productId' => '20269',
                'title' => 'Product 1',
                'currency' => 'INR',
                'imageUrl' => 'https://example.com/img1.jpg'
            ],
            [
                'product_id' => '20270',
                'name' => 'Product 2',
                'currency_code' => 'USD',
                'images' => ['https://example.com/img2.jpg']
            ]
        ];
        
        $result = EkatraSDK::syncProductsBatch($products);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('successful', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertArrayHasKey('summary', $result);
        
        $this->assertCount(2, $result['successful']);
        $this->assertCount(0, $result['failed']);
        
        $this->assertEquals(2, $result['summary']['total']);
        $this->assertEquals(2, $result['summary']['successful']);
        $this->assertEquals(0, $result['summary']['failed']);
        
        // Check first successful product
        $firstProduct = $result['successful'][0];
        $this->assertEquals('20269', $firstProduct['productId']);
        $this->assertEquals('Product 1', $firstProduct['title']);
    }

    public function testSyncProductsBatchWithFailedProducts()
    {
        $products = [
            [
                'productId' => '20269',
                'title' => 'Valid Product',
                'currency' => 'INR',
                'imageUrl' => 'https://example.com/img1.jpg'
            ],
            [
                'title' => 'Invalid Product'
                // Missing required fields
            ],
            [
                'productId' => '20271',
                'title' => 'Another Valid Product',
                'currency' => 'USD',
                'imageUrl' => 'https://example.com/img3.jpg'
            ]
        ];
        
        $result = EkatraSDK::syncProductsBatch($products);
        
        $this->assertCount(2, $result['successful']);
        $this->assertCount(1, $result['failed']);
        
        $this->assertEquals(3, $result['summary']['total']);
        $this->assertEquals(2, $result['summary']['successful']);
        $this->assertEquals(1, $result['summary']['failed']);
        
        // Check failed product structure
        $failedProduct = $result['failed'][0];
        $this->assertArrayHasKey('index', $failedProduct);
        $this->assertArrayHasKey('input', $failedProduct);
        $this->assertArrayHasKey('error', $failedProduct);
        $this->assertArrayHasKey('validation', $failedProduct);
        
        $this->assertEquals(1, $failedProduct['index']);
        $this->assertEquals('Invalid Product', $failedProduct['input']['title']);
        $this->assertIsString($failedProduct['error']);
        $this->assertArrayHasKey('errors', $failedProduct['validation']);
    }

    public function testSyncProductsBatchWithAllFailed()
    {
        $products = [
            ['title' => 'Missing fields 1'],
            ['title' => 'Missing fields 2'],
            ['title' => 'Missing fields 3']
        ];
        
        $result = EkatraSDK::syncProductsBatch($products);
        
        $this->assertCount(0, $result['successful']);
        $this->assertCount(3, $result['failed']);
        $this->assertEquals(3, $result['summary']['total']);
        $this->assertEquals(0, $result['summary']['successful']);
        $this->assertEquals(3, $result['summary']['failed']);
    }

    public function testSyncProductsBatchWithEmptyArray()
    {
        $result = EkatraSDK::syncProductsBatch([]);
        
        $this->assertCount(0, $result['successful']);
        $this->assertCount(0, $result['failed']);
        $this->assertEquals(0, $result['summary']['total']);
        $this->assertEquals(0, $result['summary']['successful']);
        $this->assertEquals(0, $result['summary']['failed']);
    }

    public function testSyncProductsBatchPreservesOriginalInputInFailures()
    {
        $products = [
            [
                'product_id' => '20269',
                'title' => 'Product with custom fields',
                'currency' => 'INR',
                'custom_field' => 'custom_value',
                'another_field' => 123
                // Missing imageUrl
            ]
        ];
        
        $result = EkatraSDK::syncProductsBatch($products);
        
        $this->assertCount(1, $result['failed']);
        $failed = $result['failed'][0];
        
        // Original input should be preserved
        $this->assertEquals('20269', $failed['input']['product_id']);
        $this->assertEquals('Product with custom fields', $failed['input']['title']);
        $this->assertEquals('custom_value', $failed['input']['custom_field']);
        $this->assertEquals(123, $failed['input']['another_field']);
    }

    public function testSyncProductsBatchEventStructureBuilding()
    {
        $products = [
            ['product_id' => '20269', 'title' => 'Product 1', 'currency' => 'INR', 'image_urls' => 'https://example.com/img1.jpg'],
            ['product_id' => '20270', 'title' => 'Product 2', 'currency' => 'INR', 'image_urls' => 'https://example.com/img2.jpg'],
        ];
        
        $result = EkatraSDK::syncProductsBatch($products);
        
        // Build event structure
        $event = [
            'eventType' => 'PRODUCT_CREATE',
            'eventId' => 'evt_test',
            'timestamp' => date('c'),
            'products' => $result['successful']
        ];
        
        $this->assertEquals('PRODUCT_CREATE', $event['eventType']);
        $this->assertCount(2, $event['products']);
        $this->assertEquals('20269', $event['products'][0]['productId']);
        $this->assertEquals('20270', $event['products'][1]['productId']);
        
        // Verify each product has complete structure
        foreach ($event['products'] as $product) {
            $this->assertArrayHasKey('productId', $product);
            $this->assertArrayHasKey('title', $product);
            $this->assertArrayHasKey('currency', $product);
            $this->assertArrayHasKey('variants', $product);
            $this->assertArrayHasKey('sizes', $product);
        }
    }

    public function testSyncProductDataExceptionContainsValidationErrors()
    {
        try {
            $invalidData = [
                'title' => 'Test'
                // Missing other required fields
            ];
            
            EkatraSDK::syncProductData($invalidData);
            $this->fail('Expected EkatraValidationException was not thrown');
        } catch (EkatraValidationException $e) {
            $this->assertNotEmpty($e->getMessage());
            $errors = $e->getErrors();
            $this->assertIsArray($errors);
            $this->assertNotEmpty($errors);
        }
    }
}

