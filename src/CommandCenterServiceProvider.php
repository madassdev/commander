<?php

namespace Madassdev\Commander;

use Illuminate\Support\ServiceProvider;

class CommandCenterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/command-center.php', 'command-center');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/command-center.php' => config_path('command-center.php'),
        ], 'command-center-config');

        $this->publishes([
            __DIR__ . '/../resources/js/Layouts/CommandLayout.vue' => resource_path('js/Layouts/CommandLayout.vue'),
            __DIR__ . '/../resources/js/Components/command' => resource_path('js/Components/command'),
            __DIR__ . '/../resources/js/Pages/Command' => resource_path('js/Pages/Command'),
        ], 'command-center-inertia');

        $this->loadRoutes();
    }

    protected function loadRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/command.php');
    }
}
