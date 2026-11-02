<?php

namespace Ekatra\Product\Tests\Laravel;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Artisan;
use Ekatra\Product\EkatraSDK;
use Ekatra\Product\Laravel\ServiceProvider;
use Ekatra\Product\Laravel\Facades\Ekatra;

/**
 * Boots the package inside a real Laravel application (via Testbench)
 * so each Laravel version in the CI matrix is actually exercised.
 */
class ServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return ['Ekatra' => Ekatra::class];
    }

    public function testPackageConfigIsMerged()
    {
        $this->assertSame('INR', config('ekatra.default_currency'));
        $this->assertFalse(config('ekatra.testing.routes_enabled'));
    }

    public function testConfigIsPublishable()
    {
        $paths = ServiceProvider::pathsToPublish(ServiceProvider::class, 'ekatra-config');

        $this->assertCount(1, $paths);
        $this->assertFileExists(array_keys($paths)[0]);
    }

    public function testFacadeResolvesToSdk()
    {
        $this->assertInstanceOf(EkatraSDK::class, Ekatra::getFacadeRoot());
        $this->assertSame(EkatraSDK::version(), Ekatra::version());

        $result = Ekatra::transformProduct(['id' => 'P1', 'name' => 'Test', 'currency' => 'INR']);
        $this->assertArrayHasKey('status', $result);
    }

    public function testArtisanCommandIsRegistered()
    {
        $this->assertArrayHasKey('ekatra:test-mapping', Artisan::all());
    }

    public function testTestRoutesAreDisabledByDefault()
    {
        $this->get('/ekatra/test/sample')->assertStatus(404);
    }
}
