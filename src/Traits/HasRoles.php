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
     * This is a many-to-many relationship using a pivot table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function role()
    {
        return $this->belongsToMany(
            \Abdulbaset\RolesPermissions\Models\Role::class,
            config('roles.tables.role_user'),
            'user_id',
            'role_id'
        )->withTimestamps();
    }
    
    /**
     * Get the user's single role.
     * This method is used as an accessor to return only the first role
     * when accessing the role attribute (e.g., $user->role).
     * Since users have only one role, this ensures we always get a single role instance.
     *
     * @return \Abdulbaset\RolesPermissions\Models\Role|null Returns the user's role or null if no role is assigned
     */
    public function getRoleAttribute()
    {
        return $this->role()->first();
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
        return $this->role()->where('slug', $roleSlug)->exists();
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
        return $this->role()->whereIn('slug', $roleSlugs)->exists();
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
        $role = $this->role;
        return $role && $role->permissions()->where('slug', $permissionSlug)->exists();
    }

    /**
     * Check if the user has any of the given permissions.
     *
     * @param array $permissionSlugs Array of permission slugs to check
     * @return bool Returns true if the user has any of the specified permissions
     */
    public function hasAnyPermission(array $permissionSlugs): bool
    {
        return $this->role()
            ->whereHas('permissions', function($query) use ($permissionSlugs) {
                $query->whereIn('slug', $permissionSlugs);
            })
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
        $role = $this->role()->first();
        if (!$role) {
            return false;
        }

        $permissionCount = $role->permissions()
            ->whereIn('slug', $permissionSlugs)
            ->count();

        return $permissionCount === count($permissionSlugs);
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
        $role = \Abdulbaset\RolesPermissions\Models\Role::where('slug', $roleSlug)->first();

        if (!$role) {
            throw new \InvalidArgumentException("Role [{$roleSlug}] not found.");
        }

        // Sync the role (will detach any existing roles first)
        $this->role()->sync([$role->id]);
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
        $count = $this->role()->detach();
        return $count > 0;
    }
}
