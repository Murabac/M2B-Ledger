<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_admin_collections_can_list_accounts(): void
    {
        foreach (['owner', 'admin', 'collections'] as $state) {
            $user = User::factory()->{$state}()->create();
            Account::factory()->for($user->company)->count(2)->create();
            Sanctum::actingAs($user);

            $this->getJson('/api/accounts')
                ->assertOk()
                ->assertJsonCount(2, 'data');
        }
    }

    public function test_sales_rep_cannot_list_accounts(): void
    {
        $user = User::factory()->salesRep()->create();
        Account::factory()->for($user->company)->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/accounts')->assertForbidden();
    }

    public function test_accounts_can_be_filtered_by_type(): void
    {
        $user = User::factory()->owner()->create();
        Account::factory()->for($user->company)->create(['account_type' => 'Bank', 'full_name' => 'Checking']);
        Account::factory()->for($user->company)->create(['account_type' => 'Income', 'full_name' => 'Sales']);
        Sanctum::actingAs($user);

        $this->getJson('/api/accounts?type=Bank')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.full_name', 'Checking')
            ->assertJsonStructure(['data' => [['balance', 'total_balance']]]);
    }
}
