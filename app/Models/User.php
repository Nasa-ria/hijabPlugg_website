<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_super_admin',
        'admin_permissions',
        'must_change_password',
        'invited_by',
        'is_active',
        'profile_bio',
        'phone',
        'address',
        'city',
        'postal_code',
        'country',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'is_super_admin' => 'boolean',
        'admin_permissions' => 'array',
        'must_change_password' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * Admin areas that can be granted to an admin. "team" is reserved for super admins.
     * key => [label, route name of the area's main page]
     */
    public const ADMIN_SECTIONS = [
        'dashboard' => ['Dashboard', 'admin.dashboard'],
        'orders' => ['Orders', 'admin.orders.index'],
        'messages' => ['Messages', 'admin.messages.index'],
        'products' => ['Products', 'admin.products.index'],
        'inventory' => ['Inventory, returns & reports', 'admin.inventory.index'],
        'shipping' => ['Shipping settings', 'admin.shipping.index'],
        'categories' => ['Categories', 'admin.categories.index'],
        'reviews' => ['Reviews', 'admin.reviews.index'],
        'newsletters' => ['Newsletters', 'admin.newsletters.index'],
        'posts' => ['Blog posts', 'admin.posts.index'],
    ];

    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && $this->is_super_admin;
    }

    /**
     * Can this admin use the given admin area?
     */
    public function canAccessAdmin(string $section): bool
    {
        if (! $this->isAdmin()) {
            return false;
        }
        if ($this->is_super_admin) {
            return true;
        }

        return $section !== 'team' && in_array($section, $this->admin_permissions ?? [], true);
    }

    /**
     * Where to send this admin after signing in: the first area they can use.
     */
    public function adminHomeRoute(): ?string
    {
        foreach (self::ADMIN_SECTIONS as $key => [, $route]) {
            if ($this->canAccessAdmin($key)) {
                return $route;
            }
        }

        return null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' && $this->is_active;
    }
}
