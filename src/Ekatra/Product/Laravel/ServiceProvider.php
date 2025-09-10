<?php

namespace Ekatra\Product\Laravel;

use Illuminate\Support\ServiceProvider;
use Ekatra\Product\Laravel\Commands\TestMappingCommand;

/**
 * EkatraProductServiceProvider
 * 
 * Laravel service provider for Ekatra Product SDK
 */
class EkatraProductServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/ekatra.php',
            'ekatra'
        );
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
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
