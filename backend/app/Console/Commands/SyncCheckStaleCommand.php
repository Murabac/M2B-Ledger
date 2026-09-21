<?php

namespace App\Console\Commands;

use App\Enums\SyncLogStatus;
use App\Models\Company;
use App\Models\SyncLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncCheckStaleCommand extends Command
{
    protected $signature = 'sync:check-stale';

    protected $description = 'Warn when a company has no successful sync within 15 minutes';

    public function handle(): int
    {
        $threshold = now()->subMinutes(15);

        Company::query()->orderBy('id')->each(function (Company $company) use ($threshold): void {
            $lastSuccess = SyncLog::query()
                ->where('company_id', $company->id)
                ->where('status', SyncLogStatus::Success)
                ->orderByDesc('synced_at')
                ->first();

            if ($lastSuccess === null) {
                Log::warning('Company has never successfully synced.', [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                ]);
                $this->warn("Company #{$company->id} ({$company->name}) has never successfully synced.");

                return;
            }

            if ($lastSuccess->synced_at->lt($threshold)) {
                Log::warning('Company sync is stale.', [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'last_synced_at' => $lastSuccess->synced_at->toIso8601String(),
                ]);
                $this->warn("Company #{$company->id} ({$company->name}) last synced at {$lastSuccess->synced_at->toIso8601String()}.");
            }
        });

        return self::SUCCESS;
    }
}
