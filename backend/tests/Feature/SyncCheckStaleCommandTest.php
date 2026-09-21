<?php

namespace Tests\Feature;

use App\Enums\SyncLogStatus;
use App\Models\Company;
use App\Models\SyncLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SyncCheckStaleCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_warns_when_sync_is_missing_or_stale(): void
    {
        Log::spy();

        $never = Company::factory()->create(['name' => 'Never Synced']);
        $stale = Company::factory()->create(['name' => 'Stale Co']);
        $fresh = Company::factory()->create(['name' => 'Fresh Co']);

        SyncLog::factory()->for($stale)->create([
            'status' => SyncLogStatus::Success,
            'synced_at' => now()->subMinutes(20),
        ]);
        SyncLog::factory()->for($fresh)->create([
            'status' => SyncLogStatus::Success,
            'synced_at' => now()->subMinutes(2),
        ]);

        $this->artisan('sync:check-stale')->assertSuccessful();

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context) use ($never) {
            return str_contains($message, 'never successfully synced')
                && ($context['company_id'] ?? null) === $never->id;
        })->once();

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context) use ($stale) {
            return str_contains($message, 'stale')
                && ($context['company_id'] ?? null) === $stale->id;
        })->once();
    }
}
