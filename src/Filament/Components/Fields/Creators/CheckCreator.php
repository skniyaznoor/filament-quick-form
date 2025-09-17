<?php

namespace Quickform\Formbuilder\Filament\Components\Fields\Creators;

use Filament\Forms;

class CheckCreator
{
    public static function create(string $label, array $field): Forms\Components\Checkbox
    {
        return Forms\Components\Checkbox::make($label)
            ->label($field['label'] ?? $label) 
            ->required($field['required'] ?? false)
            ->afterStateHydrated(function ($component) use ($field) {
                if (!$component->getState()) {
                    $component->state($field['default_value'] ?? []);
                }
            });
    }
}
