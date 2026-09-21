<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Support\IdentityColumn;
use App\Filament\Support\ResourceForm;
use App\Filament\Support\ResourceTable;
use App\Filament\Support\RoleBadgeColumn;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ResourceForm::section(
                    'Account',
                    'Who this person is and which company they belong to.',
                    [
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull(),
                    ]
                ),
                ResourceForm::section(
                    'Access',
                    'Role controls API visibility. Sales reps need a matching QB sales-rep name.',
                    [
                        Forms\Components\Select::make('role')
                            ->options(collect(UserRole::cases())->mapWithKeys(
                                fn (UserRole $role) => [$role->value => str_replace('_', ' ', ucwords($role->value, '_'))]
                            ))
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('qb_sales_rep_name')
                            ->label('QB sales rep name')
                            ->helperText('Must match QuickBooks SalesRepRef FullName for sales-rep scoping.')
                            ->maxLength(255)
                            ->nullable()
                            ->required(fn (Get $get): bool => $get('role') === UserRole::SalesRep->value)
                            ->visible(fn (Get $get): bool => filled($get('role'))),
                    ]
                ),
                ResourceForm::section(
                    'Security',
                    'Password is required when creating a user. Leave blank on edit to keep the current one.',
                    [
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->rule(Password::defaults())
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText(fn (string $operation): ?string => $operation === 'edit'
                                ? 'Leave blank to keep the current password.'
                                : null)
                            ->columnSpanFull(),
                    ]
                ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return ResourceTable::configure($table)
            ->columns([
                IdentityColumn::make('name', 'email', 'User'),
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                RoleBadgeColumn::make('role'),
                Tables\Columns\TextColumn::make('qb_sales_rep_name')
                    ->label('QB sales rep')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options(collect(UserRole::cases())->mapWithKeys(
                        fn (UserRole $role) => [$role->value => str_replace('_', ' ', ucwords($role->value, '_'))]
                    )),
                Tables\Filters\SelectFilter::make('company')
                    ->relationship('company', 'name'),
            ])
            ->recordUrl(fn (User $record): string => static::getUrl('edit', ['record' => $record]))
            ->actions(ResourceTable::editDeleteActions())
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('No users yet')
            ->emptyStateDescription('Create a user to grant API and admin access.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('New user'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
