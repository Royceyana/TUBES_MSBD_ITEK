<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_KASIR = 'kasir';
    public const ROLE_OWNER = 'owner';
    public const ROLE_DAPUR = 'dapur';
    public const ROLE_WAITRESS = 'waitress';

    public const ROLES = [self::ROLE_ADMIN, self::ROLE_KASIR, self::ROLE_OWNER, self::ROLE_DAPUR, self::ROLE_WAITRESS];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /**
     * Halaman awal tiap role setelah login. Rute dapur dan waitress menunggu halamannya dibuat.
     */
    public const HOME_PATHS = [
        self::ROLE_ADMIN => '/admin/dashboard',
        self::ROLE_KASIR => '/kasir/dashboard',
        self::ROLE_OWNER => '/owner/dashboard',
        self::ROLE_DAPUR => '/dapur/antrian',
        self::ROLE_WAITRESS => '/waitress/pesanan',
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
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function homePath(): string
    {
        return self::HOME_PATHS[$this->role] ?? '/';
    }
}
