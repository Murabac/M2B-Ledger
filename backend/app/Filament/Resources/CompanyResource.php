<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Support\IdentityColumn;
use App\Filament\Support\ResourceForm;
use App\Filament\Support\ResourceTable;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ResourceForm::section(
                    'Company',
                    'A QuickBooks company file mapping used by users and the sync agent.',
                    [
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ],
                    columns: 1,
                ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return ResourceTable::configure($table)
            ->columns([
                IdentityColumn::named('name', 'Company'),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->recordUrl(fn (Company $record): string => static::getUrl('edit', ['record' => $record]))
            ->actions(ResourceTable::viewEditDeleteActions())
            ->emptyStateIcon('heroicon-o-building-office-2')
            ->emptyStateHeading('No companies yet')
            ->emptyStateDescription('Add a company to start mapping users and agent tokens.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('New company'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'view' => Pages\ViewCompany::route('/{record}'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
