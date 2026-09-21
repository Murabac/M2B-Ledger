<?php

namespace App\Filament\Support;

use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class ResourceTable
{
    /**
     * Shared list-table chrome: filters button, no bulk chrome unless actions are added later.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->striped(false)
            ->filtersLayout(FiltersLayout::Dropdown)
            ->filtersFormWidth('sm')
            ->filtersTriggerAction(
                fn (Action $action): Action => $action
                    ->button()
                    ->label('Filters')
                    ->icon('heroicon-m-funnel')
                    ->color('gray')
                    ->size('sm')
            )
            ->persistFiltersInSession(false)
            ->deferFilters(false)
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->extremePaginationLinks()
            ->bulkActions([]);
    }

    /**
     * @param  array<Action>  $actions
     */
    public static function rowActions(array $actions): ActionGroup
    {
        return ActionGroup::make($actions)
            ->label('Actions')
            ->icon('heroicon-m-ellipsis-vertical')
            ->color('gray')
            ->button()
            ->size('sm')
            ->tooltip('Actions');
    }

    /**
     * @param  array<Action>  $extra
     * @return array{0: ActionGroup}
     */
    public static function editDeleteActions(array $extra = []): array
    {
        return [
            self::rowActions([
                ...$extra,
                EditAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }

    /**
     * @param  array<Action>  $extra
     * @return array{0: ActionGroup}
     */
    public static function viewEditDeleteActions(array $extra = []): array
    {
        return [
            self::rowActions([
                ViewAction::make(),
                ...$extra,
                EditAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }

    /**
     * @param  array<Action>  $extra
     * @return array{0: ActionGroup}
     */
    public static function viewOnlyActions(array $extra = []): array
    {
        return [
            self::rowActions([
                ViewAction::make(),
                ...$extra,
            ]),
        ];
    }
}
