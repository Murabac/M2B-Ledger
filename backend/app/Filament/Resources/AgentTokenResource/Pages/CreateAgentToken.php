<?php

namespace App\Filament\Resources\AgentTokenResource\Pages;

use App\Filament\Concerns\ModernizesResourceForm;
use App\Filament\Resources\AgentTokenResource;
use App\Models\AgentToken;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAgentToken extends CreateRecord
{
    use ModernizesResourceForm;

    protected static string $resource = AgentTokenResource::class;

    public const SESSION_PLAINTEXT_KEY = 'filament.agent_token_plaintext';

    protected function handleRecordCreation(array $data): Model
    {
        $plaintext = AgentToken::generatePlaintext();

        $data['token_hash'] = AgentToken::hashToken($plaintext);
        $data['last_used_at'] = null;
        $data['revoked_at'] = null;

        /** @var AgentToken $record */
        $record = static::getModel()::query()->create($data);

        session()->flash(self::SESSION_PLAINTEXT_KEY, $plaintext);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Agent token created — copy it now. It will not be shown again.';
    }

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Create token');
    }
}
