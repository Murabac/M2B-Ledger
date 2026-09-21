<?php

namespace Database\Factories;

use App\Enums\SyncLogStatus;
use App\Models\Company;
use App\Models\SyncLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyncLog>
 */
class SyncLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'status' => SyncLogStatus::Success,
            'accounts_count' => fake()->numberBetween(5, 40),
            'customers_count' => fake()->numberBetween(8, 80),
            'duration_ms' => fake()->numberBetween(200, 4000),
            'error' => null,
            'synced_at' => now(),
        ];
    }

    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SyncLogStatus::Error,
            'error' => 'Sync failed',
        ]);
    }
}
