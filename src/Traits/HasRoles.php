<?php

namespace Abdulbaset\RolesPermissions\Traits;

use Abdulbaset\RolesPermissions\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * HasRoles Trait
 *
 * This trait provides role and permission management functionality to the User model.
 * It enables role-based access control (RBAC) with support for single role per user
 * and multiple permissions per role.
 *
 * @package Abdulbaset\RolesPermissions\Traits
 * @author Abdulbaset R. Sayed
 * @link https://github.com/AbdulbasetRS/laravel-roles-permissions
 * @link https://www.linkedin.com/in/abdulbaset-r-sayed
 * @version 1.0.0
 * @license MIT
 */
trait HasRoles
{
    /**
     * Define the relationship between the user and their role.
     * This is a one-to-many relationship where a user can have only one role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(
            \Abdulbaset\RolesPermissions\Models\Role::class,
            'id',
            'user_id',
            config('roles.tables.role_user')
        );
    }

    /**
     * Check if the user has the specified role.
     *
     * @param string $roleSlug The slug of the role to check
     * @return bool Returns true if the user has the specified role, false otherwise
     * 
     * @example
     * // Check for a role
     * $user->hasRole('admin');
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->role && $this->role->slug === $roleSlug;
    }

    /**
     * Check if the user has any of the specified roles.
     *
     * @param array $roleSlugs Array of role slugs to check
     * @return bool Returns true if the user has any of the specified roles
     * 
     * @example
     * // Check for multiple roles
     * $user->hasAnyRole(['admin', 'editor']);
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        return $this->role && in_array($this->role->slug, $roleSlugs);
    }

    /**
     * Check if the user has a specific permission.
     * This checks if the user's role has the specified permission.
     *
     * @param string $permissionSlug The slug of the permission to check
     * @return bool Returns true if the user has the permission, false otherwise
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->role && $this->role->permissions()
            ->where('slug', $permissionSlug)
            ->exists();
    }

    /**
     * Check if the user has any of the given permissions.
     *
     * @param array $permissionSlugs Array of permission slugs to check
     * @return bool Returns true if the user has any of the specified permissions
     */
    public function hasAnyPermission(array $permissionSlugs): bool
    {
        return $this->role && $this->role->permissions()
            ->whereIn('slug', $permissionSlugs)
            ->exists();
    }

    /**
     * Check if the user has all of the given permissions.
     *
     * @param array $permissionSlugs Array of permission slugs to check
     * @return bool Returns true if the user has all of the specified permissions
     */
    public function hasAllPermissions(array $permissionSlugs): bool
    {
        if (!$this->role) {
            return false;
        }

        $count = $this->role->permissions()
            ->whereIn('slug', $permissionSlugs)
            ->count();

        return $count === count($permissionSlugs);
    }

    /**
     * Assign a role to the user.
     * If the user already has a role, it will be replaced.
     *
     * @param string $roleSlug
     * @return void
     */
    public function giveRole(string $roleSlug): void
    {
        $role = Role::firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => ucfirst(str_replace('-', ' ', $roleSlug))]
        );

        // Remove any existing role first
        $this->removeRole();

        // Attach the new role
        \DB::table(config('roles.tables.role_user'))->updateOrInsert(
            ['user_id' => $this->id],
            ['role_id' => $role->id, 'created_at' => now(), 'updated_at' => now()]
        );
        
        // Refresh the relationship
        $this->load('role');
    }

    /**
     * Alias for giveRole that accepts a single role slug.
     * 
     * @param string $roleSlug The slug of the role to assign
     * @return void
     */
    public function syncRoles(string $roleSlug): void
    {
        $this->giveRole($roleSlug);
    }

    /**
     * Remove the user's current role.
     * 
     * @return bool Returns true if a role was removed, false otherwise
     */
    public function removeRole(): bool
    {
        $deleted = \DB::table(config('roles.tables.role_user'))
            ->where('user_id', $this->id)
            ->delete();

        if ($deleted) {
            $this->load('role');
            return true;
        }

        return false;
    }
}
