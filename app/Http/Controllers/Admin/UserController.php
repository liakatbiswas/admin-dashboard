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
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->with(['roles.permissions', 'permissions'])
            ->when($request->status === 'trashed', fn ($q) => $q->onlyTrashed())
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->status === 'invited', fn ($q) => $q->whereNotNull('invited_at'))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term));
            })
            ->latest()
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

            $user->syncRoles($data['role']);

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

        $this->sendInvite($user);

        return redirect()->route('users.index')
            ->with('success', "An invitation has been sent to {$user->email}.");
    }

    public function edit(User $user)
    {
        Gate::authorize('manage', $user);

        return view('users.edit', [
            'user' => $user->load('roles'),
            'roles' => auth()->user()->assignableRoles(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('manage', $user);
        $data = $request->validated();

        // Roles before the change, for the activity log
        $oldRoles = $user->getRoleNames()->sort()->values()->all();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        if ($user->wasChanged('email')) {
            if ($user->invited_at) {
                // Invite not accepted yet: the old token belongs to the old address, so send a fresh one
                $this->sendInvite($user);
            } else {
                $user->sendEmailVerificationNotification();
            }
        }

        if ($user->isNot(auth()->user()) && isset($data['role'])) {
            $user->syncRoles($data['role']);

            $newRoles = [$data['role']];

            if ($oldRoles !== $newRoles) {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($user)
                    ->withProperties(['old' => $oldRoles, 'new' => $newRoles])
                    ->log('Role changed');
            }
        }

        return redirect()->route('users.index')->with('success', 'User has been updated.');
    }

    public function toggle(User $user): RedirectResponse
    {
        Gate::authorize('manage', $user);

        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'You cannot deactivate your account.']);
        }

        // is_active is in the activity log options, so this update is logged automatically
        $user->update(['is_active' => ! $user->is_active]);

        if (! $user->is_active) {
            $this->revokeAccess($user);
        }

        return back()->with('success', $user->is_active ? 'User has been activated.' : 'User has been deactivated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('manage', $user);

        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'You cannot delete your account.']);
        }

        $this->revokeAccess($user); // must run before delete()
        $user->delete();            // the "deleted" event is logged automatically

        return back()->with('success', 'User has been deleted (can be restored).');
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

        return back()->with('success', 'The user has been brought back.');
    }

    public function invite(User $user): RedirectResponse
    {
        Gate::authorize('manage', $user);

        if (is_null($user->invited_at)) {
            return back()->withErrors(['user' => 'This user has already set a password.']);
        }

        $this->sendInvite($user);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log("Invitation resent: {$user->email}");

        return back()->with('success', 'The invitation has been sent again.');
    }

    private function sendInvite(User $user): void
    {
        $token = Password::broker(config('fortify.passwords'))->createToken($user);
        $user->notify(new AccountInvitation($token));
    }

    private function revokeAccess(User $user): void
    {
        $user->tokens()->delete();                                   // Sanctum tokens
        $user->forceFill(['remember_token' => null])->saveQuietly(); // invalidate remember-me cookie

        if (config('session.driver') === 'database') {               // web sessions
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }
    }
}
