<?php

namespace Quickform\Formbuilder\Filament\Components\Fields\Creators;
use Illuminate\Support\Facades\Storage;

use Filament\Forms;

class FileUploadCreator
{
    public static function create(string $label, array $field): Forms\Components\FileUpload
    {   
        return Forms\Components\FileUpload::make($label)
            ->required($field['required'] ?? false)
            ->multiple()
            ->reactive()
            ->minFiles($field['minFiles'] ?? 1)
            ->maxFiles($field['maxFiles'] ?? 10)
            ->maxSize($field['maxSize'] ?? 5120)
            ->minSize($field['minSize'] ?? 1)
            ->directory('uploads')
            ->visibility('public')
            ->imagePreviewHeight('250')
            ->downloadable()
            ->openable()
            ->default($field['default_value'])
            ->afterStateUpdated(function ($component, $state) {
                // $component->fill();
                $component->state($state);
            })
        ;
    }
}