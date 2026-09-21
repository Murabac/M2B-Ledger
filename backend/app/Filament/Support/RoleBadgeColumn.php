<?php

namespace App\Filament\Support;

use App\Enums\UserRole;
use Filament\Tables\Columns\TextColumn;

class RoleBadgeColumn
{
    public static function make(string $attribute = 'role'): TextColumn
    {
        return TextColumn::make($attribute)
            ->badge()
            ->formatStateUsing(fn (UserRole|string $state): string => str_replace(
                '_',
                ' ',
                ucwords($state instanceof UserRole ? $state->value : $state, '_')
            ))
            ->color(fn (UserRole|string $state): string => match ($state instanceof UserRole ? $state : UserRole::tryFrom((string) $state)) {
                UserRole::Admin => 'admin',
                UserRole::Owner => 'owner',
                UserRole::Collections => 'collections',
                UserRole::SalesRep => 'sales_rep',
                default => 'gray',
            })
            ->extraAttributes(fn (UserRole|string $state): array => [
                'class' => 'm2b-role-badge m2b-role-'.($state instanceof UserRole ? $state->value : (string) $state),
            ])
            ->sortable();
    }
}
