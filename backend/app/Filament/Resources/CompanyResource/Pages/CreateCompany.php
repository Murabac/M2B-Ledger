<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Concerns\ModernizesResourceForm;
use App\Filament\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCompany extends CreateRecord
{
    use ModernizesResourceForm;

    protected static string $resource = CompanyResource::class;

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Create company');
    }
}
