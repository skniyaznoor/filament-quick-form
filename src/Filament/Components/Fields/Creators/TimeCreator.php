<?php

namespace Quickform\Formbuilder\Filament\Components\Fields\Creators;

use Filament\Forms;

class TimeCreator
{
    public static function create(string $label, array $field): Forms\Components\TimePicker
    {
        return Forms\Components\TimePicker::make($label)
            ->required($field['required'] ?? false)
            ->default($field['default_value'] ?? null)
            ->reactive()
            ->minDate($field['min_time'] ?? null)
            ->maxDate($field['max_time'] ?? null)
            ->afterStateHydrated(function ($component) use ($field) {
                if (!$component->getState()) {
                    $component->state($field['default_value'] ?? '');
                }
            });
    }
}