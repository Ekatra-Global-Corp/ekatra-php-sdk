<?php

namespace Ekatra\Product\Tests\Laravel;

use Orchestra\Testbench\TestCase;
use Ekatra\Product\Laravel\ServiceProvider;

class TestRoutesEnabledTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('ekatra.testing.routes_enabled', true);
    }

    public function testTestRoutesLoadWhenEnabled()
    {
        $this->get('/ekatra/test/sample')
            ->assertStatus(200)
            ->assertJsonStructure(['product_sample', 'variant_sample']);
    }

    public function testProductRouteTransformsData()
    {
        $this->postJson('/ekatra/test/product', ['id' => 'P1', 'name' => 'Test', 'currency' => 'INR'])
            ->assertJsonStructure(['status', 'metadata', 'message']);
    }
}
