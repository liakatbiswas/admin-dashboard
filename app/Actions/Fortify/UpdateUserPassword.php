<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => [...$this->passwordRules(), 'different:current_password'],
        ], [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ])->validateWithBag('updatePassword');

        DB::transaction(function () use ($user, $input): void {
            $current = User::query()->lockForUpdate()->findOrFail($user->id);
            if (! Hash::check($input['current_password'], $current->password) || $current->temporaryPasswordHasExpired()) {
                throw ValidationException::withMessages([
                    'current_password' => __('The provided password does not match your current password.'),
                ])->errorBag('updatePassword');
            }

            $current->forceFill([
                'password' => Hash::make($input['password']),
                'must_change_password' => false,
                'temporary_password_expires_at' => null,
                'invited_at' => null,
                'remember_token' => null,
                'auth_version' => $current->auth_version + 1,
            ])->save();
            $current->tokens()->delete();

            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                    ->where('user_id', $current->id)
                    ->when(request()->hasSession(), fn ($query) => $query->where('id', '!=', request()->session()->getId()))
                    ->delete();
            }

            activity('security')->causedBy($current)->log('Password changed');
        });

        $user->refresh();
        if (request()->hasSession()) {
            request()->session()->put('auth.version', $user->auth_version);
            request()->session()->regenerate();
        }
    }
}
