<?php

namespace Quickform\Formbuilder\Filament\Components\Fields\Creators;

use Filament\Forms;
use Illuminate\Support\Str;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Log;

class TextInputCreator
{
    public static function create(string $label, array $field): Forms\Components\TextInput
    {
        $textInput = Forms\Components\TextInput::make($label)
            ->placeholder($field['placeholder'] ?? '')
            ->maxLength($field['max_length'] ?? 255)
            ->minLength($field['min_length'] ?? 0)
            ->required($field['required'] ?? false)
            ->readOnly($field['readonly'] ?? false)
            ->default($field['default_value'] ?? '')
            // ->reactive()
            ->live(onBlur: true)
            // ->debounce('100ms')
            ->afterStateHydrated(function ($component) use ($field) {
                if (!$component->getState()) {
                    $component->state($field['default_value'] ?? '');
                }
            });

        if ($field['slug'] ?? false) {
            $textInput->afterStateUpdated(function ($state, $component) {
                $slug = Str::slug($state); 
                $component->state($slug);
            });
        }

        return $textInput;
    }
}
