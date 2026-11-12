<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount([
            'users' => fn ($query) => $query->withTrashed(),
            'permissions',
        ])->orderBy('name')->get();

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        return view('roles.create', [
            'groups' => $this->permissionGroups(),
            'selected' => [],
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
            $role->syncPermissions($data['permissions'] ?? []);

            activity()->causedBy(auth()->user())->performedOn($role)
                ->withProperties([
                    'permissions' => $role->permissions()->orderBy('name')->pluck('name')->all(),
                ])
                ->log("Role created: {$role->name}");
        });

        return redirect()->route('roles.index')->with('success', "Role '{$data['name']}' has been created.");
    }

    public function edit(Role $role)
    {
        $this->authorizeRole($role);

        return view('roles.edit', [
            'role' => $role,
            'groups' => $this->permissionGroups(),
            'selected' => $role->permissions->pluck('name')->all(),
            'isProtected' => $this->isProtected($role),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorizeRole($role);

        $data = $request->validated();

        DB::transaction(function () use ($role, $data): void {
            $role = Role::query()->lockForUpdate()->findOrFail($role->id);
            $this->authorizeRole($role);
            /* I will not allow the removal of permissions that are not present
             * in my own set but are already included in the role.
             */
            $assignable = auth()->user()->assignablePermissions()->pluck('name');
            $locked = $role->permissions->pluck('name')->diff($assignable);
            $oldPermissions = $role->permissions->pluck('name')->sort()->values()->all();

            $role->update(['name' => $data['name']]);
            $role->syncPermissions(
                collect($data['permissions'] ?? [])->merge($locked)->unique()->all()
            );
            $newPermissions = $role->permissions()->orderBy('name')->pluck('name')->all();

            activity()->causedBy(auth()->user())->performedOn($role)
                ->withProperties([
                    'permissions' => [
                        'old' => $oldPermissions,
                        'new' => $newPermissions,
                        'added' => array_values(array_diff($newPermissions, $oldPermissions)),
                        'removed' => array_values(array_diff($oldPermissions, $newPermissions)),
                    ],
                ])
                ->log("Role updated: {$role->name}");

        });

        return redirect()->route('roles.index')->with('success', 'Role has been updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($this->isProtected($role)) {
            return back()->withErrors(['role' => 'This role cannot be deleted.']);
        }

        return DB::transaction(function () use ($role): RedirectResponse {
            $role = Role::query()->lockForUpdate()->findOrFail($role->id);
            $this->authorizeRole($role);
            if (User::withTrashed()->whereHas('roles', fn ($query) => $query->whereKey($role->id))->exists()) {
                return back()->withErrors(['role' => 'There are users in this role; change their roles first.']);
            }
            $name = $role->name;
            $role->delete();
            activity()->causedBy(auth()->user())->log("Role deleted: {$name}");

            return back()->with('success', "Role '{$name}' has been deleted.");
        });

    }

    private function authorizeRole(Role $role): void
    {
        abort_if($role->name === 'super-admin', 403, 'The super admin role cannot be changed.');
        if (auth()->user()->hasRole('super-admin')) {
            return;
        }
        abort_if($this->isProtected($role), 403, 'Only a super admin can change this role.');
        abort_unless($role->permissions->pluck('name')->diff(auth()->user()->getAllPermissions()->pluck('name'))->isEmpty(), 403);
        User::withTrashed()->whereHas('roles', fn ($query) => $query->whereKey($role->id))
            ->with(['roles.permissions', 'permissions'])->each(function (User $user): void {
                Gate::authorize('manage', $user);
            });
    }

    private function isProtected(Role $role): bool
    {
        return in_array($role->name, config('permissions.protected_roles'), true);
    }

    private function permissionGroups()
    {
        return auth()->user()->assignablePermissions()
            ->groupBy(fn ($p) => Str::before($p->name, '.'));
    }
}
