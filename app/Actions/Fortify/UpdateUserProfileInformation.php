<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Notifications\AccountEmailChanged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        $emailChanged = isset($input['email']) && $input['email'] !== $user->email;

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => $emailChanged
                ? ['required', 'string', 'current_password:web']
                : ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:500'],
            'avatar' => [
                'nullable',
                'bail',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:1024',
                Rule::dimensions()->maxWidth(2000)->maxHeight(2000),
            ],
            'remove_avatar' => ['nullable', 'boolean'],
        ])->validateWithBag('updateProfileInformation');

        $oldAvatar = $user->avatar;
        $newAvatar = null;

        if (isset($input['avatar'])) {
            $newAvatar = $input['avatar']->store('avatars', 'public');
            if (! $newAvatar) {
                throw ValidationException::withMessages(['avatar' => __('The photo could not be uploaded.')])
                    ->errorBag('updateProfileInformation');
            }
            $user->avatar = $newAvatar;
        } elseif (filter_var($input['remove_avatar'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $user->avatar = null;
        }

        $data = [
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $input['phone'] ?? null,
            'bio' => $input['bio'] ?? null,
            'address' => $input['address'] ?? null,
        ];

        if (isset($input['avatar']) || $user->avatar !== $oldAvatar) {
            $data['avatar'] = $user->avatar;
        }

        $oldEmail = $user->email;
        try {
            DB::transaction(function () use ($user, $data, $emailChanged, &$oldAvatar): void {
                $current = User::query()->lockForUpdate()->findOrFail($user->id);
                abort_unless($current->email === $user->email, 409, __('Your profile changed. Reload the page and try again.'));
                $oldAvatar = $current->avatar;
                $user->forceFill($emailChanged ? $data + ['email_verified_at' => null] : $data)->save();
                if ($emailChanged) {
                    $user->tokens()->delete();
                }
            });
        } catch (\Throwable $exception) {
            if ($newAvatar) {
                Storage::disk('public')->delete($newAvatar);
            }
            throw $exception;
        }

        if (array_key_exists('avatar', $data) && $oldAvatar && $oldAvatar !== $user->avatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        if ($emailChanged) {
            Auth::guard('web')->logoutOtherDevices($input['current_password']);
            foreach ([
                fn () => $user->sendEmailVerificationNotification(),
                fn () => Notification::route('mail', $oldEmail)
                    ->notify(new AccountEmailChanged($user->name, $oldEmail, $user->email)),
            ] as $notify) {
                try {
                    $notify();
                } catch (\Throwable $exception) {
                    report($exception);
                    if (request()->hasSession()) {
                        request()->session()->flash('warning', __('Your profile was saved, but an email could not be sent. Please retry email verification or contact an administrator.'));
                    }
                }
            }
        }
    }
}
