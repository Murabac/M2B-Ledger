<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AgentSyncRequest;
use App\Models\AgentToken;
use App\Services\AgentSyncService;
use Illuminate\Http\JsonResponse;

class AgentSyncController extends Controller
{
    public function __invoke(AgentSyncRequest $request, AgentSyncService $syncService): JsonResponse
    {
        /** @var AgentToken $token */
        $token = $request->attributes->get('agent_token');

        $companyId = (int) $request->validated('company_id');

        if ($token->company_id !== $companyId) {
            return response()->json([
                'message' => 'Token is not authorized for this company.',
            ], 403);
        }

        $result = $syncService->sync($token, $request->validated());

        return response()->json([
            'ok' => true,
            'accounts_count' => $result['accounts_count'],
            'customers_count' => $result['customers_count'],
            'synced_at' => $result['sync_log']->synced_at?->utc()->toIso8601String(),
            'duration_ms' => $result['sync_log']->duration_ms,
        ]);
    }
}
