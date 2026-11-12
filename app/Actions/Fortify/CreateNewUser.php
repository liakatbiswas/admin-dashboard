<?php

namespace App\Actions\Fortify;

use App\Models\AdmissionOnOff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {

        abort_unless(AdmissionOnOff::isOn(), 403, 'Registration is closed.');

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $user = DB::transaction(function () use ($input): User {
            abort_unless((bool) AdmissionOnOff::query()->whereKey(1)->lockForUpdate()->value('is_on'), 403, 'Registration is closed.');
            $role = Role::where('name', 'student')->where('guard_name', 'web')->lockForUpdate()->firstOrFail();
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]);
            $user->assignRole($role);

            return $user;
        });

        return $user;

    }
}
