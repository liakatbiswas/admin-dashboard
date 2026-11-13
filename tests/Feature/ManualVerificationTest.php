<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ManualVerificationTest extends TestCase
{
    public function test_incomplete_administrative_profile_blocks_web_and_api_but_allows_profile(): void
    {
        $user = User::factory()->create(['phone' => null, 'verified_phone' => null, 'phone_verified_at' => null])->assignRole('teacher');
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('profile.edit'));
        $this->withSession(['auth.password_confirmed_at' => time()])->get('/profile')->assertOk()->assertSee('Complete your profile');
        $this->getJson('/api/user')->assertForbidden();
        $this->post('/logout')->assertRedirect();
    }

    public function test_contact_verification_unlocks_dashboard_and_records_actor(): void
    {
        $admin = $this->superAdmin();
        $user = User::factory()->unverified()->create(['phone_verified_at' => null, 'verified_phone' => null])->assignRole('teacher');
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('profile.edit'));
        $this->flushSession();
        foreach (['email', 'phone'] as $field) {
            $this->actingAs($admin)->post(route('contact-verifications.store', $user), [
                'field' => $field, 'value' => $user->{$field}, 'reason' => 'Checked in person',
            ])->assertSessionHasNoErrors()->assertRedirect();
        }
        $this->assertTrue($user->fresh()->contactsAreVerified());
        $this->assertSame(2, Activity::where('description', 'Contact manually verified')->where('causer_id', $admin->id)->count());
        $this->flushSession();
        $this->actingAs($user->fresh())->get('/dashboard')->assertOk();
    }

    public function test_self_verification_and_non_super_admin_verification_are_forbidden(): void
    {
        $admin = $this->superAdmin();
        $other = User::factory()->create()->assignRole('teacher');
        $payload = ['field' => 'email', 'value' => $admin->email, 'reason' => 'Checked'];
        $this->actingAs($admin)->post(route('contact-verifications.store', $admin), $payload)->assertForbidden();
        $this->flushSession();
        $other->givePermissionTo('users.verify');
        $this->actingAs($other)->post(route('contact-verifications.store', $admin), $payload)->assertForbidden();
    }

    public function test_stale_contact_values_and_duplicate_verified_mobile_are_rejected(): void
    {
        $admin = $this->superAdmin();
        $user = User::factory()->unverified()->create(['phone' => $admin->phone, 'phone_verified_at' => null, 'verified_phone' => null]);
        $this->actingAs($admin)->post(route('contact-verifications.store', $user), [
            'field' => 'email', 'value' => 'stale@example.com', 'reason' => 'Checked',
        ])->assertSessionHasErrors('contact');
        $this->post(route('contact-verifications.store', $user), [
            'field' => 'phone', 'value' => $user->phone, 'reason' => 'Checked',
        ])->assertSessionHasErrors('contact');
        $this->assertNull($user->fresh()->phone_verified_at);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_profile_changes_clear_verification_and_do_not_send_email(): void
    {
        Notification::fake();
        $user = User::factory()->create()->assignRole('teacher');
        $this->actingAs($user)->put(route('user-profile-information.update'), [
            'name' => $user->name, 'email' => 'changed@example.com', 'phone' => '+8801812345678',
            'current_password' => 'password', 'email_verified_at' => now(), 'phone_verified_at' => now(),
        ])->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertSame('01812345678', $user->phone);
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->phone_verified_at);
        $this->assertNull($user->verified_phone);
        Notification::assertNothingSent();
    }

    public function test_mobile_is_required_for_administrative_profile(): void
    {
        $user = User::factory()->create()->assignRole('staff');
        $this->actingAs($user)->put(route('user-profile-information.update'), [
            'name' => $user->name, 'email' => $user->email,
        ])->assertSessionHasErrorsIn('updateProfileInformation', 'phone');
        $this->put(route('user-profile-information.update'), [
            'name' => $user->name, 'email' => $user->email, 'phone' => ['invalid'],
        ])->assertSessionHasErrorsIn('updateProfileInformation', 'phone');
    }

    public function test_creation_and_recovery_do_not_verify_email(): void
    {
        Notification::fake();
        $this->actingAs($this->superAdmin())->post(route('users.store'), [
            'name' => 'Teacher', 'email' => 'new@example.com', 'roles' => ['teacher'],
        ])->assertOk();
        $this->assertNull(User::where('email', 'new@example.com')->sole()->email_verified_at);
        $this->assertSame(0, Artisan::call('sms:super-admin', ['email' => 'recovery@example.com', '--create' => true, '--name' => 'Recovery']));
        $this->assertNull(User::where('email', 'recovery@example.com')->sole()->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_bootstrap_verifies_initial_admin_once_and_requires_matching_contacts(): void
    {
        $user = User::where('email', config('permissions.super_admin.email'))->sole();
        $user->forceFill(['must_change_password' => false, 'phone' => '01912345678'])->save();
        $args = ['email' => $user->email, 'phone' => $user->phone, '--reason' => 'Checked in person'];
        $this->assertSame(1, Artisan::call('sms:verify-initial-admin', [...$args, 'phone' => '01999999999']));
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertSame(0, Artisan::call('sms:verify-initial-admin', $args));
        $this->assertTrue($user->fresh()->contactsAreVerified());
        $this->assertSame(1, Artisan::call('sms:verify-initial-admin', $args));
    }

    public function test_unverified_contacts_block_existing_api_tokens(): void
    {
        $user = User::factory()->unverified()->create()->assignRole('teacher');
        $token = $user->createToken('existing')->plainTextToken;
        $this->withToken($token)->getJson('/api/user')->assertForbidden();
    }

    public function test_admin_mobile_change_clears_only_mobile_verification(): void
    {
        $user = User::factory()->create()->assignRole('teacher');
        $this->actingAs($this->superAdmin())->put(route('users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'phone' => '01822223333', 'roles' => ['teacher'],
        ])->assertSessionHasNoErrors();
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->phone_verified_at);
        $this->assertNull($user->fresh()->verified_phone);
    }

    public function test_bootstrap_cannot_verify_a_different_super_admin(): void
    {
        $user = User::factory()->unverified()->create()->assignRole('super-admin');
        $this->assertSame(1, Artisan::call('sms:verify-initial-admin', [
            'email' => $user->email, 'phone' => $user->phone, '--reason' => 'Checked in person',
        ]));
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_email_verification_links_and_resend_are_disabled(): void
    {
        $this->actingAs($this->superAdmin())->post('/email/verification-notification')->assertNotFound();
        $this->get('/email/verify/1/hash')->assertNotFound();
        $this->get(route('contact-verifications.index'))->assertOk();
    }
}
