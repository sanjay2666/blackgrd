<?php

namespace App\Providers;

use App\Services\AuthorizationService;
use App\Services\CurrentOrganizationContext;
use App\Services\FinancialYearResolver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentOrganizationContext::class, fn () => new CurrentOrganizationContext());
        $this->app->scoped(AuthorizationService::class, fn () => new AuthorizationService());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(fn ($user, string $ability) => app(AuthorizationService::class)->can($ability) ? true : null);
        RateLimiter::for('auth-login', function (Request $request): array {
            $email = Str::lower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(20)->by('ip:'.$request->ip()),
                Limit::perMinute(5)->by('account:'.$request->ip().'|'.$email),
            ];
        });

        RateLimiter::for('password-reset', function (Request $request): array {
            $email = Str::lower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(10)->by('ip:'.$request->ip()),
                Limit::perMinute(3)->by('account:'.$request->ip().'|'.$email),
            ];
        });

        Model::creating(function (Model $model): void {
            if (! Schema::hasColumn($model->getTable(), 'financial_year_id')) {
                return;
            }

            $context = request()->attributes->get(CurrentOrganizationContext::class);
            if (! $context instanceof CurrentOrganizationContext) {
                return;
            }

            $financialYear = app(FinancialYearResolver::class)->current();
            $model->setAttribute('financial_year_id', $financialYear->id);
            if (array_key_exists('financial_year', $model->getAttributes())) {
                $model->setAttribute('financial_year', $financialYear->code);
            }
        });
    }
}
