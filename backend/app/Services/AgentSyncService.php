<?php

namespace App\Services;

use App\Enums\SyncLogStatus;
use App\Models\Account;
use App\Models\AgentToken;
use App\Models\Customer;
use App\Models\SyncLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class AgentSyncService
{
    /**
     * @param  array{
     *     company_id: int,
     *     synced_at: string,
     *     accounts: list<array<string, mixed>>,
     *     customers: list<array<string, mixed>>
     * }  $payload
     * @return array{sync_log: SyncLog, accounts_count: int, customers_count: int}
     */
    public function sync(AgentToken $token, array $payload): array
    {
        $started = hrtime(true);

        try {
            $result = DB::transaction(function () use ($token, $payload) {
                $companyId = (int) $payload['company_id'];
                $syncedAt = Carbon::parse($payload['synced_at'])->utc();

                $accountListIds = [];
                foreach ($payload['accounts'] as $row) {
                    $accountListIds[] = $row['qb_list_id'];
                    Account::query()->updateOrCreate(
                        [
                            'company_id' => $companyId,
                            'qb_list_id' => $row['qb_list_id'],
                        ],
                        [
                            'full_name' => $row['full_name'],
                            'account_type' => $row['account_type'],
                            'is_active' => (bool) $row['is_active'],
                            'balance' => $row['balance'],
                            'total_balance' => $row['total_balance'],
                        ]
                    );
                }

                $customerListIds = [];
                foreach ($payload['customers'] as $row) {
                    $customerListIds[] = $row['qb_list_id'];
                    Customer::query()->updateOrCreate(
                        [
                            'company_id' => $companyId,
                            'qb_list_id' => $row['qb_list_id'],
                        ],
                        [
                            'full_name' => $row['full_name'],
                            'is_active' => (bool) $row['is_active'],
                            'balance' => $row['balance'],
                            'total_balance' => $row['total_balance'],
                            'sales_rep_name' => $row['sales_rep_name'] ?? null,
                        ]
                    );
                }

                Account::query()
                    ->where('company_id', $companyId)
                    ->when(
                        $accountListIds !== [],
                        fn ($q) => $q->whereNotIn('qb_list_id', $accountListIds),
                        fn ($q) => $q
                    )
                    ->update(['is_active' => false]);

                Customer::query()
                    ->where('company_id', $companyId)
                    ->when(
                        $customerListIds !== [],
                        fn ($q) => $q->whereNotIn('qb_list_id', $customerListIds),
                        fn ($q) => $q
                    )
                    ->update(['is_active' => false]);

                $token->forceFill(['last_used_at' => now()])->save();

                return [
                    'synced_at' => $syncedAt,
                    'accounts_count' => count($accountListIds),
                    'customers_count' => count($customerListIds),
                ];
            });

            $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);

            $syncLog = SyncLog::query()->create([
                'company_id' => (int) $payload['company_id'],
                'status' => SyncLogStatus::Success,
                'accounts_count' => $result['accounts_count'],
                'customers_count' => $result['customers_count'],
                'duration_ms' => $durationMs,
                'error' => null,
                'synced_at' => $result['synced_at'],
            ]);

            return [
                'sync_log' => $syncLog,
                'accounts_count' => $result['accounts_count'],
                'customers_count' => $result['customers_count'],
            ];
        } catch (Throwable $e) {
            $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);

            SyncLog::query()->create([
                'company_id' => (int) $payload['company_id'],
                'status' => SyncLogStatus::Error,
                'accounts_count' => 0,
                'customers_count' => 0,
                'duration_ms' => $durationMs,
                'error' => $e->getMessage(),
                'synced_at' => Carbon::parse($payload['synced_at'])->utc(),
            ]);

            throw $e;
        }
    }
}
