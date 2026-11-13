<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public function test_inactive_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create(['is_active' => false])->assignRole('teacher');

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['is_active' => false])->assignRole('teacher');

        $this->post('/login', ['login' => $user->email, 'password' => 'password']);
        $this->assertGuest();
    }

    public function test_active_user_can_log_in(): void
    {
        $user = User::factory()->create()->assignRole('teacher');

        $this->post('/login', ['login' => $user->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_super_admin_creates_user_without_sending_an_email(): void
    {
        Notification::fake();

        $this->actingAs($this->superAdmin())->post(route('users.store'), [
            'name' => 'Teacher One',
            'email' => 'teacher@example.com',
            'roles' => ['teacher'],
        ])->assertOk()->assertViewIs('users.temporary-password');

        $user = User::where('email', 'teacher@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('teacher'));
        Notification::assertNothingSent();
    }

    public function test_user_manager_cannot_edit_super_admin(): void
    {
        $manager = $this->userWithPermissions(['users.view', 'users.edit']);

        $this->actingAs($manager)
            ->get(route('users.edit', $this->superAdmin()))
            ->assertForbidden();
    }

    public function test_user_creation_permission_does_not_allow_a_non_super_admin_to_create_accounts(): void
    {
        Role::create(['name' => 'powerful', 'guard_name' => 'web'])->givePermissionTo('users.delete');
        $manager = $this->userWithPermissions(['users.create']);

        $this->actingAs($manager)->post(route('users.store'), [
            'name' => 'X',
            'email' => 'x@example.com',
            'roles' => ['powerful'],
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }
}
