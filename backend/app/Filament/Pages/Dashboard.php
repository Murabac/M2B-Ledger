<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

class Dashboard extends BaseDashboard
{
    protected static string $view = 'filament.pages.dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getWidgets(): array
    {
        return [];
    }

    public function getHeading(): string | Htmlable
    {
        $firstName = Str::of(auth()->user()?->name ?? 'there')
            ->before(' ')
            ->toString();

        return "Welcome back, {$firstName}";
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Read-only balance insights from QuickBooks.';
    }
}
