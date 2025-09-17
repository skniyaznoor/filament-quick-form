<?php

namespace Quickform\Formbuilder\Filament\Components\Fields\Creators;

use Filament\Forms;

class DateTimeCreator
{
    public static function create(string $label, array $field): Forms\Components\DateTimePicker
    {
        return Forms\Components\DateTimePicker::make($label)
            ->required($field['required'] ?? false)
            ->default($field['default_value'] ?? null)
            ->reactive()
            ->minDate($field['min_date'] ?? null)
            ->maxDate($field['max_date'] ?? null)
            ->afterStateHydrated(function ($component) use ($field) {
                if (!$component->getState()) {
                    $component->state($field['default_value'] ?? '');
                }
            });
    }
}