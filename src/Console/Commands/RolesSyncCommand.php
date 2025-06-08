<?php

namespace Abdulbaset\RolesPermissions\Console\Commands;

use Illuminate\Console\Command;
use Abdulbaset\RolesPermissions\Models\Role;

/**
 * RolesSyncCommand
 *
 * This Artisan command synchronizes roles between your configuration file
 * and the database. It ensures that all roles defined in your roles configuration
 * exist in the database, and removes any roles that are no longer in use.
 *
 * @package Abdulbaset\RolesPermissions\Console\Commands
 * @author Abdulbaset R. Sayed
 * @link https://github.com/AbdulbasetRS/laravel-roles-permissions
 * @version 1.0.0
 * @license MIT
 */
class RolesSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize roles between config and database (removes roles not in config)';

    /**
     * Execute the console command.
     * 
     * @return int
     */
    public function handle()
    {
        $this->info('🔄 Syncing roles with database...');
        
        try {
            $roles = config('roles.roles', []);
            
            if (empty($roles)) {
                $this->warn('No roles found in configuration. Please check your config/roles.php file.');
                return 1;
            }
            
            $roleSlugs = array_keys($roles);
            $syncedCount = 0;
            
            // Create or update roles
            foreach ($roles as $slug => $roleData) {
                Role::updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $roleData['name']]
                );
                $this->line("  ✓ Role: {$roleData['name']} (<comment>{$slug}</comment>)");
                $syncedCount++;
            }
            
            // Remove roles not in config
            $deletedCount = Role::whereNotIn('slug', $roleSlugs)->delete();
            
            $this->newLine();
            $this->info("✅ Successfully synced {$syncedCount} roles.");
            if ($deletedCount > 0) {
                $this->info("🗑️  Removed {$deletedCount} roles that are no longer in config.");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Error syncing roles: ' . $e->getMessage());
            return 1;
        }
    }
}
