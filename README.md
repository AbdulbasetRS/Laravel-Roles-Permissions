# Laravel Roles and Permissions

A simple and flexible package for handling roles and permissions in Laravel applications.

## Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x

## Features

- Role-based access control (RBAC)
- Permission management
- Blade directives for easy role and permission checks in views
- Artisan commands for managing roles and permissions
- Support for multiple roles per user
- Lightweight and easy to integrate

## Installation

You can install the package via Composer:

```bash
composer require abdulbaset/laravel-roles-permissions
```

Publish the configuration file and migrations:

```bash
php artisan vendor:publish --provider="Abdulbaset\RolesPermissions\RolesPermissionsServiceProvider" --tag=roles-config
php artisan vendor:publish --provider="Abdulbaset\RolesPermissions\RolesPermissionsServiceProvider" --tag=roles-migrations
```

Run the migrations:

```bash
php artisan migrate
```

## Usage

### Add HasRoles Trait to User Model

Add the `HasRoles` trait to your User model:

```php
use Abdulbaset\RolesPermissions\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasRoles;
    
    // ...
}
```

> **Important:** Each user can have only one role. The system enforces this constraint at the database level.

### Available Methods

#### User Model Methods

```php
// Get the user's role
$role = $user->role;

// Check if user has a specific role
$user->hasRole('admin');

// Check if user has any of the specified roles
$user->hasAnyRole(['admin', 'editor']);

// Check if user has a permission
$user->hasPermission('edit-posts');

// Check if user has any of the given permissions
$user->hasAnyPermission(['edit-posts', 'delete-posts']);

// Check if user has all of the given permissions
$user->hasAllPermissions(['edit-posts', 'delete-posts']);

// Assign a role to a user (replaces any existing role)
$user->giveRole('editor');

// Sync role (alias for giveRole)
$user->syncRoles('editor');

// Remove the user's role
$user->removeRole();
```

#### Role Model Methods

```php
// Create a new role with description
$role = Role::create([
    'name' => 'Editor',
    'slug' => 'editor',
    'description' => 'Can edit and manage content'
]);

// Update role description
$role->update(['description' => 'Updated role description']);

// Get role description
$description = $role->description;
```

#### Permission Model Methods

```php
// Create a new permission with description
$editPosts = Permission::create([
    'name' => 'Edit Posts',
    'slug' => 'edit-posts',
    'description' => 'Allows editing of existing posts'
]);

// Update permission description
$permission->update(['description' => 'Updated permission description']);

// Get permission description
$description = $permission->description;
```

##### Example Usage:

```php
// Assign a role to a user
$user->giveRole('admin');

// Check user's role
if ($user->hasRole('admin')) {
    // User is an admin
}

// Get the role name
$roleName = $user->role ? $user->role->name : 'No role';

// Remove role
$user->removeRole();

// Check if user has any of these roles
if ($user->hasAnyRole(['admin', 'moderator'])) {
    // User is either admin or moderator
}
```

#### Role Model Methods

```php
// Check if role has a permission
$role->hasPermission('edit-posts');

// Give permission to a role
$role->givePermission('edit-posts');

// Sync all permissions for a role (removes all existing permissions and adds the given ones)
$role->syncPermissions(['edit-posts', 'delete-posts']);

// Remove a specific permission from a role
$role->removePermission('edit-posts');  // Returns boolean

// Remove multiple permissions from a role
$removedCount = $role->removePermissions(['edit-posts', 'delete-posts']);  // Returns number of permissions removed

// Remove all permissions from a role
$removedCount = $role->removeAllPermissions();  // Returns number of permissions removed
```

##### Example Usage:

```php
use Abdulbaset\RolesPermissions\Models\Role;

// Get a role
$adminRole = Role::where('slug', 'admin')->first();

// Add a permission
$adminRole->givePermission('delete-users');

// Check if role has permission
if ($adminRole->hasPermission('delete-users')) {
    // Role has the permission
}

// Remove a permission
$adminRole->removePermission('delete-users');

// Remove multiple permissions
$adminRole->removePermissions(['edit-posts', 'delete-posts']);

// Remove all permissions
$removedCount = $adminRole->removeAllPermissions();
```

## Permissions Methods

### Using getPermissions()

```php
// Get all permissions for a role
$rolePermissions = $role->getPermissions();

// Get all permissions for a user's role
$userPermissions = $user->getPermissions();

// Example: Loop through permissions
foreach ($user->getPermissions() as $permission) {
    echo $permission->name; // e.g., 'edit-posts'
    echo $permission->description; // e.g., 'Can edit posts'
}

// Check if user has any permissions
if ($user->getPermissions()->isNotEmpty()) {
    // User has permissions
}

// Check if role has any permissions
if ($role->getPermissions()->isNotEmpty()) {
    // Role has permissions
}
```

## Authorization Methods

### 1. Using FormRequest

You can check permissions directly in your FormRequest classes:

```php
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->hasPermission('create-product');
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ];
    }
}
```

### 2. Using Policies

1. First, create a policy:

```bash
php artisan make:policy ProductPolicy --model=Product
```

2. Implement the policy methods:

```php
namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasPermission('view-products');
    }

    public function view(User $user, Product $product)
    {
        return $user->hasPermission('view-product');
    }

    public function create(User $user)
    {
        return $user->hasPermission('create-product');
    }
    
    // ... other methods
}
```

3. Register the policy in `AuthServiceProvider`:

```php
protected $policies = [
    Product::class => ProductPolicy::class,
];
```

### 3. Using Middleware

1. Register the middleware in `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    'permission' => \App\Http\Middleware\CheckPermission::class,
];
```

2. Create the middleware:

```bash
php artisan make:middleware CheckPermission
```

3. Implement the middleware:

```php
namespace App\Http\Middleware;

use Closure;

class CheckPermission
{
    public function handle($request, Closure $next, $permission)
    {
        if (!auth()->check() || !auth()->user()->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
```

4. Use in routes:

```php
Route::middleware(['auth', 'permission:create-product'])->group(function () {
    Route::get('/products/create', [ProductController::class, 'create']);
    Route::post('/products', [ProductController::class, 'store']);
});
```

### 4. Directly in Controller

You can also check permissions directly in your controller methods:

```php
public function store(Request $request)
{
    // Check permission
    if (!auth()->user()->hasPermission('create-product')) {
        abort(403, 'Unauthorized action.');
    }

    // Validation
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
    ]);

    // Create the product
    $product = Product::create($validated);

    return response()->json($product, 201);
}
```

## Blade Directives

```blade
@role('admin')
    // This content will be shown only to users with the 'admin' role
@endrole

@hasanyrole(['admin', 'editor'])
    // This content will be shown to users with either 'admin' or 'editor' role
@endhasanyrole

@hasallroles(['admin', 'editor'])
    // This content will be shown only to users with both 'admin' and 'editor' roles
@endhasallroles

@haspermission('edit-posts')
    // This content will be shown only to users with the 'edit-posts' permission
@endhaspermission

@hasanypermission(['edit-posts', 'delete-posts'])
    // This content will be shown to users with either 'edit-posts' or 'delete-posts' permission
@endhasanypermission

@hasallpermissions(['edit-posts', 'delete-posts'])
    // This content will be shown only to users with both 'edit-posts' and 'delete-posts' permissions
@endhasallpermissions
```

## Artisan Commands

#### Seed Default Roles and Permissions

```bash
php artisan roles:seed
```

This command will create the default roles and permissions defined in the `config/roles.php` file.

#### Sync Permissions with Database

```bash
php artisan roles:sync
```

This command will sync the permissions defined in the `config/roles.php` file with the database.

## Configuration

You can customize the package by publishing the configuration file:

```bash
php artisan vendor:publish --provider="Abdulbaset\RolesPermissions\RolesPermissionsServiceProvider" --tag=roles-config
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover any security related issues, please email abdulbasetredasayedhf@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
