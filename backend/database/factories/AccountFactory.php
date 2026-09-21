<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        $balance = fake()->randomFloat(2, -5000, 50000);

        return [
            'company_id' => Company::factory(),
            'qb_list_id' => strtoupper(fake()->unique()->bothify('80000###-##########')),
            'full_name' => fake()->unique()->words(2, true),
            'account_type' => fake()->randomElement(['Bank', 'AccountsReceivable', 'Income', 'Expense']),
            'is_active' => true,
            'balance' => $balance,
            'total_balance' => $balance,
        ];
    }
}
