<?php

namespace SimpleImageManager;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/simple-image-manager.php' => config_path('simple-image-manager.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/simple-image-manager'),
            ], 'views');

            $this->commands([
                //
            ]);
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'simple-image-manager');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/simple-image-manager.php', 'simple-image-manager');

        $this->app->bind('simple-image-manager', function ($app) {
            return new SimpleImageManager($app);
        });
    }
}
