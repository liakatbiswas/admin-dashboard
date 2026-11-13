<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class VerifyUserContacts
{
    public function handle(User $target, ?User $actor, string $field, string $expected, string $reason): void
    {
        DB::transaction(function () use ($target, $actor, $field, $expected, $reason): void {
            Role::where('name', 'super-admin')->where('guard_name', 'web')->lockForUpdate()->firstOrFail();
            $user = User::query()->lockForUpdate()->findOrFail($target->id);
            if ($actor !== null) {
                abort_unless($actor->hasRole('super-admin') && ! $actor->is($user), 403);
            } else {
                abort_unless($user->hasRole('super-admin')
                    && $user->email === config('permissions.super_admin.email')
                    && ! Activity::where('description', 'Initial Super Admin contacts verified')->exists(), 403);
            }
            abort_unless($user->is_active, 422);
            if (! in_array($field, ['email', 'phone'], true) || ! filled($user->{$field}) || $user->{$field} !== $expected) {
                throw ValidationException::withMessages(['contact' => __('Contact details changed. Reload and check them again.')]);
            }
            if ($field === 'phone') {
                if (preg_match('/^01[3-9][0-9]{8}$/', $user->phone) !== 1
                    || User::withTrashed()->where('verified_phone', $user->phone)->whereKeyNot($user->id)->exists()) {
                    throw ValidationException::withMessages(['contact' => __('This mobile number is invalid or already verified for another account.')]);
                }
                $user->forceFill(['phone_verified_at' => now(), 'verified_phone' => $user->phone])->save();
            } else {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
            $log = activity('security')->performedOn($user)->withProperties([
                'field' => $field, 'value' => $expected, 'reason' => $reason,
                'source' => $actor ? 'web' : 'artisan',
            ]);
            $actor ? $log->causedBy($actor) : $log->causedByAnonymous();
            $log->log('Contact manually verified');
        });
    }
}
