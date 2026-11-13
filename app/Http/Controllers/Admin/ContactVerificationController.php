<?php

namespace App\Http\Controllers\Admin;

use App\Actions\VerifyUserContacts;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()->whereKeyNot($request->user()->id)->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('email_verified_at')->orWhereNull('phone_verified_at'))
            ->with('roles')->orderBy('id')->paginate(15);

        return view('users.verifications', compact('users'));
    }

    public function store(Request $request, User $user, VerifyUserContacts $verify): RedirectResponse
    {
        abort_if($user->is($request->user()), 403);
        $data = $request->validate([
            'field' => ['required', 'in:email,phone'],
            'value' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $verify->handle($user, $request->user(), $data['field'], $data['value'], $data['reason']);

        return back()->with('success', __('Contact details verified.'));
    }
}
