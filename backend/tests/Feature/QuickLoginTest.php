<?php

namespace Tests\Feature;

use App\Filament\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuickLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_uses_branding_and_hides_quick_login_in_testing(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('M2B Ledger')
            ->assertSee('Read-only balance insights from QuickBooks')
            ->assertSee('Sign in')
            ->assertDontSee('Quick login (local)');
    }

    public function test_quick_login_button_appears_only_locally(): void
    {
        $this->app['env'] = 'local';
        config(['app.quick_login' => true]);

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Quick login (local)');
    }

    public function test_quick_login_signs_in_the_demo_admin_when_enabled(): void
    {
        $this->seed();
        $this->app['env'] = 'local';
        config(['app.quick_login' => true]);

        Livewire::test(Login::class)
            ->call('quickLogin')
            ->assertRedirect();

        $this->assertAuthenticatedAs(
            User::query()->where('email', 'admin@demo.test')->first()
        );
    }

    public function test_quick_login_is_blocked_when_disabled(): void
    {
        $this->seed();
        config(['app.quick_login' => false]);

        Livewire::test(Login::class)
            ->call('quickLogin')
            ->assertStatus(404);

        $this->assertGuest();
    }
}
