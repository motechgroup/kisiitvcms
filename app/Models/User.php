<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar_path',
        'job_title',
        'bio',
        'phone',
        'is_active',
        'social_links',
        'deactivated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'social_links' => 'array',
            'deactivated_at' => 'datetime',
        ];
    }

    /**
     * Get all roles assigned to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    /**
     * Get category-specific editorial access restrictions.
     */
    public function categoryAccess(): HasMany
    {
        return $this->hasMany(UserCategoryAccess::class);
    }

    /**
     * Get categories assigned to the user.
     */
    public function accessibleCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'user_category_access')
            ->withPivot(['can_approve', 'can_publish'])
            ->withTimestamps();
    }

    /**
     * Get articles authored by the user.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Get articles where user is a co-author.
     */
    public function coAuthoredArticles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_authors')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get media uploaded by the user.
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    /**
     * Get comments by the user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get audit log entries created by this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Determine if the user is a super administrator.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Determine if user has any of the given roles.
     *
     * @param  string|list<string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return $this->roles->pluck('slug')->intersect($roles)->isNotEmpty();
    }

    /**
     * Determine if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($this->roles as $role) {
            if ($role->permissions->contains('slug', $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has permission to manage or publish a specific category.
     */
    public function canManageCategory(Category|int $category, string $action = 'view'): bool
    {
        if ($this->isSuperAdmin() || $this->hasRole(['admin', 'editor-in-chief', 'managing-editor'])) {
            return true;
        }

        $categoryId = $category instanceof Category ? $category->id : $category;

        $access = $this->categoryAccess()->where('category_id', $categoryId)->first();

        if (! $access) {
            return false;
        }

        return match ($action) {
            'approve' => (bool) $access->can_approve,
            'publish' => (bool) $access->can_publish,
            default => true,
        };
    }

    /**
     * Get the user's username or fall back to an email-based handle.
     */
    public function getUsernameAttribute(?string $value): string
    {
        if (! empty($value)) {
            return $value;
        }

        return Str::slug(Str::before($this->email, '@'), '_') ?: 'user_'.$this->id;
    }

    /**
     * Get the user's avatar URL with fallback to a generated avatar.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar_path) {
            return str_starts_with($this->avatar_path, 'http') ? $this->avatar_path : asset($this->avatar_path);
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&color=FFFFFF&background=1b52bc&bold=true';
    }
}
