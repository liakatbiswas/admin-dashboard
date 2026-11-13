<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
        if (isset($input['phone']) && is_string($input['phone'])) {
            $input['phone'] = User::normalizePhone($input['phone']);
        }
        if (isset($input['email']) && is_string($input['email'])) {
            $input['email'] = Str::lower(trim($input['email']));
        }
        $emailChanged = ($input['email'] ?? null) !== $user->email;

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [$user->emailIsRequired() ? 'required' : 'nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => $emailChanged
                ? ['required', 'string', 'current_password:web']
                : ['nullable', 'string'],
            'phone' => [($user->requiresAdministrativeProfile() || $user->hasRole('guardian')) ? 'required' : 'nullable', 'string', 'regex:/^01[3-9][0-9]{8}$/'],
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
            'email' => $input['email'] ?? null,
            'phone' => $input['phone'] ?? null,
            'bio' => $input['bio'] ?? null,
            'address' => $input['address'] ?? null,
        ];

        if (isset($input['avatar']) || $user->avatar !== $oldAvatar) {
            $data['avatar'] = $user->avatar;
        }

        try {
            DB::transaction(function () use ($user, $data, $emailChanged, &$oldAvatar): void {
                $current = User::query()->lockForUpdate()->findOrFail($user->id);
                abort_unless($current->email === $user->email && $current->phone === $user->phone, 409, __('Your profile changed. Reload the page and try again.'));
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
        }
    }
}
