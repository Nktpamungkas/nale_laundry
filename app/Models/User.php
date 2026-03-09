<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, BelongsToTenant;

    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_KASIR = 'kasir';
    public const ROLE_OPERATOR = 'operator';
    public const ROLE_STAFF = 'staff';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'role',
        'is_active',
        'password',
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
            'is_active' => 'boolean',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'last_active_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole([self::ROLE_ADMIN, self::ROLE_OWNER]);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    /**
     * @param array<int, string> $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function hasBackofficeAccess(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_SUPERADMIN,
            self::ROLE_OWNER,
            self::ROLE_ADMIN,
            self::ROLE_KASIR,
            self::ROLE_OPERATOR,
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
