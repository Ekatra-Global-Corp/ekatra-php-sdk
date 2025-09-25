<?php

namespace Ekatra\Product\Laravel;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Ekatra\Product\Laravel\Commands\TestMappingCommand;

/**
 * ServiceProvider
 * 
 * Laravel service provider for Ekatra Product SDK
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register services
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/ekatra.php',
            'ekatra'
        );
    }

    /**
     * Bootstrap services
     */
    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/ekatra.php' => config_path('ekatra.php'),
        ], 'ekatra-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                TestMappingCommand::class,
            ]);
        }

        // Register routes for testing
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }

    /**
     * Get the services provided by the provider
     */
    public function provides(): array
    {
        return [];
    }
}
