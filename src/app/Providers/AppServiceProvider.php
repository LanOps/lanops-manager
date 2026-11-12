<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

use Auth;
use App\Models\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        view()->composer('layouts._partials.events-navigation', function ($view) {
            $view->with('events', Event::listed()->orderByDesc('start')->whereNot('status', 'DRAFT')->where("start", ">", \Carbon\Carbon::today())->get());
        });
        view()->composer('*', function ($view) {
            $view->with('user', Auth::user());
        });

        Paginator::useBootstrap();

        // Read the keys through config, so this still works once config is cached
        $availablePaymentGateways = [];
        if (config('services.stripe.secret') != '' &&
            config('services.stripe.key') != ''
        ) {
            $availablePaymentGateways[] = 'stripe';
        }
        \Config::set('payments.available_gateways', $availablePaymentGateways);

        seo()
            ->site(config('app.name'))
            ->description(default:  config('app.seo_description'))
            ->image(default: fn () => config('app.url') . config('app.logo'))
            ->twitterSite('@LanOps');

        // Where a session that was signed out by a password change ends up
        \Illuminate\Session\Middleware\AuthenticateSession::redirectUsing(fn () => url('/login'));
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // html() escapes textarea contents, as laravelcollective/html did
        $this->app->singleton(\Spatie\Html\Html::class, \App\Libraries\Html::class);

        // Stripe's API client, with the secret key from config (read when first used)
        $this->app->bind(\Stripe\StripeClient::class, fn () => new \Stripe\StripeClient([
            'api_key'            => config('services.stripe.secret'),
            'max_network_retries' => 2,
        ]));

        // debugbar is a dev dependency, so it is not installed in the production image
        if (config('app.debug') === true && class_exists(\Barryvdh\Debugbar\ServiceProvider::class)) {
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
        }
    }
}
