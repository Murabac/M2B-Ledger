<?php

namespace App\Filament\Resources\AgentTokenResource\Pages;

use App\Filament\Concerns\ModernizesResourceForm;
use App\Filament\Resources\AgentTokenResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAgentToken extends EditRecord
{
    use ModernizesResourceForm;

    protected static string $resource = AgentTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->color('gray'),
            $this->modernDeleteAction(),
        ];
    }

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Save changes');
    }
}
