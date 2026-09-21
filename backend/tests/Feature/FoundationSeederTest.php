<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_demo_company_four_role_users_and_demo_data(): void
    {
        $this->seed();

        $company = Company::query()->where('name', 'Demo Company')->first();
        $this->assertNotNull($company);
        $this->assertSame(1, $company->id);

        $expected = [
            'owner@demo.test' => UserRole::Owner,
            'sales@demo.test' => UserRole::SalesRep,
            'collections@demo.test' => UserRole::Collections,
            'admin@demo.test' => UserRole::Admin,
        ];

        foreach ($expected as $email => $role) {
            $user = User::query()->where('email', $email)->first();
            $this->assertNotNull($user, "Missing seed user {$email}");
            $this->assertSame($company->id, $user->company_id);
            $this->assertSame($role, $user->role);
            $this->assertTrue(password_verify('Password123!', $user->password));
        }

        $sales = User::query()->where('email', 'sales@demo.test')->first();
        $this->assertSame('Pat Lee', $sales->qb_sales_rep_name);

        $this->assertTrue(User::query()->where('email', 'admin@demo.test')->first()->canAccessPanel(
            filament()->getDefaultPanel()
        ));
        $this->assertFalse(User::query()->where('email', 'owner@demo.test')->first()->canAccessPanel(
            filament()->getDefaultPanel()
        ));

        $types = Account::query()->where('company_id', $company->id)->pluck('account_type')->unique();
        foreach (['Bank', 'AccountsReceivable', 'Income', 'Expense'] as $type) {
            $this->assertTrue($types->contains($type), "Missing account type {$type}");
        }

        $this->assertGreaterThanOrEqual(8, Customer::query()->where('company_id', $company->id)->count());
        $this->assertSame(2, Customer::query()->where('sales_rep_name', 'Pat Lee')->count());
        $this->assertGreaterThanOrEqual(1, Customer::query()->where('sales_rep_name', 'Alex Kim')->count());
        $this->assertGreaterThanOrEqual(1, Customer::query()->whereNull('sales_rep_name')->count());
    }
}
