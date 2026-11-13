<?php

namespace App\Console\Commands;

use App\Actions\VerifyUserContacts;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Signature('sms:verify-initial-admin {email} {phone} {--reason= : How the contact details were checked}')]
#[Description('Manually verify the configured initial Super Admin once, after checking their contact details')]
class BootstrapSuperAdminVerification extends Command
{
    public function handle(VerifyUserContacts $verify): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        $reason = trim((string) $this->option('reason'));
        if (! $user || ! $user->profileIsComplete() || $user->must_change_password || $reason === '' || strlen($reason) > 500) {
            $this->error('Complete the profile and initial password change, and supply a verification reason.');

            return self::FAILURE;
        }
        try {
            DB::transaction(function () use ($user, $verify, $reason): void {
                $verify->handle($user, null, 'email', (string) $this->argument('email'), $reason);
                $verify->handle($user, null, 'phone', User::normalizePhone((string) $this->argument('phone')) ?? '', $reason);
                activity('security')->causedByAnonymous()->performedOn($user)
                    ->withProperties(['reason' => $reason, 'source' => 'artisan'])
                    ->log('Initial Super Admin contacts verified');
            });
        } catch (ValidationException|HttpException $exception) {
            $this->error('Verification refused. Check the account, contact details, uniqueness and whether bootstrap has already been completed.');

            return self::FAILURE;
        }
        $this->info('Initial Super Admin contacts verified.');

        return self::SUCCESS;
    }
}
