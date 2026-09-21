<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentTokenResource\Pages;
use App\Filament\Support\IdentityColumn;
use App\Filament\Support\ResourceForm;
use App\Filament\Support\ResourceTable;
use App\Filament\Support\StatusDotColumn;
use App\Models\AgentToken;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AgentTokenResource extends Resource
{
    protected static ?string $model = AgentToken::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Admin';

    protected static ?string $navigationLabel = 'Agent tokens';

    protected static ?string $modelLabel = 'agent token';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ResourceForm::section(
                    'Token',
                    'A labeled credential for one agent install. The plaintext value is shown only once on create.',
                    [
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('A label for this agent install (e.g. “Rock Castle server”).'),
                    ]
                ),
                ResourceForm::section(
                    'Revocation',
                    'Revoke to block further syncs without deleting history.',
                    [
                        Forms\Components\DateTimePicker::make('revoked_at')
                            ->label('Revoked at')
                            ->visibleOn('edit')
                            ->helperText('Set to revoke immediately. Leave empty while active.')
                            ->columnSpanFull(),
                    ],
                    columns: 1,
                )->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return ResourceTable::configure($table)
            ->columns([
                IdentityColumn::named('name', 'Token'),
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                StatusDotColumn::active(
                    attribute: 'revoked_at',
                    stateUsing: fn (AgentToken $record): bool => ! $record->isRevoked(),
                    label: 'Status',
                ),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (AgentToken $record): string => static::getUrl('view', ['record' => $record]))
            ->actions(ResourceTable::viewEditDeleteActions([
                Tables\Actions\Action::make('revoke')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (AgentToken $record): bool => ! $record->isRevoked())
                    ->action(fn (AgentToken $record) => $record->forceFill(['revoked_at' => now()])->save()),
            ]))
            ->emptyStateIcon('heroicon-o-key')
            ->emptyStateHeading('No agent tokens yet')
            ->emptyStateDescription('Create a token, copy it once, and paste it into the agent config.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('New agent token'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgentTokens::route('/'),
            'create' => Pages\CreateAgentToken::route('/create'),
            'view' => Pages\ViewAgentToken::route('/{record}'),
            'edit' => Pages\EditAgentToken::route('/{record}/edit'),
        ];
    }
}
