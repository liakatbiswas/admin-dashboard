<?php

namespace App\Actions;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class LinkGuardian
{
    /** @return array{user: User, password: ?string} */
    public function handle(Student $student, User $actor, ?int $accountId, string $reason): array
    {
        return DB::transaction(function () use ($student, $actor, $accountId, $reason): array {
            Role::where('name', 'super-admin')->where('guard_name', 'web')->lockForUpdate()->firstOrFail();
            $student = Student::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $phone = User::normalizePhone($student->guardian['phone']);
            $account = $accountId ? User::whereKey($accountId)->lockForUpdate()->firstOrFail()
                : User::where('verified_phone', $phone)->lockForUpdate()->first();
            $password = null;
            if ($account) {
                abort_if($account->is($actor), 403, __('You cannot verify your own contact details.'));
                abort_unless($account->is_active && $account->id !== $student->user_id, 422);
                if ($account->phone !== $phone) {
                    throw ValidationException::withMessages(['guardian_user_id' => __('The account mobile must match the verified guardian details.')]);
                }
                abort_unless($account->hasRole('guardian') || $actor->hasRole('super-admin'), 403, __('Only a Super Admin may add Guardian role to an existing account.'));
            } else {
                $email = $student->guardian['email'] ?? null;
                if (($email && User::withTrashed()->where('email', $email)->exists())
                    || User::withTrashed()->where(fn ($query) => $query->where('phone', $phone)->orWhere('verified_phone', $phone))->exists()) {
                    throw ValidationException::withMessages(['guardian_user_id' => __('An account already matches these contact details. Select and verify the existing account.')]);
                }
                $password = Str::password(20);
                $account = (new User)->forceFill(['name' => $student->guardian['name'], 'email' => $email,
                    'phone' => $phone, 'password' => $password, 'must_change_password' => true,
                    'temporary_password_expires_at' => now()->addHours(24), 'is_active' => true]);
                $account->save();
            }
            if (User::withTrashed()->where('verified_phone', $phone)->whereKeyNot($account->id)->exists()) {
                throw ValidationException::withMessages(['guardian_user_id' => __('This mobile number is already verified for another account.')]);
            }
            $account->assignRole('guardian');
            $account->forceFill(['verified_phone' => $phone, 'phone_verified_at' => now()])->save();
            $oldGuardian = $student->guardian_user_id;
            $student->update(['guardian_user_id' => $account->id]);
            activity('security')->causedBy($actor)->performedOn($student)->withProperties([
                'old_guardian_user_id' => $oldGuardian, 'guardian_user_id' => $account->id, 'reason' => $reason,
            ])->log('Guardian verified and linked');

            return ['user' => $account, 'password' => $password];
        }, 3);
    }
}
