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
            ->log("Role created: {$role->name}");

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' তৈরি হয়েছে।");
    }

    public function edit(Role $role)
    {
        abort_if($role->name === 'super-admin', 403, 'Super admin role বদলানো যাবে না।');

        return view('roles.edit', [
            'role' => $role,
            'groups' => $this->permissionGroups(),
            'selected' => $role->permissions->pluck('name')->all(),
            'isProtected' => $this->isProtected($role),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->name === 'super-admin', 403, 'Super admin role বদলানো যাবে না।');

        $data = $request->validated();

        // নিজের কাছে নেই এমন permission যা role-এ আগে থেকেই আছে, সেগুলো মুছে যেতে দেব না
        $assignable = auth()->user()->assignablePermissions()->pluck('name');
        $locked = $role->permissions->pluck('name')->diff($assignable);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions(
            collect($data['permissions'] ?? [])->merge($locked)->unique()->all()
        );

        activity()->causedBy(auth()->user())->performedOn($role)
            ->log("Role updated: {$role->name}");

        return redirect()->route('roles.index')->with('success', 'Role আপডেট হয়েছে।');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($this->isProtected($role)) {
            return back()->withErrors(['role' => 'এই role মোছা যাবে না।']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'এই role-এ user আছে, আগে তাদের role বদলান।']);
        }

        $name = $role->name;
        $role->delete();

        activity()->causedBy(auth()->user())->log("Role deleted: {$name}");

        return back()->with('success', "Role '{$name}' মুছে ফেলা হয়েছে।");
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
