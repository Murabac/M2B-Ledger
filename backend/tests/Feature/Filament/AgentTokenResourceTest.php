<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AgentTokenResource\Pages\CreateAgentToken;
use App\Filament\Resources\AgentTokenResource\Pages\ListAgentTokens;
use App\Filament\Resources\AgentTokenResource\Pages\ViewAgentToken;
use App\Models\AgentToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AgentTokenResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_shows_plaintext_once_and_stores_only_hash(): void
    {
        $admin = User::factory()->admin()->create();
        $company = $admin->company;

        $this->actingAs($admin);

        Livewire::test(CreateAgentToken::class)
            ->fillForm([
                'company_id' => $company->id,
                'name' => 'Rock Castle agent',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $token = AgentToken::query()->first();
        $this->assertNotNull($token);
        $this->assertSame('Rock Castle agent', $token->name);
        $this->assertSame(64, strlen($token->token_hash));

        $plaintext = session(CreateAgentToken::SESSION_PLAINTEXT_KEY);
        $this->assertIsString($plaintext);
        $this->assertSame(96, strlen($plaintext));
        $this->assertSame(AgentToken::hashToken($plaintext), $token->token_hash);
        $this->assertDatabaseMissing('agent_tokens', ['token_hash' => $plaintext]);

        Livewire::test(ViewAgentToken::class, ['record' => $token->getRouteKey()])
            ->assertOk()
            ->assertSee($plaintext)
            ->assertDontSee($token->token_hash);

        Livewire::test(ViewAgentToken::class, ['record' => $token->getRouteKey()])
            ->assertOk()
            ->assertDontSee($plaintext);

        Livewire::test(ListAgentTokens::class)
            ->assertOk()
            ->assertSee('Rock Castle agent')
            ->assertDontSee($plaintext)
            ->assertDontSee($token->token_hash);
    }
}
