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
        $configPath = __DIR__ . '/../../config/ekatra.php';
        
        // Only merge config if the file exists
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'ekatra');
        }
    }

    /**
     * Bootstrap services
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../../config/ekatra.php';
        
        // Publish configuration only if file exists
        if (file_exists($configPath)) {
            $this->publishes([
                $configPath => config_path('ekatra.php'),
            ], 'ekatra-config');
        }

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                TestMappingCommand::class,
            ]);
        }

        // Register routes for testing only if file exists
        $routesPath = __DIR__ . '/routes.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }
    }

    /**
     * Get the services provided by the provider
     */
    public function provides(): array
    {
        return [];
    }
}
