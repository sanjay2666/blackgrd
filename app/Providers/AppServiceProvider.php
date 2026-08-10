<?php

namespace App\Providers;

use App\Services\CurrentOrganizationContext;
use App\Services\FinancialYearResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
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
