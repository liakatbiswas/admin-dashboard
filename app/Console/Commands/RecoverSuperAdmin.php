<?php

namespace App\Console\Commands;

use App\Models\User;
use DomainException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;

#[Signature('sms:super-admin
    {email : Email of the Super Admin account}
    {--create : Create a new Super Admin instead of resetting an existing one}
    {--name= : Full name, required with --create}
    {--activate : Also reactivate an inactive Super Admin}')]
#[Description('Create or recover a Super Admin with a 24-hour temporary password, shown once')]
class RecoverSuperAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->output->getVerbosity() <= OutputInterface::VERBOSITY_QUIET) {
            return self::FAILURE;
        }

        $create = (bool) $this->option('create');
        $email = Str::lower(trim((string) $this->argument('email')));
        $name = trim((string) $this->option('name'));
        $validator = Validator::make(['email' => $email, 'name' => $name], [
            'email' => ['required', 'email', 'max:255'],
            'name' => [$create ? 'required' : 'prohibited', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        try {
            [$user, $temporaryPassword] = DB::transaction(function () use ($create, $email, $name): array {
                $user = User::withTrashed()->where('email', $email)->lockForUpdate()->first();

                if ($create && $user !== null) {
                    throw new DomainException('An account already uses this email. No account was changed.');
                }

                if (! $create) {
                    if ($user === null) {
                        throw new DomainException('Super Admin not found. Use --create --name="Full Name" to create a new account.');
                    }
                    if ($user->trashed() || ! $user->hasRole('super-admin')) {
                        throw new DomainException('Only an existing, non-deleted Super Admin can be recovered.');
                    }
                    if (! $user->is_active && ! $this->option('activate')) {
                        throw new DomainException('This Super Admin is inactive. Add --activate to reactivate it during recovery.');
                    }
                }

                $wasActive = $user?->is_active;
                $user ??= new User;
                $temporaryPassword = Str::password(20);

                if ($create) {
                    $user->forceFill([
                        'name' => $name,
                        'email' => $email,
                        'email_verified_at' => null,
                    ]);
                }

                $user->forceFill([
                    'password' => $temporaryPassword,
                    'must_change_password' => true,
                    'temporary_password_expires_at' => now()->addHours(24),
                    'is_active' => true,
                    'invited_at' => null,
                    'remember_token' => null,
                    'auth_version' => $user->exists ? $user->auth_version + 1 : 0,
                ])->save();

                if ($create) {
                    $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
                    $user->assignRole($role);
                }

                $user->tokens()->delete();
                Password::broker(config('fortify.passwords'))->deleteToken($user);
                if (config('session.driver') === 'database') {
                    DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                        ->where('user_id', $user->id)->delete();
                }

                activity('security')->causedByAnonymous()->performedOn($user)
                    ->withProperties([
                        'source' => 'artisan',
                        'command' => 'sms:super-admin',
                        'operation' => $create ? 'create' : 'reset',
                        'was_active' => $wasActive,
                        'is_active' => true,
                    ])
                    ->log($create ? 'Super Admin created via console' : 'Super Admin password reset via console');

                return [$user, $temporaryPassword];
            });
        } catch (DomainException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($create ? 'Super Admin created.' : 'Super Admin password reset.');
        $this->line('Email: '.$user->email);
        $this->line('Temporary password: '.OutputFormatter::escape($temporaryPassword));
        $this->line('Expires at: '.$user->temporary_password_expires_at->timezone('Asia/Dhaka')->format('Y-m-d H:i:s').' (Asia/Dhaka)');
        $this->line('This password is shown once. Change it after signing in.');

        return self::SUCCESS;
    }
}
