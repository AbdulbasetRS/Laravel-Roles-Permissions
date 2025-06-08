<?php

namespace Abdulbaset\RolesPermissions\Console\Commands;

use Illuminate\Console\Command;
use Abdulbaset\RolesPermissions\Models\Permission;

/**
 * PermissionsSeedCommand
 *
 * This Artisan command seeds the database with permissions from your configuration file.
 * It will only add new permissions and update existing ones, without deleting anything.
 *
 * @package Abdulbaset\RolesPermissions\Console\Commands
 * @author Abdulbaset R. Sayed
 * @link https://github.com/AbdulbasetRS/laravel-roles-permissions
 * @version 1.0.0
 * @license MIT
 */
class PermissionsSeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely seed permissions from config file (does not remove anything)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🌱 Seeding permissions from config file...');
        
        try {
            $allPermissions = $this->getAllPermissionsFromConfig();
            
            if (empty($allPermissions)) {
                $this->warn('No permissions found in configuration. Please check your config/roles.php file.');
                return 1;
            }
            
            $this->line('🔍 Found ' . count($allPermissions) . ' permissions in config file');
            
            $addedCount = 0;
            $updatedCount = 0;
            
            foreach ($allPermissions as $slug => $name) {
                $permission = Permission::withTrashed()->firstOrNew(['slug' => $slug]);
                
                if ($permission->exists) {
                    if ($permission->trashed()) {
                        $permission->restore();
                        $this->line("  ♻️  Restored permission: {$name} (<comment>{$slug}</comment>)");
                    } elseif ($permission->name !== $name) {
                        $permission->name = $name;
                        $permission->save();
                        $this->line("  🔄 Updated permission: {$name} (<comment>{$slug}</comment>)");
                        $updatedCount++;
                    } else {
                        $this->line("  ✓ Exists: {$name} (<comment>{$slug}</comment>)");
                        continue;
                    }
                } else {
                    $permission->name = $name;
                    $permission->save();
                    $this->line("  ➕ Added permission: {$name} (<comment>{$slug}</comment>)");
                    $addedCount++;
                }
            }
            
            $this->newLine();
            $this->info("✅ Successfully seeded permissions!");
            $this->line("  - Added: {$addedCount} new permissions");
            $this->line("  - Updated: {$updatedCount} existing permissions");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Error seeding permissions: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Get all unique permissions from roles configuration
     *
     * @return array
     */
    protected function getAllPermissionsFromConfig()
    {
        $allPermissions = [];
        $roles = config('roles.roles', []);
        
        foreach ($roles as $roleData) {
            if (!isset($roleData['permissions']) || !is_array($roleData['permissions'])) {
                continue;
            }
            
            foreach ($roleData['permissions'] as $permissionSlug) {
                if (!empty($permissionSlug)) {
                    $allPermissions[$permissionSlug] = ucfirst(str_replace('-', ' ', $permissionSlug));
                }
            }
        }
        
        return $allPermissions;
    }
}
