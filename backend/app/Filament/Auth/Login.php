<?php

namespace App\Filament\Auth;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.login';

    public function hasLogo(): bool
    {
        return false;
    }

    public function getHeading(): string | Htmlable
    {
        return '';
    }

    public function canQuickLogin(): bool
    {
        return app()->environment('local') && (bool) config('app.quick_login');
    }

    public function quickLogin(): LoginResponse
    {
        abort_unless($this->canQuickLogin(), 404);

        $user = User::query()->where('email', 'admin@demo.test')->firstOrFail();

        Filament::auth()->login($user, remember: true);
        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->label('Sign in');
    }
}
