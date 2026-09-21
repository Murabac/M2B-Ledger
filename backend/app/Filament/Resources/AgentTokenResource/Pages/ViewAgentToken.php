<?php

namespace App\Filament\Resources\AgentTokenResource\Pages;

use App\Filament\Resources\AgentTokenResource;
use App\Models\AgentToken;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewAgentToken extends ViewRecord
{
    protected static string $resource = AgentTokenResource::class;

    public ?string $revealedPlaintext = null;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $plaintext = session()->pull(CreateAgentToken::SESSION_PLAINTEXT_KEY);

        if (is_string($plaintext) && $plaintext !== '') {
            $this->revealedPlaintext = $plaintext;

            Notification::make()
                ->title('Copy this token now')
                ->body('This is the only time the plaintext agent token is shown. Store it in the agent appsettings.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Plaintext token (shown once)')
                    ->description('Copy this value into the agent AgentToken setting. It is never stored and will disappear after you leave this page.')
                    ->visible(fn (): bool => filled($this->revealedPlaintext))
                    ->schema([
                        Infolists\Components\TextEntry::make('revealedPlaintext')
                            ->label('Bearer token')
                            ->state(fn (): ?string => $this->revealedPlaintext)
                            ->copyable()
                            ->columnSpanFull(),
                    ]),
                Infolists\Components\Section::make('Token')
                    ->schema([
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('company.name')
                            ->label('Company'),
                        Infolists\Components\IconEntry::make('active')
                            ->label('Active')
                            ->boolean()
                            ->getStateUsing(fn (AgentToken $record): bool => ! $record->isRevoked()),
                        Infolists\Components\TextEntry::make('last_used_at')
                            ->dateTime()
                            ->placeholder('Never'),
                        Infolists\Components\TextEntry::make('revoked_at')
                            ->dateTime()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('revoke')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => ! $this->getRecord()->isRevoked())
                ->action(function (): void {
                    $this->getRecord()->forceFill(['revoked_at' => now()])->save();
                    Notification::make()->title('Token revoked')->success()->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
