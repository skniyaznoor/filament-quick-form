<?php

namespace Quickform\Formbuilder\Filament\Resources\FormTypesResource\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Quickform\Formbuilder\Models\FormTypes; 
use Quickform\Formbuilder\Filament\Components\Fields\FieldCreatorFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DynamicFormBuilder
{
    public static function buildFormSchema(string $slug)
    {
        $formType = FormTypes::where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if (!$formType) {
            return [];
        }

        $layouts = $formType->layouts;
        $fieldsByLayout = [];
        $layoutType = $formType->form_layout ?? 'one_page';
        $allFields = [];

        foreach ($layouts as $layout) {
            $layoutName = $layout['layout_name'] ?? 'Default';
            $formColumns = $formType['form_columns'] ?? 1;
            $fields = [];

            if (isset($layout['fields']) && is_array($layout['fields'])) {
                foreach ($layout['fields'] as $fieldConfig) {
                    $fieldData = static::prepareFieldData($fieldConfig);
                    $columnName = Str::snake($fieldData['label']);
                    
                    $field = FieldCreatorFactory::createField($fieldData)
                        ->name($columnName)
                        ->statePath('fields.' . $columnName)
                        ->reactive()
                        ->afterStateUpdated(function ($component, $state) {
                            // $component->fill();
                            $component->state($state);
                        });

                    if ($field) {
                        $fields[] = $field;
                        $allFields[$columnName] = $fieldData;
                    }
                }
            }

            $fieldsByLayout[] = Grid::make($formColumns)
                ->schema($fields)
                ->columns($formColumns) 
                ->columnSpan('full');
        }

        return static::generateLayout($layoutType, $layouts, $fieldsByLayout);
    }

    protected static function prepareFieldData(array $fieldConfig): array
    {
        $fieldData = [
            'type' => $fieldConfig['type'],
            'label' => $fieldConfig['label'],
            'slug' => $fieldConfig['slug'] ?? false,
            'required' => $fieldConfig['required'] ?? false,
            'readonly' => $fieldConfig['readonly'] ?? false,
            'placeholder' => $fieldConfig['placeholder'] ?? null,
            'default_value' => $fieldConfig['default_value'] ?? null,
            'validation_type' => $fieldConfig['validation_type'] ?? null,
            'options' => $fieldConfig['options'] ?? null,
            'option_type' => $fieldConfig['option_type'] ?? null,
            'field_label' => $fieldConfig['field_label'] ?? null,
            'foreign_key_enabled' => $fieldConfig['foreign_key_enabled'] ?? false,
            'foreign_table' => $fieldConfig['foreign_table'] ?? null,
            'multiple' => $fieldConfig['multiple'] ?? false,
            'max_date' => $fieldConfig['max_date'] ?? null,
            'min_date' => $fieldConfig['min_date'] ?? null,
            'minSize' => $fieldConfig['minSize'] ?? null,
            'minFiles' => $fieldConfig['minFiles'] ?? null,
            'maxSize' => $fieldConfig['maxSize'] ?? null,
            'maxFiles' => $fieldConfig['maxFiles'] ?? null,
            'min_length' => $fieldConfig['min_length'] ?? null,
            'max_length' => $fieldConfig['max_length'] ?? null,
            'depends_on' => $fieldConfig['depends_on'] ?? null,
            'dependent_condition' => $fieldConfig['dependent_condition'] ?? null,
        ];

        if ($fieldConfig['type'] === 'fileupload' && !empty($fieldConfig['default_value'])) {
            $fieldData['default_value'] = is_string($fieldConfig['default_value']) 
                ? json_decode($fieldConfig['default_value'], true) 
                : $fieldConfig['default_value'];
        }

        return $fieldData;
    }

    protected static function generateLayout(string $layoutType, array $layouts, array $fieldsByLayout): array
    {
        if ($layoutType === 'tabs') {
            return [
                Tabs::make('Form Details')
                    ->tabs(
                        collect($layouts)->map(function ($layout, $index) use ($fieldsByLayout) {
                            return Tab::make($layout['layout_name'] ?? "Tab $index")
                                ->schema([$fieldsByLayout[$index]]);
                        })->toArray()
                    ),
            ];
        } elseif ($layoutType === 'wizard') {
            return [
                Wizard::make()
                    ->steps(
                        collect($layouts)->map(function ($layout, $index) use ($fieldsByLayout) {
                            return Step::make($layout['layout_name'] ?? "Step $index")
                                ->schema([$fieldsByLayout[$index]]);
                        })->toArray()
                    ),
            ];
        }
        return $fieldsByLayout;
    }
}