<x-filament-panels::page.simple>
    <div class="m2b-login-brand">
        <img
            src="{{ asset('images/m2b-mark.svg') }}"
            alt=""
            class="m2b-login-mark"
        />

        <h1 class="m2b-login-name">
            {{ config('app.name') }}
        </h1>

        <p class="m2b-login-tagline">
            Read-only balance insights from QuickBooks
        </p>
    </div>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form id="form" wire:submit="authenticate" class="m2b-login-form">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    @if ($this->canQuickLogin())
        <button
            type="button"
            wire:click="quickLogin"
            class="m2b-quick-login"
        >
            <x-filament::icon icon="heroicon-o-bolt" class="m2b-quick-login-icon" />
            Quick login (local)
        </button>
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
</x-filament-panels::page.simple>
