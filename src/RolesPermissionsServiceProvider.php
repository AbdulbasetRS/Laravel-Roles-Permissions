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

        // Publish migrations
        if (! class_exists('CreateRolesTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_roles_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_roles_table.php'),
                __DIR__.'/../database/migrations/create_permissions_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time() + 1).'_create_permissions_table.php'),
                __DIR__.'/../database/migrations/create_role_user_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time() + 2).'_create_role_user_table.php'),
                __DIR__.'/../database/migrations/create_permission_role_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time() + 3).'_create_permission_role_table.php'),
            ], 'roles-migrations');
        }

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
