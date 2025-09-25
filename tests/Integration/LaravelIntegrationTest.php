<?php

namespace Ekatra\Product\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ekatra\Product\Laravel\EkatraProductServiceProvider;
use Ekatra\Product\Laravel\Facades\Ekatra;
use Ekatra\Product\EkatraSDK;

class LaravelIntegrationTest extends TestCase
{
    public function testServiceProviderRegistration()
    {
        // The class is ServiceProvider in the Laravel namespace
        $this->assertTrue(class_exists('Ekatra\Product\Laravel\ServiceProvider'));
    }
    
    public function testFacadeAccess()
    {
        $this->assertTrue(class_exists(Ekatra::class));
    }
    
    public function testEkatraSDKClassExists()
    {
        $this->assertTrue(class_exists(EkatraSDK::class));
    }
    
    public function testEkatraSDKMethodsExist()
    {
        $this->assertTrue(method_exists(EkatraSDK::class, 'smartTransformProduct'));
        $this->assertTrue(method_exists(EkatraSDK::class, 'getManualSetupGuide'));
        $this->assertTrue(method_exists(EkatraSDK::class, 'getDataStructureExamples'));
        $this->assertTrue(method_exists(EkatraSDK::class, 'getCodeExamples'));
        $this->assertTrue(method_exists(EkatraSDK::class, 'getBestPractices'));
        $this->assertTrue(method_exists(EkatraSDK::class, 'getTroubleshootingGuide'));
        $this->assertTrue(method_exists(EkatraSDK::class, 'getEducationalValidation'));
        $this->assertTrue(method_exists(EkatraSDK::class, 'canAutoTransform'));
        $this->assertTrue(method_exists(EkatraSDK::class, 'getSupportedFormats'));
    }
}
