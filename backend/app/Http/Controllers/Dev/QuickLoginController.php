<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuickLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(app()->environment('local') && (bool) config('app.quick_login'), 404);

        $user = User::query()->where('email', 'admin@demo.test')->firstOrFail();

        Filament::auth()->login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(Filament::getUrl());
    }
}
