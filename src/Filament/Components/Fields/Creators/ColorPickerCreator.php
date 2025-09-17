<?php

namespace Quickform\Formbuilder\Filament\Components\Fields\Creators;

use Filament\Forms;

class ColorPickerCreator
{
    public static function create(string $label, array $field): Forms\Components\ColorPicker
    {
        return Forms\Components\ColorPicker::make($label)
            ->default($field['default_value'] ?? null)
            ->reactive()
            ->dehydrated()  
            ->afterStateHydrated(function ($component, $state) use ($field) {
                if (!$component->getState()) {
                    $component->state($field['default_value'] ?? '');
                }
            });
    }
}
