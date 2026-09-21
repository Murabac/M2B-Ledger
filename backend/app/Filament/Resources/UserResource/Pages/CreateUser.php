<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Concerns\ModernizesResourceForm;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use ModernizesResourceForm;

    protected static string $resource = UserResource::class;

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Create user');
    }
}
