<?php

namespace App\Filament\Resources\AgentTokenResource\Pages;

use App\Filament\Resources\AgentTokenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgentTokens extends ListRecords
{
    protected static string $resource = AgentTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New agent token'),
        ];
    }
}
