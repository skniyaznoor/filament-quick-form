<?php

namespace Quickform\Formbuilder\Filament\Components\Fields\Creators;

use Filament\Forms;

class SelectCreator
{
    public static function create(string $label, array $field): Forms\Components\Select
    {
        return Forms\Components\Select::make($label)
            ->options(
                collect($field['options'] ?? [])
                    ->filter()
                    ->toArray()
            )
            ->required($field['required'] ?? false)
            ->default(
                ($field['multiple'] ?? false)
                    ? (is_array($field['default_value'] ?? null)
                        ? $field['default_value']
                        : explode(',', $field['default_value'] ?? ''))
                    : ($field['default_value'] ?? null)
            )
            ->multiple($field['multiple'] ?? false)
            ->reactive()
            ->afterStateHydrated(function ($component, $state) use ($field) {
                if (!$state) {
                    $component->state(
                        ($field['multiple'] ?? false)
                            ? (is_array($field['default_value'] ?? null)
                                ? $field['default_value']
                                : explode(',', $field['default_value'] ?? ''))
                            : ($field['default_value'] ?? null)
                    );
                }
            })
            ->afterStateUpdated(function ($component, $state) use ($field) {
                $component->state(
                    ($field['multiple'] ?? false)
                        ? (is_array($state)
                            ? array_values($state)
                            : explode(',', $state ?? ''))
                        : $state
                );
            });
    }
}