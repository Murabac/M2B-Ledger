<?php

namespace App\Http\Controllers\Api;

use App\Enums\SyncLogStatus;
use App\Http\Controllers\Controller;
use App\Models\SyncLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewStatus');

        $user = $request->user();

        $lastSuccess = SyncLog::query()
            ->where('company_id', $user->company_id)
            ->where('status', SyncLogStatus::Success)
            ->orderByDesc('synced_at')
            ->first();

        $syncedAt = $lastSuccess?->synced_at;
        $isStale = $syncedAt === null || $syncedAt->lt(now()->subMinutes(15));

        return response()->json([
            'synced_at' => $syncedAt?->utc()->toIso8601String(),
            'is_stale' => $isStale,
        ]);
    }
}
