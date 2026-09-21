<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\Customer;
use App\Policies\AccountPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\StatusPolicy;
use App\Policies\SummaryPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Account::class, AccountPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);

        Gate::define('viewStatus', [StatusPolicy::class, 'view']);
        Gate::define('viewSummary', [SummaryPolicy::class, 'view']);
        Gate::define('viewKeyAccounts', [SummaryPolicy::class, 'viewKeyAccounts']);

        RateLimiter::for('api-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api-agent-sync', function (Request $request) {
            $token = $request->attributes->get('agent_token');
            $key = $token !== null
                ? 'agent-token:'.$token->id
                : 'agent-ip:'.$request->ip();

            return Limit::perMinute(20)->by($key);
        });

        RateLimiter::for('api-reads', function (Request $request) {
            return Limit::perMinute(60)->by((string) ($request->user()?->id ?? $request->ip()));
        });
    }
}
