<?php

namespace Tests\Feature\Api;

use App\Enums\SyncLogStatus;
use App\Models\Account;
use App\Models\AgentToken;
use App\Models\Company;
use App\Models\Customer;
use App\Models\SyncLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentSyncTest extends TestCase
{
    use RefreshDatabase;

    private string $plaintext = 'test-agent-token-abcdefghijklmnopqrstuvwxyz0123456789';

    public function test_agent_sync_requires_bearer_token(): void
    {
        $this->postJson('/api/agent/sync', [])->assertUnauthorized();
    }

    public function test_agent_sync_rejects_revoked_token(): void
    {
        $company = Company::factory()->create();
        AgentToken::factory()->for($company)->withPlaintext($this->plaintext)->revoked()->create();

        $this->withToken($this->plaintext)
            ->postJson('/api/agent/sync', $this->payload($company->id))
            ->assertUnauthorized();
    }

    public function test_agent_sync_rejects_company_mismatch(): void
    {
        $company = Company::factory()->create();
        $other = Company::factory()->create();
        AgentToken::factory()->for($company)->withPlaintext($this->plaintext)->create();

        $this->withToken($this->plaintext)
            ->postJson('/api/agent/sync', $this->payload($other->id))
            ->assertForbidden();
    }

    public function test_agent_sync_upserts_marks_absent_inactive_and_writes_sync_log(): void
    {
        $company = Company::factory()->create();
        AgentToken::factory()->for($company)->withPlaintext($this->plaintext)->create();

        $staleAccount = Account::factory()->for($company)->create([
            'qb_list_id' => 'OLD-ACC',
            'is_active' => true,
        ]);
        $staleCustomer = Customer::factory()->for($company)->create([
            'qb_list_id' => 'OLD-CUST',
            'is_active' => true,
        ]);
        $existingAccount = Account::factory()->for($company)->create([
            'qb_list_id' => 'ACC-1',
            'full_name' => 'Old Checking',
            'balance' => 1,
            'total_balance' => 1,
        ]);

        $payload = $this->payload($company->id, [
            'accounts' => [[
                'qb_list_id' => 'ACC-1',
                'full_name' => 'Checking',
                'account_type' => 'Bank',
                'is_active' => true,
                'balance' => 12000.50,
                'total_balance' => 12000.50,
            ]],
            'customers' => [[
                'qb_list_id' => 'CUST-1',
                'full_name' => 'Acme LLC',
                'is_active' => true,
                'balance' => 430.00,
                'total_balance' => 430.00,
                'sales_rep_name' => 'Pat Lee',
            ]],
        ]);

        $response = $this->withToken($this->plaintext)
            ->postJson('/api/agent/sync', $payload);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('accounts_count', 1)
            ->assertJsonPath('customers_count', 1);

        $this->assertDatabaseHas('accounts', [
            'id' => $existingAccount->id,
            'full_name' => 'Checking',
            'balance' => 12000.50,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('accounts', [
            'id' => $staleAccount->id,
            'is_active' => 0,
        ]);
        $this->assertDatabaseHas('customers', [
            'qb_list_id' => 'CUST-1',
            'full_name' => 'Acme LLC',
            'sales_rep_name' => 'Pat Lee',
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $staleCustomer->id,
            'is_active' => 0,
        ]);

        $log = SyncLog::query()->first();
        $this->assertNotNull($log);
        $this->assertSame(SyncLogStatus::Success, $log->status);
        $this->assertSame(1, $log->accounts_count);
        $this->assertSame(1, $log->customers_count);
        $this->assertNotNull($log->duration_ms);

        $token = AgentToken::query()->first();
        $this->assertNotNull($token->last_used_at);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(int $companyId, array $overrides = []): array
    {
        return array_merge([
            'company_id' => $companyId,
            'synced_at' => '2026-09-21T19:00:00Z',
            'accounts' => [[
                'qb_list_id' => 'ACC-1',
                'full_name' => 'Checking',
                'account_type' => 'Bank',
                'is_active' => true,
                'balance' => 100,
                'total_balance' => 100,
            ]],
            'customers' => [[
                'qb_list_id' => 'CUST-1',
                'full_name' => 'Acme LLC',
                'is_active' => true,
                'balance' => 50,
                'total_balance' => 50,
                'sales_rep_name' => 'Pat Lee',
            ]],
        ], $overrides);
    }
}
