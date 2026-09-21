<?php

namespace App\Filament\Resources;

use App\Enums\SyncLogStatus;
use App\Filament\Resources\SyncLogResource\Pages;
use App\Filament\Support\ResourceTable;
use App\Models\SyncLog;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SyncLogResource extends Resource
{
    protected static ?string $model = SyncLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Admin';

    protected static ?string $navigationLabel = 'Sync logs';

    protected static ?string $modelLabel = 'sync log';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Sync run')
                    ->description('Read-only snapshot of one agent push.')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Infolists\Components\TextEntry::make('company.name')
                            ->label('Company'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (SyncLogStatus|string $state): string => match ($state instanceof SyncLogStatus ? $state : SyncLogStatus::tryFrom((string) $state)) {
                                SyncLogStatus::Success => 'success',
                                SyncLogStatus::Error => 'danger',
                                default => 'gray',
                            })
                            ->extraAttributes(fn (SyncLogStatus|string $state): array => [
                                'class' => 'm2b-status-dot '.(($state instanceof SyncLogStatus ? $state : SyncLogStatus::tryFrom((string) $state)) === SyncLogStatus::Success
                                    ? 'm2b-status-active'
                                    : 'm2b-status-inactive'),
                            ]),
                        Infolists\Components\TextEntry::make('synced_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('accounts_count'),
                        Infolists\Components\TextEntry::make('customers_count'),
                        Infolists\Components\TextEntry::make('duration_ms')
                            ->label('Duration (ms)'),
                        Infolists\Components\TextEntry::make('error')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return ResourceTable::configure($table)
            ->columns([
                Tables\Columns\TextColumn::make('synced_at')
                    ->dateTime()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('company.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (SyncLogStatus|string $state): string => match ($state instanceof SyncLogStatus ? $state : SyncLogStatus::tryFrom((string) $state)) {
                        SyncLogStatus::Success => 'success',
                        SyncLogStatus::Error => 'danger',
                        default => 'gray',
                    })
                    ->extraAttributes(fn (SyncLogStatus|string $state): array => [
                        'class' => 'm2b-status-dot '.(($state instanceof SyncLogStatus ? $state : SyncLogStatus::tryFrom((string) $state)) === SyncLogStatus::Success
                            ? 'm2b-status-active'
                            : 'm2b-status-inactive'),
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('accounts_count')
                    ->label('Accounts')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('customers_count')
                    ->label('Customers')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('ms')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('error')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('synced_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        SyncLogStatus::Success->value => 'Success',
                        SyncLogStatus::Error->value => 'Error',
                    ]),
                Tables\Filters\SelectFilter::make('company')
                    ->relationship('company', 'name'),
            ])
            ->recordUrl(fn (SyncLog $record): string => static::getUrl('view', ['record' => $record]))
            ->actions(ResourceTable::viewOnlyActions())
            ->emptyStateIcon('heroicon-o-arrow-path')
            ->emptyStateHeading('No sync logs yet')
            ->emptyStateDescription('Logs appear here after the agent posts a successful or failed sync.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSyncLogs::route('/'),
            'view' => Pages\ViewSyncLog::route('/{record}'),
        ];
    }
}
