<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const LEGACY_PERMISSION = 'settings.manage';

    private const NEW_PERMISSIONS = [
        'settings.site.manage' => 'Manage Site Settings',
        'admission.manage' => 'Enable or Disable Admissions',
    ];

    public function up(): void
    {
        $tables = config('permission.table_names');
        $columns = config('permission.column_names');
        $permissionKey = $columns['permission_pivot_key'] ?: 'permission_id';
        $roleKey = $columns['role_pivot_key'] ?: 'role_id';
        $modelKey = $columns['model_morph_key'] ?: 'model_id';
        $legacyId = DB::table($tables['permissions'])
            ->where('name', self::LEGACY_PERMISSION)
            ->where('guard_name', 'web')
            ->value('id');

        $newIds = [];

        foreach (self::NEW_PERMISSIONS as $name => $label) {
            $newIds[] = DB::table($tables['permissions'])->where([
                'name' => $name,
                'guard_name' => 'web',
            ])->value('id') ?? DB::table($tables['permissions'])->insertGetId([
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($legacyId) {
            foreach ($newIds as $newId) {
                DB::table($tables['role_has_permissions'])->insertOrIgnoreUsing(
                    [$permissionKey, $roleKey],
                    DB::table($tables['role_has_permissions'])
                        ->selectRaw('? as '.$permissionKey.', '.$roleKey, [$newId])
                        ->where($permissionKey, $legacyId)
                );

                DB::table($tables['model_has_permissions'])->insertOrIgnoreUsing(
                    [$permissionKey, 'model_type', $modelKey],
                    DB::table($tables['model_has_permissions'])
                        ->selectRaw('? as '.$permissionKey.', model_type, '.$modelKey, [$newId])
                        ->where($permissionKey, $legacyId)
                );
            }

            DB::table($tables['role_has_permissions'])->where($permissionKey, $legacyId)->delete();
            DB::table($tables['model_has_permissions'])->where($permissionKey, $legacyId)->delete();
            DB::table($tables['permissions'])->where('id', $legacyId)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $tables = config('permission.table_names');
        $columns = config('permission.column_names');
        $permissionKey = $columns['permission_pivot_key'] ?: 'permission_id';
        $roleKey = $columns['role_pivot_key'] ?: 'role_id';
        $modelKey = $columns['model_morph_key'] ?: 'model_id';
        $legacyId = DB::table($tables['permissions'])->where([
            'name' => self::LEGACY_PERMISSION,
            'guard_name' => 'web',
        ])->value('id') ?? DB::table($tables['permissions'])->insertGetId([
            'name' => self::LEGACY_PERMISSION,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $newIds = DB::table($tables['permissions'])
            ->whereIn('name', array_keys(self::NEW_PERMISSIONS))
            ->where('guard_name', 'web')
            ->pluck('id');

        if ($newIds->isNotEmpty()) {
            DB::table($tables['role_has_permissions'])->insertOrIgnoreUsing(
                [$permissionKey, $roleKey],
                DB::table($tables['role_has_permissions'])
                    ->selectRaw('? as '.$permissionKey.', '.$roleKey, [$legacyId])
                    ->whereIn($permissionKey, $newIds)
            );

            DB::table($tables['model_has_permissions'])->insertOrIgnoreUsing(
                [$permissionKey, 'model_type', $modelKey],
                DB::table($tables['model_has_permissions'])
                    ->selectRaw('? as '.$permissionKey.', model_type, '.$modelKey, [$legacyId])
                    ->whereIn($permissionKey, $newIds)
            );

            DB::table($tables['role_has_permissions'])->whereIn($permissionKey, $newIds)->delete();
            DB::table($tables['model_has_permissions'])->whereIn($permissionKey, $newIds)->delete();
            DB::table($tables['permissions'])->whereIn('id', $newIds)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
