<?php

namespace FilamentQuickForm\FormBuilder\Filament\Components\Fields\Creators;

use Filament\Forms;

class TextareaCreator
{
    public static function create(string $label, array $field): Forms\Components\Textarea
    {
        return Forms\Components\Textarea::make($label)
            ->placeholder($field['placeholder'] ?? '')
            ->maxLength($field['max_length'] ?? 255)
            ->minLength($field['min_length'] ?? 0)
            ->required($field['required'] ?? false)
            ->default($field['default_value'] ?? null)
            ->readOnly($field['readonly'] ?? false)
            ->reactive()
            ->afterStateHydrated(function ($component) use ($field) {
                if (!$component->getState()) {
                    $component->state($field['default_value'] ?? '');
                }
            });
    }
}