<?php

namespace Quickform\Formbuilder\Filament\Components\Fields\Creators;

use Filament\Forms;

class CheckboxCreator
{
    public static function create(string $label, array $field): Forms\Components\CheckboxList
    {
        return Forms\Components\CheckboxList::make($label)
            ->options($field['options'] ?? [])
            ->required($field['required'] ?? false)
            ->reactive()
            ->afterStateHydrated(function ($component) use ($field) {
                if (!$component->getState()) {
                    $component->state($field['default_value'] ?? []);
                }
            })
            ->afterStateUpdated(function ($component, $state) {
                $component->state(is_array($state) ? $state : [$state]);
            })
            ->dehydrateStateUsing(function ($state) {
                return !$state ? [] : (is_array($state) ? array_values($state) : [$state]);
            });
    }
}