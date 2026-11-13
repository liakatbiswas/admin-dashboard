<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserMultiRoleTest extends TestCase
{
    public function test_super_admin_can_create_one_account_with_multiple_roles(): void
    {
        Notification::fake();

        $this->actingAs($this->superAdmin())->post(route('users.store'), [
            'name' => 'Teacher Guardian', 'email' => 'teacher-guardian@example.com',
            'roles' => ['teacher', 'guardian'],
        ])->assertOk()->assertViewIs('users.temporary-password');

        $user = User::where('email', 'teacher-guardian@example.com')->sole();
        $this->assertSame(['guardian', 'teacher'], $user->getRoleNames()->sort()->values()->all());
        $log = Activity::where('subject_id', $user->id)->where('description', "User created: {$user->email}")->sole();
        $this->assertSame(['guardian', 'teacher'], $log->properties->get('roles'));
        Notification::assertNothingSent();
    }

    public function test_editing_personal_details_preserves_all_selected_roles_without_a_role_change_log(): void
    {
        $user = User::factory()->create()->assignRole(['teacher', 'guardian']);

        $this->actingAs($this->superAdmin())->put(route('users.update', $user), [
            'name' => 'Updated name', 'email' => $user->email, 'roles' => ['teacher', 'guardian'],
        ])->assertRedirect(route('users.index'))->assertSessionHasNoErrors();

        $this->assertSame('Updated name', $user->fresh()->name);
        $this->assertSame(['guardian', 'teacher'], $user->fresh()->getRoleNames()->sort()->values()->all());
        $this->assertSame(0, Activity::where('subject_id', $user->id)->where('description', 'Role changed')->count());
    }

    public function test_role_changes_update_combined_access_and_record_old_and_new_roles(): void
    {
        Role::findByName('teacher')->givePermissionTo('users.view');
        Role::findByName('guardian')->givePermissionTo('activity.view');
        $user = User::factory()->create()->assignRole(['teacher', 'guardian']);
        $this->actingAs($user)->get(route('users.index'))->assertOk();
        $this->get(route('activity.index'))->assertOk();
        $this->flushSession();

        $this->actingAs($this->superAdmin())->put(route('users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'phone' => $user->phone, 'roles' => ['guardian', 'staff'],
        ])->assertRedirect(route('users.index'))->assertSessionHasNoErrors();

        $this->assertSame(['guardian', 'staff'], $user->fresh()->getRoleNames()->sort()->values()->all());
        $log = Activity::where('subject_id', $user->id)->where('description', 'Role changed')->sole();
        $this->assertSame(['guardian', 'teacher'], $log->properties->get('old'));
        $this->assertSame(['guardian', 'staff'], $log->properties->get('new'));
        $this->flushSession();
        $this->actingAs($user->fresh())->get(route('users.index'))->assertForbidden();
        $this->get(route('activity.index'))->assertOk();
    }

    #[DataProvider('invalidRoles')]
    public function test_invalid_role_selections_do_not_change_an_account(mixed $roles, string $error): void
    {
        $user = User::factory()->create()->assignRole(['teacher', 'guardian']);

        $this->actingAs($this->superAdmin())->put(route('users.update', $user), [
            'name' => 'Should not save', 'email' => $user->email, 'roles' => $roles,
        ])->assertSessionHasErrors($error);

        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertSame(['guardian', 'teacher'], $user->fresh()->getRoleNames()->sort()->values()->all());
    }

    public static function invalidRoles(): array
    {
        return [
            'none selected' => [[], 'roles'],
            'single string' => ['teacher', 'roles'],
            'unknown role' => [['teacher', 'unknown-role'], 'roles.1'],
            'duplicate role' => [['teacher', 'teacher'], 'roles.0'],
            'nested value' => [['teacher', ['guardian']], 'roles.1'],
        ];
    }

    public function test_non_super_admin_with_multiple_roles_cannot_assign_roles(): void
    {
        $manager = User::factory()->create()->assignRole(['admin', 'teacher', 'guardian']);
        $manager->givePermissionTo(['users.edit', 'roles.manage']);
        $user = User::factory()->create()->assignRole('staff');

        $this->actingAs($manager)->put(route('users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'roles' => ['staff', 'super-admin'],
        ])->assertForbidden();

        $this->assertSame(['staff'], $user->fresh()->getRoleNames()->all());
    }

    public function test_edit_form_selects_all_existing_roles(): void
    {
        $user = User::factory()->create()->assignRole(['teacher', 'guardian']);

        $html = $this->actingAs($this->superAdmin())->get(route('users.edit', $user))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/name="roles\[\]" value="teacher"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/name="roles\[\]" value="guardian"[^>]*checked/', $html);
    }

    public function test_role_selections_survive_validation_errors(): void
    {
        $this->actingAs($this->superAdmin())->from(route('users.create'))->post(route('users.store'), [
            'name' => '', 'email' => 'invalid-form@example.com',
            'roles_submitted' => '1', 'roles' => ['teacher', 'guardian'],
        ])->assertSessionHasErrors('name');

        $html = $this->get(route('users.create'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/name="roles\[\]" value="teacher"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/name="roles\[\]" value="guardian"[^>]*checked/', $html);
        $this->assertDatabaseMissing('users', ['email' => 'invalid-form@example.com']);
    }

    public function test_empty_role_selection_stays_empty_after_validation_errors(): void
    {
        $user = User::factory()->create()->assignRole(['teacher', 'guardian']);
        $url = route('users.edit', $user);

        $this->actingAs($this->superAdmin())->from($url)->put(route('users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'roles_submitted' => '1',
        ])->assertSessionHasErrors('roles');

        $html = $this->get($url)->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/name="roles\[\]"[^>]*checked/', $html);
    }

    public function test_malformed_roles_render_validation_errors_without_a_server_error(): void
    {
        $user = User::factory()->create()->assignRole('teacher');
        $url = route('users.edit', $user);

        $this->actingAs($this->superAdmin())->from($url)->put(route('users.update', $user), [
            'name' => $user->name, 'email' => $user->email,
            'roles_submitted' => '1', 'roles' => 'teacher',
        ])->assertSessionHasErrors('roles');

        $this->get($url)->assertOk();
    }
}
