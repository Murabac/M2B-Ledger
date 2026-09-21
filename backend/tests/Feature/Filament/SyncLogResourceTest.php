<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SyncLogResource;
use App\Filament\Resources\SyncLogResource\Pages\ListSyncLogs;
use App\Filament\Resources\SyncLogResource\Pages\ViewSyncLog;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SyncLogResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_log_resource_is_read_only(): void
    {
        $admin = User::factory()->admin()->create();
        $log = SyncLog::factory()->for($admin->company)->create();

        $this->actingAs($admin);

        $this->assertFalse(SyncLogResource::canCreate());
        $this->assertFalse(SyncLogResource::canEdit($log));
        $this->assertFalse(SyncLogResource::canDelete($log));
        $this->assertFalse(SyncLogResource::canDeleteAny());

        Livewire::test(ListSyncLogs::class)
            ->assertOk()
            ->assertActionDoesNotExist('create');

        Livewire::test(ViewSyncLog::class, ['record' => $log->getRouteKey()])
            ->assertOk()
            ->assertActionDoesNotExist('edit')
            ->assertActionDoesNotExist('delete');

        $this->get('/admin/sync-logs/create')->assertNotFound();
    }
}
