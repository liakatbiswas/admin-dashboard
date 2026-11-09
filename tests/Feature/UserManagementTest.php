<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountInvitation;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public function test_inactive_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertGuest();
    }

    public function test_active_user_can_log_in(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_super_admin_creates_user_and_invite_is_sent(): void
    {
        Notification::fake();
        Role::create(['name' => 'teacher', 'guard_name' => 'web']);

        $this->actingAs($this->superAdmin())->post(route('users.store'), [
            'name' => 'Teacher One',
            'email' => 'teacher@example.com',
            'role' => 'teacher',
        ])->assertRedirect(route('users.index'));

        $user = User::where('email', 'teacher@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('teacher'));
        Notification::assertSentTo($user, AccountInvitation::class);
    }

    public function test_user_manager_cannot_edit_super_admin(): void
    {
        $manager = $this->userWithPermissions(['users.view', 'users.edit']);

        $this->actingAs($manager)
            ->get(route('users.edit', $this->superAdmin()))
            ->assertForbidden();
    }

    public function test_cannot_assign_a_role_stronger_than_your_own(): void
    {
        Role::create(['name' => 'powerful', 'guard_name' => 'web'])->givePermissionTo('users.delete');
        $manager = $this->userWithPermissions(['users.create']);

        $this->actingAs($manager)->post(route('users.store'), [
            'name' => 'X',
            'email' => 'x@example.com',
            'role' => 'powerful',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }
}
