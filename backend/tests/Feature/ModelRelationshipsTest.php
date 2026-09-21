<?php

namespace Tests\Feature;

use App\Enums\SyncLogStatus;
use App\Enums\UserRole;
use App\Models\Account;
use App\Models\AgentToken;
use App\Models\Company;
use App\Models\Customer;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_owns_related_records(): void
    {
        $company = Company::factory()->create();

        $user = User::factory()->for($company)->create(['role' => UserRole::Owner]);
        $account = Account::factory()->for($company)->create();
        $customer = Customer::factory()->for($company)->create();
        $syncLog = SyncLog::factory()->for($company)->create(['status' => SyncLogStatus::Success]);
        $token = AgentToken::factory()->for($company)->create();

        $company->refresh();

        $this->assertTrue($company->users->contains($user));
        $this->assertTrue($company->accounts->contains($account));
        $this->assertTrue($company->customers->contains($customer));
        $this->assertTrue($company->syncLogs->contains($syncLog));
        $this->assertTrue($company->agentTokens->contains($token));
        $this->assertTrue($user->company->is($company));
        $this->assertTrue($account->company->is($company));
        $this->assertTrue($customer->company->is($company));
    }

    public function test_accounts_are_unique_per_company_and_qb_list_id(): void
    {
        $company = Company::factory()->create();
        Account::factory()->for($company)->create(['qb_list_id' => 'LIST-1']);

        $this->expectException(QueryException::class);
        Account::factory()->for($company)->create(['qb_list_id' => 'LIST-1']);
    }

    public function test_customers_are_unique_per_company_and_qb_list_id(): void
    {
        $company = Company::factory()->create();
        Customer::factory()->for($company)->create(['qb_list_id' => 'CUST-1']);

        $this->expectException(QueryException::class);
        Customer::factory()->for($company)->create(['qb_list_id' => 'CUST-1']);
    }

    public function test_agent_token_is_stored_as_sha256_hash(): void
    {
        $plain = 'plain-agent-token';
        $token = AgentToken::factory()->create([
            'token_hash' => AgentToken::hashToken($plain),
        ]);

        $this->assertSame(hash('sha256', $plain), $token->token_hash);
        $this->assertSame(64, strlen($token->token_hash));
        $this->assertFalse($token->isRevoked());
    }
}
