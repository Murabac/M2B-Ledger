<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_all_company_customers(): void
    {
        $user = User::factory()->owner()->create();
        Customer::factory()->for($user->company)->create(['sales_rep_name' => 'Pat Lee']);
        Customer::factory()->for($user->company)->create(['sales_rep_name' => 'Alex Kim']);
        Sanctum::actingAs($user);

        $this->getJson('/api/customers')
            ->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_sales_rep_only_sees_own_customers(): void
    {
        $user = User::factory()->salesRep('Pat Lee')->create();
        Customer::factory()->for($user->company)->create([
            'full_name' => 'Mine',
            'sales_rep_name' => 'Pat Lee',
        ]);
        Customer::factory()->for($user->company)->create([
            'full_name' => 'Theirs',
            'sales_rep_name' => 'Alex Kim',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/customers')->assertOk();
        $this->assertSame(1, $response->json('total'));
        $this->assertSame('Mine', $response->json('data.0.full_name'));
    }

    public function test_customer_detail_404_for_other_reps_customer(): void
    {
        $user = User::factory()->salesRep('Pat Lee')->create();
        $theirs = Customer::factory()->for($user->company)->create([
            'sales_rep_name' => 'Alex Kim',
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/customers/'.$theirs->id)->assertNotFound();
    }

    public function test_sales_rep_can_view_own_customer_detail(): void
    {
        $user = User::factory()->salesRep('Pat Lee')->create();
        $mine = Customer::factory()->for($user->company)->create([
            'sales_rep_name' => 'Pat Lee',
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/customers/'.$mine->id)
            ->assertOk()
            ->assertJsonPath('data.id', $mine->id);
    }

    public function test_customers_support_search_sort_balance_filter_and_pagination(): void
    {
        $user = User::factory()->owner()->create();
        Customer::factory()->for($user->company)->create([
            'full_name' => 'Zebra Corp',
            'balance' => 10,
        ]);
        Customer::factory()->for($user->company)->create([
            'full_name' => 'Acme LLC',
            'balance' => 500,
        ]);
        Customer::factory()->for($user->company)->create([
            'full_name' => 'Zero Balance Inc',
            'balance' => 0,
        ]);
        for ($i = 0; $i < 24; $i++) {
            Customer::factory()->for($user->company)->create([
                'balance' => 1,
                'full_name' => 'Bulk Customer '.$i,
            ]);
        }
        Sanctum::actingAs($user);

        $this->getJson('/api/customers?search=Acme')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.full_name', 'Acme LLC');

        $sorted = $this->getJson('/api/customers?sort=balance_desc&only_with_balance=1')
            ->assertOk();
        $this->assertSame(25, $sorted->json('per_page'));
        $this->assertGreaterThan(25, $sorted->json('total'));
        $this->assertSame('Acme LLC', $sorted->json('data.0.full_name'));
    }
}
