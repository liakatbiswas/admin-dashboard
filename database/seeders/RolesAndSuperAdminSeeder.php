<?php

namespace Database\Seeders;

use App\Models\AdmissionOnOff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndSuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        foreach (config('permissions.protected_roles') as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $super = Role::findByName('super-admin', 'web');

        $email = config('permissions.super_admin.email');
        $password = config('permissions.super_admin.password');

        if (app()->isProduction() && $password === 'password') {
            throw new \RuntimeException('Set SUPER_ADMIN_PASSWORD in the production environment.');
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Md. Liakat Biswas',
                'password' => $password,
                'email_verified_at' => null,
                'is_active' => true,
            ]
        );
        if ($user->wasRecentlyCreated) {
            $user->forceFill(['must_change_password' => true])->saveQuietly();
        }
        $user->assignRole($super);

        AdmissionOnOff::firstOrCreate(['id' => 1], ['is_on' => true]);

    }
}
