<?php

namespace FilamentQuickForm\FormBuilder;

use Illuminate\Support\ServiceProvider;
use FilamentQuickForm\FormBuilder\Commands\InstallCommand;

class FormBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            InstallCommand::class,
        ]);
        $this->mergeConfigFrom(
            __DIR__ . '/../config/filament-quick-form.php',
            'quick_form'
        );
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'filament-quick-form-migrations');
        $this->publishes([
            __DIR__ . '/../config/filament-quick-form.php' => config_path('filament-quick-form.php'),
        ], 'filament-quick-form-config');
        $this->loadTranslationsFrom(
            __DIR__.'/../resources/lang', 
            'filament-quick-form' 
        );
        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang'),
        ], 'filament-quick-form-translations');
    }
}