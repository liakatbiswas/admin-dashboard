<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', 'in:active,inactive,invited,trashed']]);
        $users = User::query()
            ->with(['roles.permissions', 'permissions'])
            ->when($request->status === 'trashed', fn ($q) => $q->onlyTrashed())
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true)->whereNull('invited_at'))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->status === 'invited', fn ($q) => $q->where('is_active', true)->whereNotNull('invited_at'))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term));
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create', ['roles' => auth()->user()->assignableRoles()]);
    }

    public function store(UserRequest $request): Response
    {
        $data = $request->validated();

        $temporaryPassword = Str::password(20);
        $user = DB::transaction(function () use ($data, $temporaryPassword) {
            $roles = Role::whereIn('name', $data['roles'])->where('guard_name', 'web')
                ->orderBy('id')->lockForUpdate()->get();
            abort_unless($roles->count() === count($data['roles'])
                && $roles->pluck('name')->diff(auth()->user()->assignableRoles())->isEmpty(), 403);
            $user = (new User)->forceFill([
                'name' => $data['name'],
                'email' => ($data['email'] ?? null),
                'phone' => $data['phone'] ?? null,
                'password' => $temporaryPassword,
                'must_change_password' => true,
                'temporary_password_expires_at' => now()->addHours(24),
                'email_verified_at' => null,
                'is_active' => true,
            ]);

            // The manual creation event below includes the assigned roles.
            $user->disableLogging();
            $user->save();
            $user->enableLogging();

            $user->syncRoles($roles);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'roles' => $roles->pluck('name')->sort()->values()->all(),
                    'attributes' => $user->only(['name', 'email', 'phone']),
                ])
                ->log("User created: {$user->email}");

            activity('security')->causedBy(auth()->user())->performedOn($user)
                ->log('Temporary password issued');

            return $user;
        });

        return $this->temporaryPasswordResponse($user, $temporaryPassword);
    }

    public function edit(User $user)
    {
        Gate::authorize('manage', $user);
        if ($user->is(auth()->user())) {
            return redirect()->route('profile.edit');
        }

        return view('users.edit', [
            'user' => $user->load('roles'),
            'roles' => auth()->user()->assignableRoles(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('manage', $user);
        $data = $request->validated();

        $emailChanged = $user->email !== ($data['email'] ?? null);
        DB::transaction(function () use ($user, $data, &$emailChanged): void {
            $roles = Role::whereIn('name', $data['roles'])->where('guard_name', 'web')
                ->orderBy('id')->lockForUpdate()->get();
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            Gate::authorize('manage', $user);
            abort_unless($roles->count() === count($data['roles'])
                && $roles->pluck('name')->diff(auth()->user()->assignableRoles())->isEmpty(), 403);
            $oldRoles = $user->getRoleNames()->sort()->values()->all();
            $user->fill(['name' => $data['name'], 'email' => ($data['email'] ?? null), 'phone' => $data['phone'] ?? null]);
            $changedEmail = $user->isDirty('email');
            $emailChanged = $changedEmail;
            if ($changedEmail) {
                $user->email_verified_at = null;
            }
            $user->save();
            if ($changedEmail) {
                $this->revokeAccess($user);
            }
            $user->syncRoles($roles);
            $newRoles = $roles->pluck('name')->sort()->values()->all();
            if ($oldRoles !== $newRoles) {
                activity()->causedBy(auth()->user())->performedOn($user)
                    ->withProperties(['old' => $oldRoles, 'new' => $newRoles])->log('Role changed');
            }
        });
        $user->refresh();

        return redirect()->route('users.index')->with('success', __('User has been updated.'));
    }

    public function toggle(User $user): RedirectResponse
    {
        Gate::authorize('manage', $user);

        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => __('You cannot deactivate your account.')]);
        }

        // is_active is in the activity log options, so this update is logged automatically
        $user->update(['is_active' => ! $user->is_active]);

        if (! $user->is_active) {
            $this->revokeAccess($user);
        }

        return back()->with('success', $user->is_active ? __('User has been activated.') : __('User has been deactivated.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('manage', $user);

        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => __('You cannot delete your account.')]);
        }

        $this->revokeAccess($user); // must run before delete()
        $user->delete();            // the "deleted" event is logged automatically

        return back()->with('success', __('User has been deleted (can be restored).'));
    }

    public function restore(User $user): RedirectResponse
    {
        Gate::authorize('manage', $user);
        $user->restore();

        // "restored" is not logged by default, so log it manually
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log("User restored: {$user->email}");

        return back()->with('success', __('The user has been brought back.'));
    }

    public function forceDelete(User $user): RedirectResponse
    {
        Gate::authorize('manage', $user);

        if (! $user->trashed()) {
            return back()->withErrors(['user' => __('Only deleted users can be permanently removed.')]);
        }

        $avatar = $user->avatar;

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->roles()->detach();
            $user->permissions()->detach();
            $user->forceDelete();
        });

        if ($avatar) {
            Storage::disk('public')->delete($avatar);
        }

        return back()->with('success', __('The user has been permanently deleted.'));
    }

    public function resetTemporaryPassword(User $user): Response
    {
        abort_if($user->is(auth()->user()), 403);
        Gate::authorize('manage', $user);

        $temporaryPassword = Str::password(20);
        DB::transaction(function () use ($user, $temporaryPassword): void {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            Gate::authorize('manage', $user);
            $user->forceFill([
                'password' => $temporaryPassword,
                'must_change_password' => true,
                'temporary_password_expires_at' => now()->addHours(24),
                'invited_at' => null,
            ])->save();
            $this->revokeAccess($user);
            Password::broker(config('fortify.passwords'))->deleteToken($user);
            activity('security')->causedBy(auth()->user())->performedOn($user)
                ->log('Temporary password reset');
        });

        return $this->temporaryPasswordResponse($user->refresh(), $temporaryPassword);
    }

    private function temporaryPasswordResponse(User $user, #[\SensitiveParameter] string $temporaryPassword): Response
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        return response()->view('users.temporary-password', compact('user', 'temporaryPassword'))
            ->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }

    private function revokeAccess(User $user): void
    {
        $user->tokens()->delete();                                   // Sanctum tokens
        $user->increment('auth_version');
        $user->forceFill(['remember_token' => null])->saveQuietly(); // invalidate remember-me cookie

        if (config('session.driver') === 'database') {               // web sessions
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }
    }
}
