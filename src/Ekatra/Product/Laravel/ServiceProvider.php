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
        // Load config only if it exists and is readable
        $configPath = __DIR__ . '/../../config/ekatra.php';
        
        if (file_exists($configPath) && is_readable($configPath)) {
            try {
                $this->mergeConfigFrom($configPath, 'ekatra');
            } catch (\Exception $e) {
                // Silently fail if config loading fails
                // This prevents upgrade issues while still allowing config when possible
            }
        }
    }

    /**
     * Bootstrap services
     */
    public function boot()
    {
        // Publish configuration with error handling
        $configPath = __DIR__ . '/../../config/ekatra.php';
        
        if (file_exists($configPath) && is_readable($configPath)) {
            try {
                $this->publishes([
                    $configPath => config_path('ekatra.php'),
                ], 'ekatra-config');
            } catch (\Exception $e) {
                // Silently fail if config publishing fails
            }
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
