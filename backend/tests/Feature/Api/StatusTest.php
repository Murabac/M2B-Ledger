<?php

namespace Tests\Feature\Api;

use App\Enums\SyncLogStatus;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_is_stale_when_no_successful_sync(): void
    {
        $user = User::factory()->owner()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/status')
            ->assertOk()
            ->assertJsonPath('synced_at', null)
            ->assertJsonPath('is_stale', true);
    }

    public function test_status_is_fresh_within_fifteen_minutes(): void
    {
        $user = User::factory()->owner()->create();
        SyncLog::factory()->for($user->company)->create([
            'status' => SyncLogStatus::Success,
            'synced_at' => now()->subMinutes(5),
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/status')->assertOk();

        $this->assertFalse($response->json('is_stale'));
        $this->assertNotNull($response->json('synced_at'));
    }

    public function test_status_is_stale_after_fifteen_minutes(): void
    {
        $user = User::factory()->owner()->create();
        SyncLog::factory()->for($user->company)->create([
            'status' => SyncLogStatus::Success,
            'synced_at' => now()->subMinutes(16),
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/status')
            ->assertOk()
            ->assertJsonPath('is_stale', true);
    }

    public function test_status_ignores_error_sync_logs(): void
    {
        $user = User::factory()->owner()->create();
        SyncLog::factory()->for($user->company)->error()->create([
            'synced_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/status')
            ->assertOk()
            ->assertJsonPath('synced_at', null)
            ->assertJsonPath('is_stale', true);
    }

    public function test_all_roles_can_view_status(): void
    {
        foreach (['owner', 'admin', 'collections', 'salesRep'] as $factoryState) {
            $user = User::factory()->{$factoryState}()->create();
            Sanctum::actingAs($user);
            $this->getJson('/api/status')->assertOk();
        }
    }
}
