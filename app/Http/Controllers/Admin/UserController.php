<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Notifications\AccountInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->with('roles')
            ->when($request->status === 'trashed', fn ($q) => $q->onlyTrashed())
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
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
        $user = (new User)->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Str::random(40),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->save();
        $user->syncRoles($data['role']);

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
            $user->sendEmailVerificationNotification();
        }

        if ($user->isNot(auth()->user()) && isset($data['role'])) {
            $user->syncRoles($data['role']);
        }

        return redirect()->route('users.index')->with('success', 'User has been updated.');
    }

    public function toggle(User $user): RedirectResponse
    {
        Gate::authorize('manage', $user);

        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'You cannot deactivate your account.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        if (! $user->is_active) {
            $user->tokens()->delete(); // Sanctum token deleted
        }

        return back()->with('success', $user->is_active ? 'User has been activated.' : 'User has been deactivated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('manage', $user);

        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'You cannot delete your account.']);
        }

        $user->tokens()->delete();
        $user->delete();

        return back()->with('success', 'User has been deleted (can be restored).');
    }

    public function restore(User $user): RedirectResponse
    {
        Gate::authorize('manage', $user);
        $user->restore();

        return back()->with('success', 'The user has been brought back.');
    }

    public function invite(User $user): RedirectResponse
    {
        Gate::authorize('manage', $user);
        $this->sendInvite($user);

        return back()->with('success', 'The invitation has been sent again.');
    }

    private function sendInvite(User $user): void
    {
        $token = Password::broker(config('fortify.passwords'))->createToken($user);
        $user->notify(new AccountInvitation($token));
    }
}
