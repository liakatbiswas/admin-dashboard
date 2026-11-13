<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndSuperAdminSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class TemporaryPasswordTest extends TestCase
{
    public function test_creation_shows_a_password_once_without_persisting_the_plaintext(): void
    {
        $this->travelTo(now()->startOfSecond());
        Notification::fake();

        $response = $this->actingAs($this->superAdmin())->post(route('users.store'), [
            'name' => 'Teacher', 'email' => 'temporary@example.com', 'roles' => ['teacher'],
        ])->assertOk()->assertViewIs('users.temporary-password');

        $password = $response->viewData('temporaryPassword');
        $user = User::where('email', 'temporary@example.com')->sole();
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(now()->addHours(24)->equalTo($user->temporary_password_expires_at));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString($password, json_encode(session()->all()));
        $this->assertStringNotContainsString($password, Activity::all()->toJson());
        $this->get(route('users.index'))->assertOk()->assertDontSee($password);
        $this->get(route('users.create'))->assertOk()->assertDontSee($password);
        Notification::assertNothingSent();
    }

    public function test_first_login_requires_a_password_change_even_without_email_verification(): void
    {
        $user = $this->temporaryUser()->forceFill(['email_verified_at' => null]);
        $user->save();

        $this->post('/login', ['login' => $user->email, 'password' => 'Temporary-Password-123'])
            ->assertSessionHasNoErrors();

        $this->get('/dashboard')->assertRedirect(route('password.change.required'));
        $this->get(route('password.change.required'))->assertOk()->assertSee('Change your password');
        $this->put(route('user-profile-information.update'), ['name' => 'Bypass'])
            ->assertRedirect(route('password.change.required'));
    }

    #[DataProvider('expiryTimes')]
    public function test_login_is_rejected_at_or_after_temporary_password_expiry(int $hours): void
    {
        $this->freezeTime();
        $user = $this->temporaryUser();
        $this->travel($hours)->hours();

        $this->post('/login', ['login' => $user->email, 'password' => 'Temporary-Password-123'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public static function expiryTimes(): array
    {
        return ['at expiry' => [24], 'after expiry' => [25]];
    }

    public function test_an_expired_temporary_password_ends_an_existing_session(): void
    {
        $this->freezeTime();
        $user = $this->temporaryUser();
        $this->post('/login', ['login' => $user->email, 'password' => 'Temporary-Password-123']);
        $this->travel(24)->hours();

        $this->get(route('password.change.required'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_password_change_unlocks_the_account_and_revokes_old_credentials(): void
    {
        $user = $this->temporaryUser();
        $user->createToken('old-token');
        $this->post('/login', ['login' => $user->email, 'password' => 'Temporary-Password-123']);

        $this->from(route('password.change.required'))->put(route('user-password.update'), [
            'current_password' => 'Temporary-Password-123',
            'password' => 'Permanent-Password-456', 'password_confirmation' => 'Permanent-Password-456',
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertNull($user->temporary_password_expires_at);
        $this->assertTrue(Hash::check('Permanent-Password-456', $user->password));
        $this->assertFalse(Hash::check('Temporary-Password-123', $user->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame(1, $user->auth_version);
        $this->get('/dashboard')->assertOk();
    }

    #[DataProvider('invalidPasswordChanges')]
    public function test_invalid_password_changes_do_not_unlock_the_account(array $input, string $error): void
    {
        $user = $this->temporaryUser();

        $this->actingAs($user)->put(route('user-password.update'), $input)
            ->assertSessionHasErrorsIn('updatePassword', $error);

        $this->assertTrue($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check('Temporary-Password-123', $user->fresh()->password));
    }

    public static function invalidPasswordChanges(): array
    {
        return [
            'wrong current password' => [[
                'current_password' => 'Wrong-Password-123',
                'password' => 'Permanent-Password-456', 'password_confirmation' => 'Permanent-Password-456',
            ], 'current_password'],
            'confirmation mismatch' => [[
                'current_password' => 'Temporary-Password-123',
                'password' => 'Permanent-Password-456', 'password_confirmation' => 'Different-Password-456',
            ], 'password'],
            'same password' => [[
                'current_password' => 'Temporary-Password-123',
                'password' => 'Temporary-Password-123', 'password_confirmation' => 'Temporary-Password-123',
            ], 'password'],
        ];
    }

    public function test_temporary_accounts_cannot_bypass_password_change_using_the_api(): void
    {
        $user = $this->temporaryUser();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->getJson('/api/user')->assertForbidden();
    }

    public function test_super_admin_reset_invalidates_sessions_tokens_and_the_old_password(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = User::factory()->create(['remember_token' => 'old-remember-token'])->assignRole('teacher');
        $user->createToken('old-token');
        DB::table('sessions')->insert([
            'id' => 'existing-session', 'user_id' => $user->id,
            'payload' => base64_encode(serialize([])), 'last_activity' => time(),
        ]);
        config(['session.driver' => 'database']);

        $response = $this->actingAs($this->superAdmin())->post(route('users.temporary-password', $user))
            ->assertOk()->assertViewIs('users.temporary-password');

        $user->refresh();
        $this->assertTrue(Hash::check($response->viewData('temporaryPassword'), $user->password));
        $this->assertFalse(Hash::check('password', $user->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame(1, $user->auth_version);
        $this->assertNull($user->remember_token);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(now()->addHours(24)->equalTo($user->temporary_password_expires_at));
        $this->assertDatabaseMissing('sessions', ['id' => 'existing-session']);
    }

    public function test_super_admin_cannot_reset_their_own_password_through_user_management(): void
    {
        $user = $this->superAdmin();
        $password = $user->password;

        $this->actingAs($user)->post(route('users.temporary-password', $user))->assertForbidden();

        $this->assertSame($password, $user->fresh()->password);
    }

    public function test_the_seeded_super_admin_requires_a_password_change_without_expiry(): void
    {
        $this->freezeTime();
        $user = User::where('email', config('permissions.super_admin.email'))->sole();
        $password = $user->password;

        $this->seed(RolesAndSuperAdminSeeder::class);
        $this->travel(25)->hours();

        $this->assertTrue($user->fresh()->must_change_password);
        $this->assertNull($user->fresh()->temporary_password_expires_at);
        $this->assertFalse($user->fresh()->temporaryPasswordHasExpired());
        $this->assertSame($password, $user->fresh()->password);
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('password.change.required'));
    }

    public function test_email_recovery_and_old_invitation_routes_are_disabled(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', ['email' => 'test@example.com'])->assertNotFound();
        $this->post('/reset-password', [])->assertNotFound();
        $user = User::factory()->create();
        $this->actingAs($this->superAdmin())->post('/admin/users/'.$user->id.'/invite')->assertNotFound();
    }

    private function temporaryUser(): User
    {
        $user = User::factory()->create(['password' => 'Temporary-Password-123'])->assignRole('teacher');
        $user->forceFill([
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->addHours(24),
        ])->save();

        return $user;
    }
}
