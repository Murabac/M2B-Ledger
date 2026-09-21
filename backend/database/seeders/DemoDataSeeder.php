<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public const DEV_PASSWORD = 'Password123!';

    public function run(): void
    {
        $company = Company::query()->create([
            'name' => 'Demo Company',
        ]);

        $users = [
            ['name' => 'Dana Owner', 'email' => 'owner@demo.test', 'role' => UserRole::Owner, 'qb_sales_rep_name' => null],
            ['name' => 'Pat Lee', 'email' => 'sales@demo.test', 'role' => UserRole::SalesRep, 'qb_sales_rep_name' => 'Pat Lee'],
            ['name' => 'Casey Collections', 'email' => 'collections@demo.test', 'role' => UserRole::Collections, 'qb_sales_rep_name' => null],
            ['name' => 'Avery Admin', 'email' => 'admin@demo.test', 'role' => UserRole::Admin, 'qb_sales_rep_name' => null],
        ];

        foreach ($users as $user) {
            User::query()->create([
                ...$user,
                'company_id' => $company->id,
                'password' => Hash::make(self::DEV_PASSWORD),
                'email_verified_at' => now(),
            ]);
        }

        $accounts = [
            ['qb_list_id' => '80000001-1111111111', 'full_name' => 'Checking', 'account_type' => 'Bank', 'balance' => '12000.50', 'total_balance' => '12000.50'],
            ['qb_list_id' => '80000002-1111111111', 'full_name' => 'Savings', 'account_type' => 'Bank', 'balance' => '45000.00', 'total_balance' => '45000.00'],
            ['qb_list_id' => '80000003-1111111111', 'full_name' => 'Accounts Receivable', 'account_type' => 'AccountsReceivable', 'balance' => '4475.25', 'total_balance' => '4475.25'],
            ['qb_list_id' => '80000004-1111111111', 'full_name' => 'Sales', 'account_type' => 'Income', 'balance' => '0.00', 'total_balance' => '0.00'],
            ['qb_list_id' => '80000005-1111111111', 'full_name' => 'Office Supplies', 'account_type' => 'Expense', 'balance' => '312.40', 'total_balance' => '312.40'],
            ['qb_list_id' => '80000006-1111111111', 'full_name' => 'Accounts Payable', 'account_type' => 'AccountsPayable', 'balance' => '890.00', 'total_balance' => '890.00'],
        ];

        foreach ($accounts as $account) {
            Account::query()->create([
                ...$account,
                'company_id' => $company->id,
                'is_active' => true,
            ]);
        }

        $customers = [
            ['qb_list_id' => '80000011-2222222222', 'full_name' => 'Acme LLC', 'balance' => '430.00', 'sales_rep_name' => 'Pat Lee'],
            ['qb_list_id' => '80000012-2222222222', 'full_name' => 'Harbor Roofing', 'balance' => '1250.50', 'sales_rep_name' => 'Pat Lee'],
            ['qb_list_id' => '80000013-2222222222', 'full_name' => 'Northside Plumbing', 'balance' => '89.00', 'sales_rep_name' => 'Alex Kim'],
            ['qb_list_id' => '80000014-2222222222', 'full_name' => 'Oak Street Bakery', 'balance' => '0.00', 'sales_rep_name' => 'Alex Kim'],
            ['qb_list_id' => '80000015-2222222222', 'full_name' => 'Pine Valley HOA', 'balance' => '2100.00', 'sales_rep_name' => null],
            ['qb_list_id' => '80000016-2222222222', 'full_name' => 'Riverside Dental', 'balance' => '15.75', 'sales_rep_name' => 'Alex Kim'],
            ['qb_list_id' => '80000017-2222222222', 'full_name' => 'Summit Electric', 'balance' => '-50.00', 'sales_rep_name' => null],
            ['qb_list_id' => '80000018-2222222222', 'full_name' => 'Westfield Hardware', 'balance' => '640.00', 'sales_rep_name' => 'Alex Kim'],
        ];

        foreach ($customers as $customer) {
            Customer::query()->create([
                ...$customer,
                'company_id' => $company->id,
                'is_active' => true,
                'total_balance' => $customer['balance'],
            ]);
        }
    }
}
