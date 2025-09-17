<?php

namespace Quickform\Formbuilder\Filament\Resources\FormTypesResource\Pages;

use Quickform\Formbuilder\Filament\Resources\FormTypesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFormTypes extends EditRecord
{
    protected static string $resource = FormTypesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
