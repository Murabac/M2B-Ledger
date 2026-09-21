<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        $balance = fake()->randomFloat(2, -200, 8000);

        return [
            'company_id' => Company::factory(),
            'qb_list_id' => strtoupper(fake()->unique()->bothify('80000###-##########')),
            'full_name' => fake()->unique()->company(),
            'is_active' => true,
            'balance' => $balance,
            'total_balance' => $balance,
            'sales_rep_name' => fake()->optional(0.7)->randomElement(['Pat Lee', 'Alex Kim']),
        ];
    }
}
