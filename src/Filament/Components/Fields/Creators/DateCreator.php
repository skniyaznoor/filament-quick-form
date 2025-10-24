<?php

namespace FilamentQuickForm\FormBuilder\Filament\Components\Fields\Creators;

use Filament\Forms;

class DateCreator
{
    public static function create(string $label, array $field): Forms\Components\DatePicker
    {
        return Forms\Components\DatePicker::make($label)
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