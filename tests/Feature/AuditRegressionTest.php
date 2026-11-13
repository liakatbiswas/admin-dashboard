<?php

namespace Tests\Feature;

use App\Models\AdmissionOnOff;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditRegressionTest extends TestCase
{
    public function test_password_reset_revokes_existing_api_tokens(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('existing')->plainTextToken;
        $this->actingAs($this->superAdmin())->post(route('users.temporary-password', $user))->assertOk();

        $this->assertSame(0, $user->tokens()->count());
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => explode('|', $token)[0]]);
    }

    public function test_two_factor_settings_require_password_confirmation_before_revealing_recovery_codes(): void
    {
        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => encrypt(json_encode(['secret-recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->actingAs($user)->get(route('settings.two-factor'))
            ->assertRedirect(route('password.confirm'))->assertDontSee('secret-recovery-code');
        $this->withSession(['auth.password_confirmed_at' => time()])->get(route('settings.two-factor'))
            ->assertOk()->assertSee('secret-recovery-code');
        $this->get(route('profile.edit'))->assertOk()->assertDontSee('secret-recovery-code');
    }

    public function test_admin_cannot_bypass_profile_rules_using_self_update(): void
    {
        $user = $this->superAdmin();
        $email = $user->email;

        $this->actingAs($user)->put(route('users.update', $user), [
            'name' => $user->name, 'email' => 'attacker@example.com', 'roles' => ['super-admin'],
        ])->assertForbidden();

        $this->assertSame($email, $user->fresh()->email);
        $this->get(route('users.edit', $user))->assertRedirect(route('profile.edit'));
    }

    public function test_profile_email_change_requires_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('user-profile-information.update'), [
            'name' => $user->name, 'email' => 'changed@example.com',
        ])->assertSessionHasErrorsIn('updateProfileInformation', 'current_password');

        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function test_passkey_endpoints_are_unavailable_until_feature_is_implemented(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/user/passkeys/options')->assertNotFound();
    }

    public function test_failed_avatar_write_preserves_existing_photo(): void
    {
        $user = User::factory()->create(['avatar' => 'avatars/old.png']);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->once()->andReturn($disk);

        $this->actingAs($user)->put(route('user-profile-information.update'), [
            'name' => $user->name, 'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('photo.png', 100, 100),
        ])->assertSessionHasErrorsIn('updateProfileInformation', 'avatar');

        $this->assertSame('avatars/old.png', $user->fresh()->avatar);
    }

    public function test_successful_avatar_replacement_removes_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/old.png', 'old');
        $user = User::factory()->create(['avatar' => 'avatars/old.png']);

        $this->actingAs($user)->put(route('user-profile-information.update'), [
            'name' => $user->name, 'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('photo.png', 100, 100),
        ])->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing('avatars/old.png');
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    public function test_profile_email_change_revokes_tokens_without_sending_verification_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $token = $user->createToken('old');
        $this->actingAs($user)->put(route('user-profile-information.update'), [
            'name' => $user->name, 'email' => 'changed@example.com', 'current_password' => 'password',
        ])->assertSessionHasNoErrors();
        $this->assertSame('changed@example.com', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertModelMissing($token->accessToken);
        Notification::assertNothingSent();
    }

    public function test_registration_returns_429_after_repeated_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/register', [])->assertUnprocessable();
        }

        $this->postJson('/register', [])->assertTooManyRequests();
    }

    public function test_repeated_admission_close_requests_do_not_reopen_registration(): void
    {
        $this->actingAs($this->superAdmin());
        $this->post(route('settings.admission.toggle'), ['is_on' => false])->assertSessionHasNoErrors();
        $this->post(route('settings.admission.toggle'), ['is_on' => false])->assertSessionHasNoErrors();

        $this->assertFalse(AdmissionOnOff::isOn());
    }

    public function test_admission_requires_explicit_boolean_state(): void
    {
        $this->actingAs($this->superAdmin())->post(route('settings.admission.toggle'), [])
            ->assertSessionHasErrors('is_on');

        $this->assertTrue(AdmissionOnOff::isOn());
    }

    public function test_delegated_role_manager_cannot_change_public_student_permissions(): void
    {
        $manager = $this->userWithPermissions(['roles.manage', 'users.delete']);
        $role = Role::findByName('student');

        $this->actingAs($manager)->put(route('roles.update', $role), [
            'name' => 'student', 'permissions' => ['users.delete'],
        ])->assertForbidden();

        $this->assertSame([], $role->fresh()->permissions->pluck('name')->all());
    }

    public function test_role_manager_cannot_change_role_of_unmanageable_user(): void
    {
        $manager = $this->userWithPermissions(['roles.manage', 'users.view']);
        $role = Role::create(['name' => 'shared', 'guard_name' => 'web']);
        $target = User::factory()->create()->assignRole($role);
        $target->givePermissionTo('users.delete');

        $this->actingAs($manager)->put(route('roles.update', $role), [
            'name' => 'shared', 'permissions' => ['users.view'],
        ])->assertForbidden();

        $this->assertSame([], $role->fresh()->permissions->pluck('name')->all());
    }

    public function test_role_permissions_survive_validation_redirect(): void
    {
        $this->actingAs($this->superAdmin())->from(route('roles.create'))->post(route('roles.store'), [
            'name' => 'INVALID NAME', 'permissions_submitted' => '1', 'permissions' => ['users.view'],
        ])->assertSessionHasErrors('name');

        $html = $this->get(route('roles.create'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/value="users.view"[^>]*checked/', $html);
    }

    public function test_intentionally_empty_permissions_stay_empty_after_validation_failure(): void
    {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $role->givePermissionTo('users.view');
        $url = route('roles.edit', $role);
        $this->actingAs($this->superAdmin())->from($url)->put(route('roles.update', $role), [
            'name' => 'INVALID NAME', 'permissions_submitted' => '1',
        ])->assertSessionHasErrors('name');

        $html = $this->get($url)->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/value="users.view"[^>]*checked/', $html);
    }

    public function test_activity_migration_can_be_rolled_back_and_reapplied(): void
    {
        $migration = require database_path('migrations/2026_11_08_081121_create_activity_log_table.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('activity_log'));

        $migration->up();
        $this->assertTrue(Schema::hasTable('activity_log'));
    }

    public function test_settings_reads_do_not_return_stale_cached_attributes(): void
    {
        SiteSetting::current();
        SiteSetting::whereKey(1)->update(['site_title' => 'New title']);

        $this->assertSame('New title', SiteSetting::current()->site_title);
    }

    public function test_site_settings_updates_require_separate_permission(): void
    {
        $manager = $this->userWithPermissions(['admission.manage']);

        $this->actingAs($manager)->put(route('settings.site.update'), ['site_title' => 'Unauthorized'])
            ->assertForbidden();

        $this->assertNotSame('Unauthorized', SiteSetting::current()->site_title);
    }

    public function test_site_settings_save_updates_only_authorized_fields(): void
    {
        $this->actingAs($this->superAdmin())->put(route('settings.site.update'), ['site_title' => 'Updated school'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Updated school', SiteSetting::current()->site_title);
    }

    public function test_user_role_and_email_update_requires_manual_verification(): void
    {
        Notification::fake();
        $user = User::factory()->create()->assignRole('student');
        $user->createToken('existing');

        $this->actingAs($this->superAdmin())->put(route('users.update', $user), [
            'name' => 'Updated name', 'email' => 'updated@example.com', 'roles' => ['student'],
        ])->assertSessionHasNoErrors();

        $this->assertSame('updated@example.com', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertSame(0, $user->tokens()->count());
        Notification::assertNothingSent();
    }

    public function test_account_creation_does_not_send_email_or_create_reset_tokens(): void
    {
        Notification::fake();
        $this->actingAs($this->superAdmin())->post(route('users.store'), [
            'name' => 'Invite', 'email' => 'invite@example.com', 'roles' => ['student'],
        ])->assertOk()->assertSessionHasNoErrors();
        $user = User::where('email', 'invite@example.com')->firstOrFail();

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_revoked_session_is_rejected_without_relying_on_database_session_deletion(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['auth_version' => 2])->save();
        $guardKey = auth()->guard('web')->getName();

        $this->actingAs($user)->withSession([$guardKey => $user->id, 'auth.version' => 1])
            ->get('/dashboard')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_normal_login_stamps_the_session_and_allows_dashboard_access(): void
    {
        $user = User::factory()->create()->assignRole('teacher');

        $this->post('/login', ['login' => $user->email, 'password' => 'password'])
            ->assertSessionHasNoErrors()->assertSessionHas('auth.version', 0);

        $this->get('/dashboard')->assertOk();
    }

    public function test_role_creation_rolls_back_when_audit_logging_fails(): void
    {
        Event::listen('eloquent.creating: '.Activity::class,
            fn () => throw new \RuntimeException('Audit storage failed'));

        $this->actingAs($this->superAdmin())->post(route('roles.store'), [
            'name' => 'rollback-role', 'permissions' => ['users.view'],
        ])->assertServerError();

        $this->assertDatabaseMissing('roles', ['name' => 'rollback-role']);
    }

    public function test_site_uploads_are_cleaned_up_when_database_write_fails(): void
    {
        Storage::fake('public');
        $oldTitle = SiteSetting::current()->site_title;
        Event::listen('eloquent.updating: '.SiteSetting::class,
            fn () => throw new \RuntimeException('Settings storage failed'));

        $this->actingAs($this->superAdmin())->put(route('settings.site.update'), [
            'site_title' => 'Uncommitted title',
            'light_logo' => UploadedFile::fake()->image('logo.png', 100, 100),
        ])->assertServerError();

        $this->assertSame($oldTitle, SiteSetting::current()->site_title);
        $this->assertSame([], Storage::disk('public')->allFiles('site-settings'));
    }

    public function test_profile_upload_is_cleaned_up_when_database_write_fails(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/old.png', 'old');
        $user = User::factory()->create(['avatar' => 'avatars/old.png']);
        Event::listen('eloquent.updating: '.User::class,
            fn () => throw new \RuntimeException('User storage failed'));

        $this->actingAs($user)->put(route('user-profile-information.update'), [
            'name' => $user->name, 'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('avatar.png', 100, 100),
        ])->assertServerError();

        $this->assertSame('avatars/old.png', $user->fresh()->avatar);
        $this->assertSame(['avatars/old.png'], Storage::disk('public')->allFiles('avatars'));
    }

    public function test_account_creation_does_not_depend_on_mail_delivery(): void
    {
        $channel = Mockery::mock(MailChannel::class);
        $channel->shouldNotReceive('send');
        $this->app->instance(MailChannel::class, $channel);

        $this->actingAs($this->superAdmin())->post(route('users.store'), [
            'name' => 'Retry invitation', 'email' => 'retry@example.com', 'roles' => ['student'],
        ])->assertOk()->assertSessionHasNoErrors();

        $user = User::where('email', 'retry@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('student'));
        $this->assertTrue($user->must_change_password);
    }

    public function test_activity_date_filter_includes_end_of_day_but_excludes_next_day(): void
    {
        $this->freezeTime();
        Activity::create(['description' => 'End of selected day', 'created_at' => '2026-11-10 23:59:59']);
        Activity::create(['description' => 'Start of next day', 'created_at' => '2026-11-11 00:00:00']);

        $this->actingAs($this->superAdmin())->get(route('activity.index', ['from' => '2026-11-10', 'to' => '2026-11-10']))
            ->assertOk()->assertSee('End of selected day')->assertDontSee('Start of next day');
    }

    public function test_revocation_deletes_sessions_on_the_configured_database_connection(): void
    {
        Notification::fake();
        $user = User::factory()->create()->assignRole('student');
        config(['database.connections.session_test' => config('database.connections.sqlite'), 'session.driver' => 'database', 'session.connection' => 'session_test']);
        Schema::connection('session_test')->create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity');
        });
        $row = ['id' => 'target-session', 'user_id' => $user->id, 'payload' => base64_encode(serialize([])), 'last_activity' => time()];
        DB::connection('session_test')->table('sessions')->insert($row);
        DB::table('sessions')->insert($row);

        $this->actingAs($this->superAdmin())->put(route('users.update', $user), [
            'name' => $user->name, 'email' => 'new-address@example.com', 'roles' => ['student'],
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, DB::connection('session_test')->table('sessions')->where('id', 'target-session')->count());
        $this->assertDatabaseHas('sessions', ['id' => 'target-session']);
        $this->assertSame(1, $user->fresh()->auth_version);
        Notification::assertNothingSent();
    }

    public function test_malformed_permission_input_renders_validation_errors_without_server_error(): void
    {
        $this->actingAs($this->superAdmin())->from(route('roles.create'))->post(route('roles.store'), [
            'name' => 'editor', 'permissions_submitted' => '1', 'permissions' => 'not-an-array',
        ])->assertSessionHasErrors('permissions');

        $this->get(route('roles.create'))->assertOk();
        $this->assertDatabaseMissing('roles', ['name' => 'editor']);
    }

    public function test_text_only_profile_update_does_not_delete_a_concurrently_replaced_avatar(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/current.png', 'current');
        $user = User::factory()->create(['avatar' => 'avatars/stale.png']);
        User::whereKey($user->id)->update(['avatar' => 'avatars/current.png']);

        $this->actingAs($user)->put(route('user-profile-information.update'), [
            'name' => 'Updated text', 'email' => $user->email,
        ])->assertSessionHasNoErrors();

        $this->assertSame('avatars/current.png', $user->fresh()->avatar);
        Storage::disk('public')->assertExists('avatars/current.png');
    }
}
