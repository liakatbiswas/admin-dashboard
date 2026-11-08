<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndSuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.manage', 'settings.manage', 'activity.view',
        ] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $super = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        $user = User::firstOrCreate(
            ['email' => 'liakatbiswas@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => '12345', // 'hashed' cast নিজে hash করবে
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $user->assignRole($super);

        Setting::set('registration_enabled', '1');
    }
}
