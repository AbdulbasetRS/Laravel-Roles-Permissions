<?php

namespace Abdulbaset\RolesPermissions;

use Illuminate\Support\ServiceProvider;
use Abdulbaset\RolesPermissions\Providers\BladeServiceProvider;

/**
 * RolesPermissionsServiceProvider
 *
 * The main service provider for the Laravel Roles and Permissions package.
 * This class handles package registration, configuration publishing, and service bootstrapping.
 * It's responsible for setting up the package's core functionality and making it available
 * to the Laravel application.
 *
 * @package Abdulbaset\RolesPermissions
 * @author Abdulbaset R. Sayed
 * @link https://github.com/AbdulbasetRS/laravel-roles-permissions
 * @link https://www.linkedin.com/in/abdulbaset-r-sayed
 * @version 1.0.0
 * @license MIT
 */
class RolesPermissionsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the main class to use with the facade
        $this->mergeConfigFrom(
            __DIR__.'/../config/roles.php', 'roles'
        );

        // Register the Blade service provider
        $this->app->register(BladeServiceProvider::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/../config/roles.php' => config_path('roles.php'),
        ], 'roles-config');

        // Load migrations directly from the package
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Optionally allow publishing migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'roles-migrations');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\RolesSeedCommand::class,
                Console\Commands\RolesSyncCommand::class,
                Console\Commands\PermissionsSeedCommand::class,
                Console\Commands\PermissionsSyncCommand::class,
            ]);
        }
    }
}
