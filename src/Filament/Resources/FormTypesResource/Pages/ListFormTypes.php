<?php

namespace FilamentQuickForm\FormBuilder\Filament\Resources\FormTypesResource\Pages;

use FilamentQuickForm\FormBuilder\Filament\Resources\FormTypesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFormTypes extends ListRecords
{
    protected static string $resource = FormTypesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
