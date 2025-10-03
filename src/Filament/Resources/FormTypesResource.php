<?php
namespace Quickform\Formbuilder\Filament\Resources;

use Quickform\Formbuilder\Filament\Resources\FormTypesResource\Pages;
use Quickform\Formbuilder\Filament\Resources\FormTypesResource\Schemas\FormTypesForm;
use Quickform\Formbuilder\Filament\Resources\FormTypesResource\Tables\FormTypesTable;
use Quickform\Formbuilder\Models\FormTypes;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Config;
use BackedEnum;
use Filament\Schemas\Schema;

class FormTypesResource extends Resource
{
    protected static ?string $model = FormTypes::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    
    public static function getLabel(): string
    {
        return __('quickform::formbuilder-quickform.config.resources.form_type.label');
    }
    
    public static function getPluralLabel(): string
    {
        return __('quickform::formbuilder-quickform.config.resources.form_type.plural_label');
    }
    
    public static function form(Schema $schema): Schema
    {
        return FormTypesForm::configure($schema);
    }
    
    public static function table(Table $table): Table
    {
        return FormTypesTable::configure($table);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFormTypes::route('/'),
            'create' => Pages\CreateFormTypes::route('/create'),
            'view' => Pages\ViewFormTypes::route('/{record}'),
            'edit' => Pages\EditFormTypes::route('/{record}/edit'),
        ];
    }
}