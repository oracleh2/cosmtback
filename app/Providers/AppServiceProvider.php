<?php

namespace App\Providers;

use App\Services\CosmeticService;
use App\Services\SkinAnalysisService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register SkinAnalysisService
        $this->app->singleton(SkinAnalysisService::class, function ($app) {
            return new SkinAnalysisService();
        });

        // Register CosmeticService
        $this->app->singleton(CosmeticService::class, function ($app) {
            return new CosmeticService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // For PostgreSQL
        Schema::defaultStringLength(191);

        // Configure PostgreSQL to use the jsonb type for JSON columns
//        DB::connection()
//            ->getSchemaGrammar()
//            ->setJsonGrammar(function ($value) {
//            return "jsonb_set($value)";
//        });

        // Enable strict mode for models
        Model::shouldBeStrict(!$this->app->isProduction());
    }
}
