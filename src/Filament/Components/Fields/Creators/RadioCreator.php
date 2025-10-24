<?php

namespace FilamentQuickForm\FormBuilder\Filament\Components\Fields\Creators;

use Filament\Forms;

class RadioCreator
{
    public static function create(string $label, array $field): Forms\Components\Radio
    {
        return Forms\Components\Radio::make($label)
            ->options($field['options'] ?? [])
            ->required($field['required'] ?? false)
            ->default($field['default_value'] ?? null)
            ->reactive()
            ->afterStateHydrated(function ($component) use ($field) {
                if (!$component->getState()) {
                    $component->state($field['default_value'] ?? null);
                }
            });
    }
}