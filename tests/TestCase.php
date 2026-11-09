<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\RolesAndSuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = RolesAndSuperAdminSeeder::class;

    protected function superAdmin(): User
    {
        return User::where('email', config('permissions.super_admin.email'))->firstOrFail();
    }

    protected function userWithPermissions(array $permissions): User
    {
        $role = Role::firstOrCreate(['name' => 'test-role', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        return User::factory()->create()->assignRole($role);
    }
}
