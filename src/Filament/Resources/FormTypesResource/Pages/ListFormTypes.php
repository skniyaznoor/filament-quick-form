<?php

namespace Quickform\Formbuilder\Filament\Resources\FormTypesResource\Pages;

use Quickform\Formbuilder\Filament\Resources\FormTypesResource;
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
