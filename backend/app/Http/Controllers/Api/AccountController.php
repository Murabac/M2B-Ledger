<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AccountIndexRequest;
use App\Models\Account;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    public function index(AccountIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);

        $user = $request->user();

        $query = Account::query()
            ->where('company_id', $user->company_id)
            ->orderBy('full_name');

        if ($request->filled('type')) {
            $query->where('account_type', $request->string('type')->toString());
        }

        $accounts = $query->get()->map(fn (Account $account) => [
            'id' => $account->id,
            'qb_list_id' => $account->qb_list_id,
            'full_name' => $account->full_name,
            'account_type' => $account->account_type,
            'is_active' => $account->is_active,
            'balance' => (string) $account->balance,
            'total_balance' => (string) $account->total_balance,
        ]);

        return response()->json([
            'data' => $accounts,
        ]);
    }
}
