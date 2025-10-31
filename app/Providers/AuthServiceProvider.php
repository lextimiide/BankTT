<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;
use App\Models\Admin;
use App\Models\Client;
use App\Auth\MultiTableUserProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Configuration pour supporter Admin et Client
        Passport::useClientModel(\Laravel\Passport\Client::class);
        Passport::useTokenModel(\Laravel\Passport\Token::class);
        Passport::useAuthCodeModel(\Laravel\Passport\AuthCode::class);
        Passport::usePersonalAccessClientModel(\Laravel\Passport\PersonalAccessClient::class);

        // Enregistrer le provider multi-table
        \Auth::provider('multi_table', function ($app, array $config) {
            return new MultiTableUserProvider();
        });

        // Durée des tokens
        Passport::tokensExpireIn(now()->addMinutes(60)); // 1 heure
        Passport::refreshTokensExpireIn(now()->addDays(7)); // 7 jours
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        // Configuration des guards pour Admin et Client
        Passport::enableImplicitGrant();
        Passport::tokensCan([
            'admin' => 'Admin Access',
            'client' => 'Client Access',
        ]);
    }
}
