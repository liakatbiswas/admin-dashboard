<?php

namespace Database\Seeders;

use App\Models\Setting;
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

        $super = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        $email = config('permissions.super_admin.email');
        $password = config('permissions.super_admin.password');

        if (app()->isProduction() && $password === 'password') {
            throw new \RuntimeException('Production-এ .env এ SUPER_ADMIN_PASSWORD সেট করুন।');
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Md. Liakat Biswas',
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $user->assignRole($super);

        Setting::firstOrCreate(['key' => 'registration_enabled'], ['value' => '1']);

    }
}
