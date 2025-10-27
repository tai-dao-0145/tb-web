<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ServiceRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->bindings($this->registerServices());
        $this->bindings($this->registerRepositories());
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Loop all register to binding
     *
     * @param array $classes : class name
     *
     * @return void
     */
    private function bindings(array $classes): void
    {
        foreach ($classes as $interface => $implement) {
            $this->app->bind($interface, $implement);
        }
    }

    /**
     * Register services for binding
     *
     * @return string[]
     */
    private function registerServices(): array
    {
        return [];
    }

    /**
     * Register repositories for binding
     *
     * @return string[]
     */
    private function registerRepositories(): array
    {
        return [];
    }
}
