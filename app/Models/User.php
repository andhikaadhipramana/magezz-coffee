<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_MANAGER = 'manager';

    public const ROLE_KASIR = 'kasir';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isKasir(): bool
    {
        return $this->role === self::ROLE_KASIR;
    }

    public function hasRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_MANAGER => 'Manager',
            self::ROLE_KASIR => 'Kasir / Barista',
            default => ucfirst($this->role),
        };
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function activeShift()
    {
        return $this->shifts()->where('status', 'open')->latest('started_at')->first();
    }
}
