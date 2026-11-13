<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthenticateUser
{
    public static function normalizeIdentifier(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }
        $value = trim($value);
        if (str_contains($value, '@')) {
            return Str::lower($value);
        }
        $phone = User::normalizePhone($value);
        if (preg_match('/^01[3-9][0-9]{8}$/', $phone ?? '') === 1) {
            return $phone;
        }

        return Str::upper($value);
    }

    public function __invoke(Request $request): ?User
    {
        $identifier = self::normalizeIdentifier($request->input('login'));
        $query = User::query()->with('roles')->where('is_active', true);
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = $query->where('email', $identifier)->first();
            $allowed = $user?->requiresAdministrativeProfile() ?? false;
        } elseif (preg_match('/^01[3-9][0-9]{8}$/', $identifier) === 1) {
            $user = $query->where('verified_phone', $identifier)->where('phone', $identifier)
                ->whereNotNull('phone_verified_at')->first();
            $allowed = $user && ($user->requiresAdministrativeProfile() || $user->hasRole('guardian'));
        } elseif (preg_match('/^NGHS[0-9]{11}$/', $identifier) === 1) {
            $user = $query->where('student_id', $identifier)->first();
            $allowed = $user?->hasRole('student') ?? false;
        } else {
            return null;
        }

        return $allowed && ! $user->temporaryPasswordHasExpired()
            && Hash::check($request->input('password'), $user->password) ? $user : null;
    }
}
