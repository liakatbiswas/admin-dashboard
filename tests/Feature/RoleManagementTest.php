<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    public function test_super_admin_creates_role_with_permissions(): void
    {
        $this->actingAs($this->superAdmin())->post(route('roles.store'), [
            'name' => 'content-editor',
            'permissions' => ['users.view', 'activity.view'],
        ])->assertRedirect(route('roles.index'));

        $role = Role::findByName('content-editor');
        $this->assertEqualsCanonicalizing(
            ['users.view', 'activity.view'],
            $role->permissions->pluck('name')->all()
        );
    }

    public function test_protected_roles_cannot_be_deleted(): void
    {
        $this->actingAs($this->superAdmin());

        foreach (['super-admin', 'admin', 'teacher', 'clerk', 'student', 'guardian', 'staff'] as $name) {
            $this->delete(route('roles.destroy', Role::findByName($name)))
                ->assertSessionHasErrors('role');
            $this->assertDatabaseHas('roles', ['name' => $name]);
        }
    }

    public function test_role_with_users_cannot_be_deleted(): void
    {
        $role = Role::create(['name' => 'custom-teacher', 'guard_name' => 'web']);
        User::factory()->create()->assignRole($role);

        $this->actingAs($this->superAdmin())
            ->delete(route('roles.destroy', $role))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('roles', ['name' => 'custom-teacher']);
    }

    public function test_super_admin_role_is_not_editable(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('roles.edit', Role::findByName('super-admin')))
            ->assertForbidden();
    }

    public function test_role_management_permission_does_not_allow_a_non_super_admin_to_create_roles(): void
    {
        $manager = $this->userWithPermissions(['roles.manage']);

        $this->actingAs($manager)->post(route('roles.store'), [
            'name' => 'sneaky',
            'permissions' => ['users.delete'],
        ])->assertForbidden();

        $this->assertDatabaseMissing('roles', ['name' => 'sneaky']);
    }

    public function test_user_without_permission_gets_403(): void
    {
        $student = User::factory()->create(['student_id' => 'NGHS20270600001'])->assignRole('student');

        $this->actingAs($student)->get(route('roles.index'))->assertForbidden();
    }
}
