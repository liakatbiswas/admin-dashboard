<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasRoles, LogsActivity,
        Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected $fillable = [
        'name', 'email', 'password',
        'phone', 'avatar', 'bio', 'address', 'is_active',
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
        ];
    }

    protected $hidden = [
        'password', 'remember_token',
        'two_factor_secret', 'two_factor_recovery_codes',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    // public function assignableRoles(): Collection
    // {
    //     return Role::query()
    //         ->when(! $this->hasRole('super-admin'), fn ($q) => $q->where('name', '!=', 'super-admin'))
    //         ->orderBy('name')
    //         ->pluck('name');
    // }

    public function assignableRoles(): Collection
    {
        if ($this->hasRole('super-admin')) {
            return Role::orderBy('name')->pluck('name');
        }

        $mine = $this->getAllPermissions()->pluck('name');

        return Role::with('permissions')
            ->where('name', '!=', 'super-admin')
            ->orderBy('name')
            ->get()
            ->filter(fn ($role) => $role->permissions->pluck('name')->diff($mine)->isEmpty())
            ->pluck('name')
            ->values();
    }

    public function assignablePermissions(): Collection
    {
        return $this->hasRole('super-admin')
            ? Permission::orderBy('name')->get()
            : $this->getAllPermissions()->sortBy('name')->values();
    }
}
