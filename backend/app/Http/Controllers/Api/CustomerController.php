<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomerIndexRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(CustomerIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $user = $request->user();

        $query = Customer::query()->where('company_id', $user->company_id);

        if ($user->role === UserRole::SalesRep) {
            $query->where('sales_rep_name', $user->qb_sales_rep_name);
        }

        if ($request->filled('search')) {
            $term = $request->string('search')->toString();
            $query->where('full_name', 'like', '%'.$term.'%');
        }

        if ($request->boolean('only_with_balance')) {
            $query->where('balance', '!=', 0);
        }

        if ($request->string('sort')->toString() === 'balance_desc') {
            $query->orderByDesc('balance')->orderBy('full_name');
        } else {
            $query->orderBy('full_name');
        }

        $paginator = $query->paginate(25);

        $paginator->getCollection()->transform(fn (Customer $customer) => [
            'id' => $customer->id,
            'qb_list_id' => $customer->qb_list_id,
            'full_name' => $customer->full_name,
            'is_active' => $customer->is_active,
            'balance' => (string) $customer->balance,
            'total_balance' => (string) $customer->total_balance,
            'sales_rep_name' => $customer->sales_rep_name,
        ]);

        return response()->json($paginator);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $customer = Customer::query()
            ->where('company_id', $request->user()->company_id)
            ->whereKey($id)
            ->first();

        if ($customer === null || ! $request->user()->can('view', $customer)) {
            abort(404);
        }

        return response()->json([
            'data' => [
                'id' => $customer->id,
                'qb_list_id' => $customer->qb_list_id,
                'full_name' => $customer->full_name,
                'is_active' => $customer->is_active,
                'balance' => (string) $customer->balance,
                'total_balance' => (string) $customer->total_balance,
                'sales_rep_name' => $customer->sales_rep_name,
            ],
        ]);
    }
}
