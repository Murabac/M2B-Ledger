<?php

namespace Database\Factories;

use App\Models\AgentToken;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentToken>
 */
class AgentTokenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->words(2, true).' agent',
            'token_hash' => AgentToken::hashToken(bin2hex(random_bytes(48))),
            'last_used_at' => null,
            'revoked_at' => null,
        ];
    }

    public function withPlaintext(string $plaintext): static
    {
        return $this->state(fn (array $attributes) => [
            'token_hash' => AgentToken::hashToken($plaintext),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
        ]);
    }
}
