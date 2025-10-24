<?php

namespace FilamentQuickForm\FormBuilder\Filament\Components\Fields;

use FilamentQuickForm\FormBuilder\Filament\Components\Fields\Creators\{
    TextInputCreator,
    SelectCreator,
    RadioCreator,
    CheckboxCreator,
    FileUploadCreator,
    TextareaCreator,
    DateCreator,
    CheckCreator,
    ToggleCreator,
    TimeCreator,
    DateTimeCreator,
    ColorPickerCreator
};
use Illuminate\Support\Str;
use Filament\Forms\Set;
use Filament\Forms;
use Illuminate\Support\Facades\Log;
use FilamentQuickForm\FormBuilder\Models\FormTypes;

class FieldCreatorFactory
{
    protected static array $fieldTypeMap = [
        'text' => TextInputCreator::class,
        'select' => SelectCreator::class,
        'radio' => RadioCreator::class,
        'checkbox' => CheckboxCreator::class,
        'fileupload' => FileUploadCreator::class,
        'textarea' => TextareaCreator::class,
        'date' => DateCreator::class,
        'check' => CheckCreator::class,
        'toggle' => ToggleCreator::class,
        'time' => TimeCreator::class,
        'datetime' => DateTimeCreator::class,
        'colorpicker' => ColorPickerCreator::class,
    ];

    public static function createField(array $field, $model = null)
    {
        $type = $field['type'] ?? 'text';
        $label = $field['label'] ?? 'Field';
        $name = $field['name'] ?? Str::snake($label);
        $defaultValue = $field['default_value'] ?? null;
        $dependsOn = $field['depends_on'] ?? null;
        $dependentCondition = $field['dependent_condition'] ?? null;
        $validationType = $field['validation_type'] ?? null;
        $customvalidationType = $field['custom_validation'] ?? null;
        $optionType = $field['option_type'] ?? null;
        $parsedOptions = $field['parsed_options'] ?? [];

        $inputType = $model
            ? $model::whereJsonContains('fields', [['type' => $type]])->first()
            : FormTypes::whereJsonContains('layouts', [['fields' => [['type' => $type]]]])->first();

        if ($inputType) {
            try {
                $creatorClass = static::$fieldTypeMap[$type] ?? TextInputCreator::class;
                $fieldComponent = $creatorClass::create($label, $field);

                if ($type === 'colorpicker') {
                    $fieldComponent = self::applyOptionType($fieldComponent, $optionType);
                }

                $fieldComponent = self::applyValidationType($fieldComponent, $validationType);

                if ($dependsOn && $dependentCondition) {
                    $parentField = Str::snake($dependsOn);
                    $fieldComponent->visible(function (callable $get) use ($parentField, $dependentCondition) {
                        $value = $get($parentField);
                        if (is_array($value)) {
                            return in_array((string) $dependentCondition, array_map('strval', $value), true);
                        }
                        return (string) $value === (string) $dependentCondition;
                    });
                }

                return $fieldComponent;

            } catch (\Throwable $e) {
                Log::error('Error creating field:', [
                    'type' => $type,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return Forms\Components\TextInput::make($name)
                    ->label($label)
                    ->placeholder('Error rendering field');
            }
        }

        return Forms\Components\TextInput::make($name)
            ->label($label)
            ->placeholder('Unknown input type')
            ->default($defaultValue);
    }

    protected static function applyValidationType($fieldComponent, $validationType)
    {
        switch ($validationType) {
            case 'email':
                return $fieldComponent->email();
            case 'url':
                return $fieldComponent->url();
            case 'tel':
                return $fieldComponent->tel();
            case 'password':
                return $fieldComponent->password()->revealable();
            case 'numeric':
                return $fieldComponent->numeric();
            case 'integer':
                return $fieldComponent->integer();
            default:
                return $fieldComponent;
        }
    }

    protected static function applyOptionType($fieldComponent, $optionType)
    {
        switch ($optionType) {
            case 'hsl':
                return $fieldComponent->hsl();
            case 'rgb':
                return $fieldComponent->rgb();
            case 'rgba':
                return $fieldComponent->rgba();
            default:
                return $fieldComponent;
        }
    }
}