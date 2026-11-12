<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Notifications\AccountEmailChanged;
use Illuminate\Support\Facades\Auth;
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

        if (isset($input['avatar'])) {
            $user->avatar = $input['avatar']->store('avatars', 'public');
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

        if ($emailChanged) {
            $oldEmail = $user->email;
            $user->forceFill($data + ['email_verified_at' => null])->save();

            $user->tokens()->delete();
            Auth::guard('web')->logoutOtherDevices($input['current_password']);
            $user->sendEmailVerificationNotification();

            try {
                Notification::route('mail', $oldEmail)
                    ->notify(new AccountEmailChanged($user->name, $oldEmail, $user->email));
            } catch (\Throwable $exception) {
                report($exception);
            }
        } else {
            $user->forceFill($data)->save();
        }

        if ($oldAvatar && $oldAvatar !== $user->avatar) {
            Storage::disk('public')->delete($oldAvatar);
        }
    }
}
