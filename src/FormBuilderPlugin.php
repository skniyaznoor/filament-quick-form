<?php

namespace FilamentQuickForm\FormBuilder;

use Filament\Contracts\Plugin;
use Filament\Panel;
use FilamentQuickForm\FormBuilder\Filament\Resources\FormTypesResource;

class FormBuilderPlugin implements Plugin
{
    public static function make(): static
    {
        return new static();
    }

    public function getId(): string
    {
        return 'form-builder';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            FormTypesResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        // 
    }
}