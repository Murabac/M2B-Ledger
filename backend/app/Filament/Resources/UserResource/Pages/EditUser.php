<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Concerns\ModernizesResourceForm;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    use ModernizesResourceForm;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->modernDeleteAction(),
        ];
    }

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Save changes');
    }
}
