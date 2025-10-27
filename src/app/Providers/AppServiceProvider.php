<?php

namespace App\Providers;

use App\Helpers\Common;
use App\Helpers\LogHelperService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(LogHelperService::class, function () {
            return new LogHelperService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if (app()->environment(['staging', 'production'])) {
            URL::forceScheme('https');
        }

        DB::listen(function ($query) {
            if ($query->time >= 1000) {
                app(LogHelperService::class)->warning('Slow Query', [
                    'query' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                ]);
            }
        });

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return Common::buildResetPasswordUrl($token, $user->email);
        });

        LogViewer::auth(function ($request) {
            return $request->user()
                && in_array(trim($request->user()->email), config('log-viewer.authorized_users', []));
        });
    }
}
