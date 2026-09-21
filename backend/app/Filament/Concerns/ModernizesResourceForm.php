<?php

namespace App\Filament\Concerns;

use Filament\Actions;

/**
 * Sticky blurred action bar + secondary delete styling for resource create/edit pages.
 * Livewire boots `bootModernizesResourceForm` automatically when this trait is used.
 */
trait ModernizesResourceForm
{
    public function bootModernizesResourceForm(): void
    {
        static::stickyFormActions();
        static::alignFormActionsStart();
    }

    protected function getCancelFormAction(): Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Cancel')
            ->color('gray');
    }

    /**
     * Secondary danger control — not the primary header CTA.
     */
    protected function modernDeleteAction(): Actions\DeleteAction
    {
        return Actions\DeleteAction::make()
            ->label('Delete')
            ->color('danger')
            ->outlined()
            ->icon('heroicon-m-trash');
    }
}
