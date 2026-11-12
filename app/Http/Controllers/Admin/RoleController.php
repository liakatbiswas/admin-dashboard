<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])->orderBy('name')->get();

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

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        activity()->causedBy(auth()->user())->performedOn($role)
            ->withProperties([
                'permissions' => $role->permissions()->orderBy('name')->pluck('name')->all(),
            ])
            ->log("Role created: {$role->name}");

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' has been created.");
    }

    public function edit(Role $role)
    {
        abort_if($role->name === 'super-admin', 403, 'The super admin role cannot be changed.');

        return view('roles.edit', [
            'role' => $role,
            'groups' => $this->permissionGroups(),
            'selected' => $role->permissions->pluck('name')->all(),
            'isProtected' => $this->isProtected($role),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->name === 'super-admin', 403, 'The super admin role cannot be changed.');

        $data = $request->validated();

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

        return redirect()->route('roles.index')->with('success', 'Role has been updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($this->isProtected($role)) {
            return back()->withErrors(['role' => 'This role cannot be deleted.']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'There are users in this role; change their roles first.']);
        }

        $name = $role->name;
        $role->delete();

        activity()->causedBy(auth()->user())->log("Role deleted: {$name}");

        return back()->with('success', "Role '{$name}' has been deleted.");
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
