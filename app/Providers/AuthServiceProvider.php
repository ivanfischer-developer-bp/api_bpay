<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        if (! $this->app->routesAreCached()) {
            Passport::routes();
        }
        // Para cambiar la duración de los tokens descomentar las líneas a continuación
        // actualmente y por omisión los tokens duran un año
        // Expiración de tokens según ambiente
        if (!in_array(config('app.env'), ['local', 'staging'])) {
            Passport::tokensExpireIn(now()->addDay());
            Passport::refreshTokensExpireIn(now()->addDays(2));
            Passport::personalAccessTokensExpireIn(now()->addDay());
        }

        // Implicitly grant "super administrador" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super administrador') ? true : null;
        });
    }
}
