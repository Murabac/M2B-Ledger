<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_rep_mapping_saves_on_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $sales = User::factory()->for($admin->company)->salesRep('Old Name')->create([
            'email' => 'rep@example.test',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $sales->getRouteKey()])
            ->fillForm([
                'company_id' => $admin->company_id,
                'name' => $sales->name,
                'email' => $sales->email,
                'role' => UserRole::SalesRep->value,
                'qb_sales_rep_name' => 'Pat Lee',
                'password' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $sales->id,
            'role' => UserRole::SalesRep->value,
            'qb_sales_rep_name' => 'Pat Lee',
        ]);
    }
}
