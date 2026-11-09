<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    // Super admin-এর জন্য Gate::before আগেই true দেয়
    public function manage(User $auth, User $target): bool
    {
        return ! $target->hasRole('super-admin');
    }
}
