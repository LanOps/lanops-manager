<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;

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
            $view->with('events', Event::orderByDesc('start')->whereNot('status', 'DRAFT')->where("start", ">", \Carbon\Carbon::today())->get());
        });
        view()->composer('*', function ($view) {
            $view->with('user', Auth::user());
        });

        Paginator::useBootstrap();

        // Build links (password resets, email verification) from APP_URL, never
        // from the request's Host or X-Forwarded-Host headers
        if ($this->app->environment('production') && config('app.url')) {
            URL::forceRootUrl(config('app.url'));
            if (str_starts_with(config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }

        // Read the keys through config, so this still works once config is cached
        $availablePaymentGateways = [];
        if (config('laravel-omnipay.gateways.stripe.credentials.secret') != '' &&
            config('laravel-omnipay.gateways.stripe.credentials.public') != ''
        ) {
            $availablePaymentGateways[] = 'stripe';
        }
        if (config('laravel-omnipay.gateways.paypal_express.credentials.username') != '' &&
            config('laravel-omnipay.gateways.paypal_express.credentials.password') != '' &&
            config('laravel-omnipay.gateways.paypal_express.credentials.signature') != ''
        ) {
            $availablePaymentGateways[] = 'paypal_express';
        }
        \Config::set('laravel-omnipay.gateways.available_payment_gateways', $availablePaymentGateways);

        seo()
            ->site(config('app.name'))
            ->description(default:  config('app.seo_description'))
            ->image(default: fn () => config('app.url') . config('app.logo'))
            ->twitterSite('@LanOps');
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

        // debugbar is a dev dependency, so it is not installed in the production image
        if (config('app.debug') === true && class_exists(\Barryvdh\Debugbar\ServiceProvider::class)) {
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
        }
    }
}
