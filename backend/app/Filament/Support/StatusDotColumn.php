<?php

namespace App\Filament\Support;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

class StatusDotColumn
{
    /**
     * Soft status pill with a colored dot — for active / inactive flags.
     */
    public static function active(
        string $attribute = 'is_active',
        ?callable $stateUsing = null,
        string $label = 'Status',
    ): TextColumn {
        return TextColumn::make($attribute)
            ->label($label)
            ->badge()
            ->getStateUsing($stateUsing)
            ->formatStateUsing(fn (mixed $state): string => $state ? 'Active' : 'Inactive')
            ->color(fn (mixed $state): string => $state ? 'success' : 'gray')
            ->icon(fn (mixed $state): string => $state ? 'heroicon-m-check-circle' : 'heroicon-m-minus-circle')
            ->extraAttributes(fn (mixed $state): array => [
                'class' => 'm2b-status-dot '.($state ? 'm2b-status-active' : 'm2b-status-inactive'),
            ]);
    }

    /**
     * Compact boolean icon column (green / muted).
     */
    public static function activeIcon(
        ?callable $stateUsing = null,
        string $label = 'Active',
    ): IconColumn {
        return IconColumn::make('active_status')
            ->label($label)
            ->boolean()
            ->getStateUsing($stateUsing)
            ->trueIcon('heroicon-m-check-circle')
            ->falseIcon('heroicon-m-x-circle')
            ->trueColor('success')
            ->falseColor('gray');
    }
}
