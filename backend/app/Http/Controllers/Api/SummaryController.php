<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SummaryController extends Controller
{
    /** @var list<string> */
    public const KEY_ACCOUNT_TYPES = [
        'Bank',
        'AccountsReceivable',
        'AccountsPayable',
        'Income',
        'Expense',
        'OtherCurrentAsset',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewSummary');

        $user = $request->user();
        $companyId = $user->company_id;

        $customerQuery = Customer::query()->where('company_id', $companyId);

        if ($user->role === UserRole::SalesRep) {
            $customerQuery->where('sales_rep_name', $user->qb_sales_rep_name);
        }

        $totalAr = (clone $customerQuery)->sum('balance');
        $customersWithBalance = (clone $customerQuery)->where('balance', '!=', 0)->count();

        $topBalances = (clone $customerQuery)
            ->orderByDesc('balance')
            ->orderBy('full_name')
            ->limit(5)
            ->get(['id', 'full_name', 'balance', 'sales_rep_name'])
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'full_name' => $c->full_name,
                'balance' => (string) $c->balance,
                'sales_rep_name' => $c->sales_rep_name,
            ]);

        $payload = [
            'total_ar' => number_format((float) $totalAr, 2, '.', ''),
            'customers_with_balance' => $customersWithBalance,
            'top_balances' => $topBalances,
        ];

        if (Gate::allows('viewKeyAccounts')) {
            $payload['key_accounts'] = Account::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->whereIn('account_type', self::KEY_ACCOUNT_TYPES)
                ->orderBy('account_type')
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'account_type', 'balance', 'total_balance'])
                ->map(fn (Account $a) => [
                    'id' => $a->id,
                    'full_name' => $a->full_name,
                    'account_type' => $a->account_type,
                    'balance' => (string) $a->balance,
                    'total_balance' => (string) $a->total_balance,
                ]);
        }

        return response()->json($payload);
    }
}
