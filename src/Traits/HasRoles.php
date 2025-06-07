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
     * Check if the user has a specific role.
     *
     * @param string $roleSlug The slug of the role to check
     * @return bool Returns true if the user has the specified role, false otherwise
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->role && $this->role->slug === $roleSlug;
    }

    /**
     * Check if the user has any of the given roles.
     *
     * @param array $roleSlugs Array of role slugs to check
     * @return bool Returns true if the user has any of the specified roles
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        return $this->role && in_array($this->role->slug, $roleSlugs);
    }

    /**
     * For backward compatibility, checks if the user's role matches all given roles.
     * Since a user can have only one role, this checks if that role is in the given array.
     *
     * @param array $roleSlugs Array of role slugs to check
     * @return bool Returns true if the user's role is in the given array
     */
    public function hasAllRoles(array $roleSlugs): bool
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
     * Alias for giveRole to maintain backward compatibility.
     * Only the first role in the array will be used.
     */
    public function syncRoles(array $roleSlugs): void
    {
        if (!empty($roleSlugs)) {
            $this->giveRole($roleSlugs[0]);
        } else {
            $this->removeRole();
        }
    }

    /**
     * Remove the user's role.
     * If a role slug is provided, it will only be removed if it matches the current role.
     * 
     * @param string|null $roleSlug
     * @return bool Returns true if a role was removed, false otherwise
     */
    public function removeRole(?string $roleSlug = null): bool
    {
        if ($roleSlug && !$this->hasRole($roleSlug)) {
            return false;
        }

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
