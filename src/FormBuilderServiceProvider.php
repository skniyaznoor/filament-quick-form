<?php

namespace Quickform\Formbuilder;

use Illuminate\Support\ServiceProvider;
use Quickform\Formbuilder\Commands\InstallCommand;

class FormBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            InstallCommand::class,
        ]);
        $this->mergeConfigFrom(
            __DIR__ . '/../config/quickform.php',
            'quick_form'
        );
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'formbuilder-migrations');
        $this->publishes([
            __DIR__ . '/../config/quickform.php' => config_path('quickform.php'),
        ], 'config');
        $this->loadTranslationsFrom(
            __DIR__.'/../resources/lang', 
            'quickform' 
        );
        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang'),
        ], 'quickform-translations');
    }
}