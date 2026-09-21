<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            UserRole::Owner,
            UserRole::Admin,
            UserRole::Collections,
            UserRole::SalesRep,
        ], true);
    }

    public function view(User $user, Customer $customer): bool
    {
        if ($customer->company_id !== $user->company_id) {
            return false;
        }

        if ($user->role === UserRole::SalesRep) {
            return $customer->sales_rep_name !== null
                && $customer->sales_rep_name === $user->qb_sales_rep_name;
        }

        return in_array($user->role, [
            UserRole::Owner,
            UserRole::Admin,
            UserRole::Collections,
        ], true);
    }
}
