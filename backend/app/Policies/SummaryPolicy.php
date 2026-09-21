<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class SummaryPolicy
{
    public function view(User $user): bool
    {
        return in_array($user->role, [
            UserRole::Owner,
            UserRole::Admin,
            UserRole::Collections,
            UserRole::SalesRep,
        ], true);
    }

    public function viewKeyAccounts(User $user): bool
    {
        return in_array($user->role, [
            UserRole::Owner,
            UserRole::Admin,
        ], true);
    }
}
