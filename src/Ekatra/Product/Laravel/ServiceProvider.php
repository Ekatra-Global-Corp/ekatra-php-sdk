<?php

namespace Ekatra\Product\Laravel;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Ekatra\Product\EkatraSDK;
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
        $configPath = $this->configPath();

        if (file_exists($configPath) && is_readable($configPath)) {
            try {
                $this->mergeConfigFrom($configPath, 'ekatra');
            } catch (\Exception $e) {
                // Silently fail if config loading fails
                // This prevents upgrade issues while still allowing config when possible
            }
        }

        // Backs the Ekatra facade
        $this->app->singleton('ekatra', function () {
            return new EkatraSDK();
        });
    }

    /**
     * Bootstrap services
     */
    public function boot()
    {
        // Publish configuration with error handling
        $configPath = $this->configPath();

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

        // Register test routes only when explicitly enabled (EKATRA_TEST_ROUTES_ENABLED=true).
        // routes.php additionally restricts them to local/testing environments.
        $routesPath = __DIR__ . '/routes.php';
        if (file_exists($routesPath) && $this->app['config']->get('ekatra.testing.routes_enabled', false)) {
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

    /**
     * Path to the package config file (config/ekatra.php at the package root)
     */
    private function configPath(): string
    {
        return dirname(__DIR__, 4) . '/config/ekatra.php';
    }
}
