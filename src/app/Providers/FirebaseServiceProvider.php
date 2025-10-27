<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('firebase.auth', function ($app) {
            return (new Factory)
                ->withServiceAccount(
                    base_path(config('firebase.projects.app.credentials'))
                )
                ->createAuth();
        });

        $this->app->singleton('firebase.storage', function ($app) {
            return (new Factory)
                ->withDefaultStorageBucket(
                    config('firebase.projects.app.storage.default_bucket')
                )
                ->withServiceAccount(
                    base_path(config('firebase.projects.app.credentials'))
                )
                ->createStorage();
        });

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
