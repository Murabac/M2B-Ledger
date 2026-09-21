<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_empty_state_and_welcome(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@demo.test')->first();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Welcome back, Avery')
            ->assertSee('Your dashboard is ready')
            ->assertSee('Balances and sync status will appear here once the QuickBooks sync agent is connected.')
            ->assertDontSee('Welcome to the Filament');
    }
}
