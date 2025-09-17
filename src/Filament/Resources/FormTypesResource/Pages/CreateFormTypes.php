<?php

namespace Quickform\Formbuilder\Filament\Resources\FormTypesResource\Pages;

use Quickform\Formbuilder\Filament\Resources\FormTypesResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFormTypes extends CreateRecord
{
    protected static string $resource = FormTypesResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
