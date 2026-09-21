<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            UserRole::Owner,
            UserRole::Admin,
            UserRole::Collections,
        ], true);
    }

    public function view(User $user, Account $account): bool
    {
        return $this->viewAny($user)
            && $account->company_id === $user->company_id;
    }
}
