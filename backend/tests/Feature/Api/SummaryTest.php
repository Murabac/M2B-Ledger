<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_includes_totals_and_top_balances_for_all_roles(): void
    {
        $user = User::factory()->collections()->create();
        Customer::factory()->for($user->company)->create(['full_name' => 'A', 'balance' => 100]);
        Customer::factory()->for($user->company)->create(['full_name' => 'B', 'balance' => 300]);
        Customer::factory()->for($user->company)->create(['full_name' => 'C', 'balance' => 0]);
        Account::factory()->for($user->company)->create([
            'account_type' => 'Bank',
            'is_active' => true,
            'balance' => 50,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/summary')->assertOk();

        $this->assertSame('400.00', $response->json('total_ar'));
        $this->assertSame(2, $response->json('customers_with_balance'));
        $this->assertSame('B', $response->json('top_balances.0.full_name'));
        $this->assertArrayNotHasKey('key_accounts', $response->json());
    }

    public function test_owner_and_admin_receive_key_accounts(): void
    {
        foreach (['owner', 'admin'] as $state) {
            $user = User::factory()->{$state}()->create();
            Account::factory()->for($user->company)->create([
                'account_type' => 'Bank',
                'is_active' => true,
                'full_name' => 'Checking',
            ]);
            Account::factory()->for($user->company)->create([
                'account_type' => 'Equity',
                'is_active' => true,
                'full_name' => 'Opening Bal',
            ]);
            Sanctum::actingAs($user);

            $response = $this->getJson('/api/summary')->assertOk();
            $this->assertArrayHasKey('key_accounts', $response->json());
            $this->assertCount(1, $response->json('key_accounts'));
            $this->assertSame('Checking', $response->json('key_accounts.0.full_name'));
        }
    }

    public function test_sales_rep_summary_is_scoped_and_hides_key_accounts(): void
    {
        $user = User::factory()->salesRep('Pat Lee')->create();
        Customer::factory()->for($user->company)->create([
            'sales_rep_name' => 'Pat Lee',
            'balance' => 200,
        ]);
        Customer::factory()->for($user->company)->create([
            'sales_rep_name' => 'Alex Kim',
            'balance' => 900,
        ]);
        Account::factory()->for($user->company)->create([
            'account_type' => 'Bank',
            'is_active' => true,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/summary')->assertOk();

        $this->assertSame('200.00', $response->json('total_ar'));
        $this->assertSame(1, $response->json('customers_with_balance'));
        $this->assertArrayNotHasKey('key_accounts', $response->json());
    }
}
