<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
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
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'invited_at' => 'datetime',
            'auth_version' => 'integer',
            'must_change_password' => 'boolean',
            'temporary_password_expires_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'password', 'remember_token', 'auth_version',
        'two_factor_secret', 'two_factor_recovery_codes',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            $user->email = $user->email === null ? null : Str::lower(trim($user->email));
            $user->phone = static::normalizePhone($user->phone);
            if ($user->exists && $user->isDirty('email')) {
                $user->email_verified_at = null;
            }
            if ($user->exists && $user->isDirty('phone')) {
                $user->phone_verified_at = null;
                $user->verified_phone = null;
            }
        });
    }

    public static function normalizePhone(?string $phone): ?string
    {
        $phone = preg_replace('/[\s()-]+/', '', $phone ?? '');
        if (str_starts_with($phone, '+880')) {
            $phone = '0'.substr($phone, 4);
        } elseif (str_starts_with($phone, '880')) {
            $phone = '0'.substr($phone, 3);
        }

        return $phone === '' ? null : $phone;
    }

    public function requiresAdministrativeProfile(): bool
    {
        return $this->hasAnyRole(['super-admin', 'admin', 'teacher', 'clerk', 'staff']);
    }

    public function profileIsComplete(): bool
    {
        return filled($this->name) && filled($this->email)
            && preg_match('/^01[3-9][0-9]{8}$/', $this->phone ?? '') === 1;
    }

    public function contactsAreVerified(): bool
    {
        return $this->email_verified_at !== null && $this->phone_verified_at !== null
            && $this->phone !== null && $this->verified_phone === $this->phone;
    }

    protected function login(): Attribute
    {
        return Attribute::get(fn (): string => $this->email ?? $this->verified_phone ?? $this->student_id ?? (string) $this->id);
    }

    public function emailIsRequired(): bool
    {
        return $this->requiresAdministrativeProfile() || ! $this->hasAnyRole(['student', 'guardian']);
    }

    public function profileIsReady(): bool
    {
        if ($this->requiresAdministrativeProfile()) {
            return $this->profileIsComplete() && $this->contactsAreVerified();
        }
        if ($this->hasRole('guardian')) {
            return filled($this->name) && $this->phone_verified_at !== null
                && filled($this->phone) && $this->verified_phone === $this->phone;
        }
        if ($this->hasRole('student')) {
            return filled($this->name) && filled($this->student_id);
        }

        return $this->hasVerifiedEmail();
    }

    public function temporaryPasswordHasExpired(): bool
    {
        return $this->must_change_password && $this->temporary_password_expires_at !== null
            && $this->temporary_password_expires_at->lessThanOrEqualTo(now());
    }

    public function assignableRoles(): Collection
    {
        if ($this->hasRole('super-admin')) {
            return Role::where('guard_name', 'web')->orderBy('name')->pluck('name');
        }

        return collect();
    }

    public function assignablePermissions(): Collection
    {
        return $this->hasRole('super-admin')
            ? Permission::orderBy('name')->get()
            : collect();
    }
}
