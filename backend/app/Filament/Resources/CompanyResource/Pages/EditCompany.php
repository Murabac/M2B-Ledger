<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Concerns\ModernizesResourceForm;
use App\Filament\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    use ModernizesResourceForm;

    protected static string $resource = CompanyResource::class;

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
