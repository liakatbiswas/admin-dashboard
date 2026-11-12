<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Notifications\AccountInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        /**  The super admin provides the email address, and the
         * 'set-password' link itself serves as proof of ownership.
         */
        $user = DB::transaction(function () use ($data) {
            $role = Role::where('name', $data['role'])->where('guard_name', 'web')->lockForUpdate()->firstOrFail();
            abort_unless(auth()->user()->assignableRoles()->contains($role->name), 403);
            $user = (new User)->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Str::random(40),
                'email_verified_at' => now(),
                'is_active' => true,
                'invited_at' => now(),
            ]);

            // Skip the automatic "created" log; the manual "invited" log below replaces it
            $user->disableLogging();
            $user->save();
            $user->enableLogging();

            $user->syncRoles($role);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'role' => $data['role'],
                    'attributes' => $user->only(['name', 'email', 'phone']),
                ])
                ->log("User invited: {$user->email}");

            return $user;
        });

        $sent = $this->sendInviteSafely($user);

        return redirect()->route('users.index')->with(
            $sent ? 'success' : 'warning',
            $sent
                ? __('An invitation has been sent to :email.', ['email' => $user->email])
                : __('The account was created, but the invitation could not be sent. You can resend it from the user list.')
        );
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

        $emailChanged = $user->email !== $data['email'];
        DB::transaction(function () use ($user, $data, &$emailChanged): void {
            $role = Role::where('name', $data['role'])->where('guard_name', 'web')->lockForUpdate()->firstOrFail();
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            Gate::authorize('manage', $user);
            abort_unless(auth()->user()->assignableRoles()->contains($role->name), 403);
            $oldRoles = $user->getRoleNames()->sort()->values()->all();
            $user->fill(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null]);
            $changedEmail = $user->isDirty('email');
            $emailChanged = $changedEmail;
            if ($changedEmail) {
                $user->email_verified_at = null;
            }
            $user->save();
            if ($changedEmail) {
                $this->revokeAccess($user);
            }
            $user->syncRoles($role);
            if ($oldRoles !== [$role->name]) {
                activity()->causedBy(auth()->user())->performedOn($user)
                    ->withProperties(['old' => $oldRoles, 'new' => [$role->name]])->log('Role changed');
            }
        });
        $user->refresh();
        $notificationSent = true;
        if ($emailChanged) {
            $notificationSent = $user->invited_at
                ? $this->sendInviteSafely($user)
                : $this->sendVerificationSafely($user);
        }

        return redirect()->route('users.index')->with(
            $notificationSent ? 'success' : 'warning',
            $notificationSent
                ? __('User has been updated.')
                : __('User has been updated, but the email could not be sent. Please retry the invitation or verification email.')
        );
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

    public function invite(User $user): RedirectResponse
    {
        Gate::authorize('manage', $user);

        if (is_null($user->invited_at)) {
            return back()->withErrors(['user' => __('This user has already set a password.')]);
        }

        if (! $this->sendInviteSafely($user)) {
            return back()->with('warning', __('The invitation could not be sent. Please try again later.'));
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log("Invitation resent: {$user->email}");

        return back()->with('success', __('The invitation has been sent again.'));
    }

    private function sendInvite(User $user): void
    {
        $token = Password::broker(config('fortify.passwords'))->createToken($user);
        $user->notify(new AccountInvitation($token));
    }

    private function sendInviteSafely(User $user): bool
    {
        try {
            $this->sendInvite($user);

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    private function sendVerificationSafely(User $user): bool
    {
        try {
            $user->sendEmailVerificationNotification();

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
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
