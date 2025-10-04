<?php

namespace Quickform\Formbuilder\Filament\Components\Fields\Creators;

use Filament\Forms;

class ToggleCreator
{
    public static function create(string $label, array $field): Forms\Components\Toggle
    {
        return Forms\Components\Toggle::make($label)
            ->label($field['label'] ?? $label) 
            ->required($field['required'] ?? false)
            ->afterStateHydrated(function ($component) use ($field) {
                // $component->fill();
                if (!$component->getState()) {
                    $component->state($field['default_value'] ?? []);
                }
            });
    }
}
