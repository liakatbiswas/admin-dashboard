<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class RecoverSuperAdminTest extends TestCase
{
    public function test_creation_requires_password_change_without_logging_the_password(): void
    {
        $this->travelTo(now()->startOfSecond());
        $this->assertSame(0, Artisan::call('sms:super-admin', [
            'email' => 'recovery@example.com', '--create' => true, '--name' => 'Recovery Admin',
        ]));
        $output = Artisan::output();
        preg_match('/Temporary password: (.+)/', $output, $matches);
        $password = trim($matches[1]);
        $user = User::where('email', 'recovery@example.com')->sole();
        $this->assertTrue($user->hasRole('super-admin'));
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(now()->addHours(24)->equalTo($user->temporary_password_expires_at));
        $this->assertSame(1, substr_count($output, $password));
        $this->assertStringNotContainsString($password, Activity::all()->toJson());
        $audit = Activity::where('description', 'Super Admin created via console')->sole();
        $this->assertNull($audit->causer_id);
        $this->assertSame('artisan', $audit->properties['source']);
        $this->post('/login', ['email' => $user->email, 'password' => $password])->assertSessionHasNoErrors();
        $this->get('/dashboard')->assertRedirect(route('password.change.required'));
    }

    public function test_reset_revokes_old_access_and_preserves_roles(): void
    {
        config(['session.driver' => 'database']);
        $user = $this->superAdmin();
        $user->assignRole('teacher');
        $user->forceFill(['remember_token' => 'old-token'])->save();
        $oldHash = $user->password;
        $user->createToken('old-token');
        $broker = Password::broker(config('fortify.passwords'));
        $resetToken = $broker->createToken($user);
        DB::table('sessions')->insert([
            ['id' => 'old-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()],
            ['id' => 'other-session', 'user_id' => null, 'payload' => '', 'last_activity' => time()],
        ]);
        $this->assertSame(0, Artisan::call('sms:super-admin', ['email' => $user->email]));
        $fresh = $user->fresh();
        $this->assertNotSame($oldHash, $fresh->password);
        $this->assertSame($user->name, $fresh->name);
        $this->assertEquals($user->email_verified_at, $fresh->email_verified_at);
        $this->assertTrue($fresh->hasAllRoles(['super-admin', 'teacher']));
        $this->assertSame($user->auth_version + 1, $fresh->auth_version);
        $this->assertNull($fresh->remember_token);
        $this->assertSame(0, $fresh->tokens()->count());
        $this->assertFalse($broker->tokenExists($fresh, $resetToken));
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-session']);
    }

    #[DataProvider('invalidRequests')]
    public function test_invalid_requests_do_not_create_accounts(array $arguments): void
    {
        $count = User::count();
        $this->assertSame(1, Artisan::call('sms:super-admin', $arguments));
        $this->assertSame($count, User::count());
        $this->assertStringNotContainsString('Temporary password:', Artisan::output());
    }

    public static function invalidRequests(): array
    {
        return [
            'missing account' => [['email' => 'missing@example.com']],
            'invalid email' => [['email' => 'invalid', '--create' => true, '--name' => 'Admin']],
            'missing name' => [['email' => 'new@example.com', '--create' => true]],
            'reset with name' => [['email' => 'missing@example.com', '--name' => 'Admin']],
            'quiet creation' => [['email' => 'new@example.com', '--create' => true, '--name' => 'Admin', '--quiet' => true]],
            'silent creation' => [['email' => 'new@example.com', '--create' => true, '--name' => 'Admin', '--silent' => true]],
        ];
    }

    public function test_existing_accounts_cannot_be_overwritten_or_promoted(): void
    {
        $user = User::factory()->create()->assignRole('teacher');
        $oldHash = $user->password;
        foreach ([[], ['--create' => true, '--name' => 'Replacement']] as $options) {
            $this->assertSame(1, Artisan::call('sms:super-admin', ['email' => $user->email, ...$options]));
            $this->assertStringNotContainsString('Temporary password:', Artisan::output());
        }
        $this->assertSame($oldHash, $user->fresh()->password);
        $this->assertFalse($user->fresh()->hasRole('super-admin'));
    }

    public function test_inactive_super_admin_requires_explicit_activation(): void
    {
        $user = $this->superAdmin();
        $user->forceFill(['is_active' => false])->save();
        $oldHash = $user->password;
        $this->assertSame(1, Artisan::call('sms:super-admin', ['email' => $user->email]));
        $this->assertSame($oldHash, $user->fresh()->password);
        $this->assertSame(0, Artisan::call('sms:super-admin', ['email' => $user->email, '--activate' => true]));
        $this->assertTrue($user->fresh()->is_active);
        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_deleted_super_admin_cannot_be_recovered_or_recreated(): void
    {
        $user = $this->superAdmin();
        $oldHash = $user->password;
        $user->delete();
        foreach ([[], ['--create' => true, '--name' => 'Replacement']] as $options) {
            $this->assertSame(1, Artisan::call('sms:super-admin', ['email' => $user->email, ...$options]));
        }
        $this->assertSame($oldHash, User::withTrashed()->findOrFail($user->id)->password);
        $this->assertSoftDeleted($user);
    }
}
