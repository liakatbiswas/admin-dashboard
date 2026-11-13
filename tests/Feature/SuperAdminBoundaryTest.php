<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndSuperAdminSeeder;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminBoundaryTest extends TestCase
{
    public function test_seeding_preserves_existing_credentials_and_role_permissions(): void
    {
        $superAdmin = $this->superAdmin();
        $password = $superAdmin->password;
        Role::findByName('teacher')->givePermissionTo('users.view');

        $this->seed(RolesAndSuperAdminSeeder::class);

        $this->assertSame(
            ['admin', 'clerk', 'guardian', 'staff', 'student', 'super-admin', 'teacher'],
            Role::where('guard_name', 'web')->orderBy('name')->pluck('name')->all()
        );
        $this->assertSame($password, $superAdmin->fresh()->password);
        $this->assertSame(['users.view'], Role::findByName('teacher')->permissions->pluck('name')->all());
    }

    #[DataProvider('nonSuperAdminRoles')]
    public function test_reserved_abilities_are_denied_even_with_direct_and_role_permissions(string $roleName): void
    {
        $permissions = ['users.create', 'users.edit', 'users.delete', 'roles.manage'];
        $role = Role::findByName($roleName);
        $role->givePermissionTo($permissions);
        $user = User::factory()->create()->assignRole($role);
        $user->givePermissionTo($permissions);

        foreach ($permissions as $permission) {
            $this->assertFalse($user->can($permission), $permission);
        }

        $this->assertTrue($user->assignableRoles()->isEmpty());
        $this->assertTrue($user->assignablePermissions()->isEmpty());
    }

    public static function nonSuperAdminRoles(): array
    {
        return [
            'admin' => ['admin'],
            'teacher' => ['teacher'],
            'clerk' => ['clerk'],
            'student' => ['student'],
            'guardian' => ['guardian'],
            'staff' => ['staff'],
        ];
    }

    #[DataProvider('restrictedEndpoints')]
    public function test_direct_requests_cannot_bypass_super_admin_restrictions(string $method, string $path): void
    {
        Notification::fake();
        $manager = User::factory()->create()->assignRole('admin');
        $manager->givePermissionTo(['users.view', 'users.create', 'users.edit', 'users.delete', 'roles.manage']);
        $target = User::factory()->create()->assignRole('teacher');
        $role = Role::create(['name' => 'custom-role', 'guard_name' => 'web']);
        $path = str_replace(['{user}', '{role}'], [(string) $target->id, (string) $role->id], $path);
        $userCount = User::count();

        $this->actingAs($manager)->call($method, $path, [
            'name' => 'blocked-change', 'email' => 'blocked@example.com',
            'roles' => ['staff'], 'permissions' => ['users.view'],
        ])->assertForbidden();

        $this->assertSame($userCount, User::count());
        $this->assertSame($target->email, $target->fresh()->email);
        $this->assertTrue($target->fresh()->is_active);
        $this->assertSame(['teacher'], $target->fresh()->getRoleNames()->all());
        $this->assertSame('custom-role', $role->fresh()->name);
        $this->assertSame([], $role->fresh()->permissions->pluck('name')->all());
        Notification::assertNothingSent();
    }

    public static function restrictedEndpoints(): array
    {
        return [
            'account form' => ['GET', '/admin/users/create'],
            'account creation' => ['POST', '/admin/users'],
            'account edit form' => ['GET', '/admin/users/{user}/edit'],
            'account role update' => ['PUT', '/admin/users/{user}'],
            'account status' => ['PATCH', '/admin/users/{user}/toggle'],
            'temporary password reset' => ['POST', '/admin/users/{user}/temporary-password'],
            'account deletion' => ['DELETE', '/admin/users/{user}'],
            'role list' => ['GET', '/admin/roles'],
            'role form' => ['GET', '/admin/roles/create'],
            'role creation' => ['POST', '/admin/roles'],
            'role edit form' => ['GET', '/admin/roles/{role}/edit'],
            'role permissions' => ['PUT', '/admin/roles/{role}'],
            'role deletion' => ['DELETE', '/admin/roles/{role}'],
        ];
    }

    public function test_non_super_admin_does_not_see_restricted_management_controls(): void
    {
        $manager = User::factory()->create()->assignRole('admin');
        $manager->givePermissionTo(['users.view', 'users.create', 'users.edit', 'users.delete', 'roles.manage']);
        $target = User::factory()->create()->assignRole('teacher');

        $this->actingAs($manager)->get(route('users.index'))->assertOk()
            ->assertDontSee(route('users.create'), false)
            ->assertDontSee(route('roles.index'), false)
            ->assertDontSee(route('users.toggle', $target), false)
            ->assertDontSee(route('users.edit', $target), false);
    }

    #[DataProvider('nonSuperAdminRoles')]
    public function test_super_admin_can_change_permissions_without_renaming_a_required_role(string $roleName): void
    {
        $role = Role::findByName($roleName);

        $this->actingAs($this->superAdmin())->put(route('roles.update', $role), [
            'name' => 'renamed-teacher', 'permissions' => ['users.view'],
        ])->assertRedirect(route('roles.index'))->assertSessionHasNoErrors();

        $this->assertSame($roleName, $role->fresh()->name);
        $this->assertSame(['users.view'], $role->fresh()->permissions->pluck('name')->all());
    }

    public function test_super_admin_can_change_another_users_role(): void
    {
        $target = User::factory()->create()->assignRole('teacher');

        $this->actingAs($this->superAdmin())->put(route('users.update', $target), [
            'name' => $target->name, 'email' => $target->email, 'roles' => ['staff'],
        ])->assertRedirect(route('users.index'))->assertSessionHasNoErrors();

        $this->assertSame(['staff'], $target->fresh()->getRoleNames()->all());
    }

    public function test_super_admin_can_deactivate_and_reactivate_an_account(): void
    {
        $target = User::factory()->create()->assignRole('teacher');

        $this->actingAs($this->superAdmin())->patch(route('users.toggle', $target))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertFalse($target->fresh()->is_active);

        $this->patch(route('users.toggle', $target))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertTrue($target->fresh()->is_active);
    }
}
