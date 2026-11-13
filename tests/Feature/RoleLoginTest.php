<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleLoginTest extends TestCase
{
    #[DataProvider('administrativeRoles')]
    public function test_administrative_roles_can_use_email_or_verified_mobile(string $role, string $identifier): void
    {
        $user = User::factory()->create(['email' => 'teacher@example.com', 'phone' => '01712345678', 'verified_phone' => '01712345678'])->assignRole($role);
        $this->post('/login', ['login' => $identifier, 'password' => 'password'])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertOk();
    }

    public static function administrativeRoles(): array
    {
        $cases = [];
        foreach (['super-admin', 'admin', 'teacher', 'clerk', 'staff'] as $role) {
            $cases[$role.' email'] = [$role, 'TEACHER@example.com'];
            $cases[$role.' mobile'] = [$role, '+880 1712-345678'];
        }

        return $cases;
    }

    public function test_guardian_without_email_can_log_in_confirm_password_and_update_profile(): void
    {
        $user = User::factory()->create(['email' => null, 'email_verified_at' => null])->assignRole('guardian');
        $this->post('/login', ['login' => $user->phone, 'password' => 'password'])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertOk();
        $this->post('/user/confirm-password', ['password' => 'password'])->assertSessionHasNoErrors();
        $this->get('/profile')->assertOk();
        $this->put(route('user-profile-information.update'), [
            'name' => 'Guardian Updated', 'email' => null, 'phone' => $user->phone,
        ])->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->email);
    }

    public function test_student_without_email_can_log_in_with_assigned_student_id(): void
    {
        $user = User::factory()->create(['email' => null, 'email_verified_at' => null, 'student_id' => 'NGHS20270600001'])->assignRole('student');
        $this->post('/login', ['login' => 'nghs20270600001', 'password' => 'password'])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertOk();
        $this->post('/user/confirm-password', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->post('/user/confirm-password', ['password' => 'password'])->assertSessionHasNoErrors();
    }

    #[DataProvider('disallowedIdentifiers')]
    public function test_role_boundaries_reject_other_login_methods(string $role, string $field): void
    {
        $user = User::factory()->create(['student_id' => 'NGHS20270600001'])->assignRole($role);
        $this->post('/login', ['login' => $user->{$field}, 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public static function disallowedIdentifiers(): array
    {
        return [
            ['student', 'email'], ['student', 'phone'], ['guardian', 'email'],
            ['guardian', 'student_id'], ['teacher', 'student_id'],
        ];
    }

    public function test_multi_role_account_accepts_the_combined_login_methods(): void
    {
        $user = User::factory()->create(['student_id' => 'NGHS20270600001'])->assignRole(['teacher', 'guardian', 'student']);
        foreach ([$user->email, $user->phone, $user->student_id] as $identifier) {
            $this->post('/login', ['login' => $identifier, 'password' => 'password'])->assertSessionHasNoErrors();
            $this->assertAuthenticatedAs($user);
            $this->post('/logout');
        }
    }

    public function test_unverified_mobile_cannot_be_used_but_administrative_email_can(): void
    {
        $user = User::factory()->create(['phone_verified_at' => null, 'verified_phone' => null])->assignRole('teacher');
        $this->post('/login', ['login' => $user->phone, 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertGuest();
        $this->post('/login', ['login' => $user->email, 'password' => 'password'])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertRedirect(route('profile.edit'));
    }

    public function test_mobile_normalization_does_not_bypass_rate_limit(): void
    {
        $user = User::factory()->create(['phone' => '01712345678', 'verified_phone' => '01712345678'])->assignRole('guardian');
        foreach (['01712345678', '+8801712345678', '8801712345678', '01712-345678', '01712 345678'] as $identifier) {
            $this->post('/login', ['login' => $identifier, 'password' => 'wrong'])->assertSessionHasErrors('login');
        }
        $this->post('/login', ['login' => $user->phone, 'password' => 'password'])->assertTooManyRequests();
        $this->assertGuest();
    }

    #[DataProvider('blockedAccounts')]
    public function test_inactive_deleted_or_expired_accounts_cannot_use_alternative_identifiers(string $role, string $state): void
    {
        $user = User::factory()->create(['student_id' => 'NGHS20270600001'])->assignRole($role);
        if ($state === 'inactive') {
            $user->forceFill(['is_active' => false])->save();
        } elseif ($state === 'deleted') {
            $user->delete();
        } else {
            $this->freezeTime();
            $user->forceFill(['must_change_password' => true, 'temporary_password_expires_at' => now()])->save();
        }
        $this->post('/login', [
            'login' => $role === 'student' ? $user->student_id : $user->phone, 'password' => 'password',
        ])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public static function blockedAccounts(): array
    {
        return [
            ['student', 'inactive'], ['student', 'deleted'], ['student', 'expired'],
            ['guardian', 'inactive'], ['guardian', 'deleted'], ['guardian', 'expired'],
        ];
    }

    public function test_initial_super_admin_can_complete_the_full_onboarding_flow(): void
    {
        $user = User::where('email', config('permissions.super_admin.email'))->sole();
        $user->forceFill(['password' => 'Initial-Password-123'])->save();
        $this->post('/login', ['login' => $user->email, 'password' => 'Initial-Password-123'])->assertSessionHasNoErrors();
        $this->get('/dashboard')->assertRedirect(route('password.change.required'));
        $this->put(route('user-password.update'), [
            'current_password' => 'Initial-Password-123', 'password' => 'Permanent-Password-456',
            'password_confirmation' => 'Permanent-Password-456',
        ])->assertSessionHasNoErrors();
        $this->get('/dashboard')->assertRedirect(route('profile.edit'));
        $this->post('/user/confirm-password', ['password' => 'Permanent-Password-456'])->assertSessionHasNoErrors();
        $this->get('/profile')->assertOk();
        $this->put(route('user-profile-information.update'), [
            'name' => $user->name, 'email' => $user->email, 'phone' => '01912345678',
        ])->assertSessionHasNoErrors();
        $this->post('/logout');
        $this->assertSame(0, Artisan::call('sms:verify-initial-admin', [
            'email' => $user->email, 'phone' => '01912345678', '--reason' => 'Checked in person',
        ]));
        $this->post('/login', ['login' => '01912345678', 'password' => 'Permanent-Password-456'])->assertSessionHasNoErrors();
        $this->get('/dashboard')->assertOk();
    }

    public function test_malformed_login_input_is_rejected(): void
    {
        $this->post('/login', ['login' => ['invalid'], 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_student_id_is_not_assignable_from_profile_or_user_update(): void
    {
        $user = User::factory()->create(['student_id' => 'NGHS20270600001'])->assignRole('student');
        $this->actingAs($this->superAdmin())->put(route('users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'roles' => ['student'], 'student_id' => 'NGHS20270600002',
        ])->assertSessionHasNoErrors();
        $this->assertSame('NGHS20270600001', $user->fresh()->student_id);
    }

    public function test_guardian_can_be_created_without_email_but_administrative_account_cannot(): void
    {
        $this->actingAs($this->superAdmin())->post(route('users.store'), [
            'name' => 'Guardian', 'phone' => '01912345678', 'roles' => ['guardian'],
        ])->assertOk();
        $this->assertDatabaseHas('users', ['name' => 'Guardian', 'email' => null]);
        $this->post(route('users.store'), [
            'name' => 'Teacher', 'roles' => ['teacher', 'guardian'],
        ])->assertSessionHasErrors('email');
    }
}
