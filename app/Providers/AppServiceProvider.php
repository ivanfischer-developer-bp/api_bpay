<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\EmailService;
// use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('email.service', function ($app) {
            return new EmailService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Schema::defaultStringLength(125);
        // setlocale(LC_TIME, 'es_AR', 'es', 'ES', 'es_AR.utf8');
    }
}
