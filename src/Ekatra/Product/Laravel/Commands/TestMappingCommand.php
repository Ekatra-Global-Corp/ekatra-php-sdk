<?php

namespace Ekatra\Product\Laravel\Commands;

use Illuminate\Console\Command;
use Ekatra\Product\Core\EkatraProduct;
use Ekatra\Product\Core\EkatraVariant;
use Ekatra\Product\Exceptions\EkatraValidationException;

/**
 * TestMappingCommand
 * 
 * Artisan command to test product data mapping
 */
class TestMappingCommand extends Command
{
    /**
     * The name and signature of the console command
     */
    protected $signature = 'ekatra:test-mapping 
                            {--file= : Path to JSON file containing test data}
                            {--data= : JSON string containing test data}
                            {--variant : Test variant mapping only}
                            {--product : Test product mapping only}
                            {--validate : Run validation tests}
                            {--format : Show formatted output}';

    /**
     * The console command description
     */
    protected $description = 'Test Ekatra product data mapping';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $this->info('🧪 Ekatra Product Mapping Test');
        $this->newLine();

        try {
            $testData = $this->getTestData();
            
            if ($this->option('variant')) {
                $this->testVariantMapping($testData);
            } elseif ($this->option('product')) {
                $this->testProductMapping($testData);
            } else {
                $this->testProductMapping($testData);
                $this->newLine();
                $this->testVariantMapping($testData);
            }

            $this->newLine();
            $this->info('✅ Testing completed successfully!');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Get test data from various sources
     */
    private function getTestData(): array
    {
        if ($file = $this->option('file')) {
            if (!file_exists($file)) {
                throw new \Exception("File not found: {$file}");
            }
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON in file: {$file}");
            }
            return $data;
        }

        if ($data = $this->option('data')) {
            $decoded = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON data provided");
            }
            return $decoded;
        }

        // Return sample data
        return $this->getSampleData();
    }

    /**
     * Test product mapping
     */
    private function testProductMapping(array $data): void
    {
        $this->info('📦 Testing Product Mapping');
        $this->line('─' . str_repeat('─', 50));

        try {
            $product = EkatraProduct::fromCustomerData($data);
            
            if ($this->option('validate')) {
                $this->testProductValidation($product);
            }
            
            $result = $product->toEkatraFormatWithValidation();
            
            if ($result['success']) {
                $this->info('✅ Product mapping successful');
                $this->displayProductResult($result['data']);
            } else {
                $this->error('❌ Product mapping failed');
                $this->displayValidationErrors($result['validation']['errors']);
            }
            
        } catch (EkatraValidationException $e) {
            $this->error('❌ Product validation failed: ' . $e->getMessage());
            $this->displayValidationErrors($e->getErrors());
        } catch (\Exception $e) {
            $this->error('❌ Product mapping error: ' . $e->getMessage());
        }
    }

    /**
     * Test variant mapping
     */
    private function testVariantMapping(array $data): void
    {
        $this->info('🔧 Testing Variant Mapping');
        $this->line('─' . str_repeat('─', 50));

        // Test with first variant if available
        $variantData = $data['variants'][0] ?? $data;
        
        try {
            $variant = EkatraVariant::fromCustomerData($variantData);
            
            if ($this->option('validate')) {
                $this->testVariantValidation($variant);
            }
            
            $result = $variant->toEkatraFormatWithValidation();
            
            if ($result['success']) {
                $this->info('✅ Variant mapping successful');
                $this->displayVariantResult($result['data']);
            } else {
                $this->error('❌ Variant mapping failed');
                $this->displayValidationErrors($result['validation']['errors']);
            }
            
        } catch (EkatraValidationException $e) {
            $this->error('❌ Variant validation failed: ' . $e->getMessage());
            $this->displayValidationErrors($e->getErrors());
        } catch (\Exception $e) {
            $this->error('❌ Variant mapping error: ' . $e->getMessage());
        }
    }

    /**
     * Test product validation
     */
    private function testProductValidation(EkatraProduct $product): void
    {
        $validation = $product->validate();
        
        if ($validation['valid']) {
            $this->info('✅ Product validation passed');
        } else {
            $this->warn('⚠️  Product validation issues found:');
            $this->displayValidationErrors($validation['errors']);
        }
    }

    /**
     * Test variant validation
     */
    private function testVariantValidation(EkatraVariant $variant): void
    {
        $validation = $variant->validate();
        
        if ($validation['valid']) {
            $this->info('✅ Variant validation passed');
        } else {
            $this->warn('⚠️  Variant validation issues found:');
            $this->displayValidationErrors($validation['errors']);
        }
    }

    /**
     * Display product result
     */
    private function displayProductResult(array $data): void
    {
        if ($this->option('format')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->line('Product ID: ' . ($data['productId'] ?? 'N/A'));
            $this->line('Title: ' . ($data['title'] ?? 'N/A'));
            $this->line('Variants: ' . count($data['variants'] ?? []));
            $this->line('Specifications: ' . count($data['specifications'] ?? []));
        }
    }

    /**
     * Display variant result
     */
    private function displayVariantResult(array $data): void
    {
        if ($this->option('format')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->line('Name: ' . ($data['name'] ?? 'N/A'));
            $this->line('Color: ' . ($data['color'] ?? 'N/A'));
            $this->line('Size: ' . ($data['size'] ?? 'N/A'));
            $this->line('Price: ' . ($data['sellingPrice'] ?? 'N/A'));
        }
    }

    /**
     * Display validation errors
     */
    private function displayValidationErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $this->line("  • {$error}");
        }
    }

    /**
     * Get sample test data
     */
    private function getSampleData(): array
    {
        return [
            'id' => 'PROD001',
            'name' => 'Sample Product',
            'description' => 'This is a sample product for testing',
            'url' => 'https://example.com/products/sample',
            'keywords' => 'sample,test,product',
            'variants' => [
                [
                    'name' => 'Sample Variant',
                    'price' => 99.99,
                    'originalPrice' => 129.99,
                    'stock' => 10,
                    'color' => 'Red',
                    'size' => 'M',
                    'images' => ['https://example.com/image1.jpg', 'https://example.com/image2.jpg']
                ]
            ]
        ];
    }
}
