<?php

namespace Quickform\Formbuilder\Enum;

use BenSampo\Enum\Enum;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Lang;

final class FieldType extends Enum
{
    const TEXT = 'text';
    const TEXTAREA = 'textarea';
    const RADIO = 'radio';
    const CHECK = 'check';
    const TOGGLE = 'toggle';
    const DATE = 'date';
    const DATETIME = 'datetime';
    const TIME = 'time';
    const NUMBER = 'number';
    const SELECT = 'select';
    const CHECKBOX = 'checkbox';
    const COLORPICKER = 'colorpicker';
    const FILEUPLOAD = 'fileupload';

    public static function getLabelTypes(): array
    {
        return [
            self::TEXT => __('quickform::formbuilder-quickform.formtypes.fields.types.text'),
            self::TEXTAREA => __('quickform::formbuilder-quickform.formtypes.fields.types.textarea'),
            self::RADIO => __('quickform::formbuilder-quickform.formtypes.fields.types.radio'),
            self::CHECK => __('quickform::formbuilder-quickform.formtypes.fields.types.check'),
            self::TOGGLE => __('quickform::formbuilder-quickform.formtypes.fields.types.toggle'),
            self::DATE => __('quickform::formbuilder-quickform.formtypes.fields.types.date'),
            self::DATETIME => __('quickform::formbuilder-quickform.formtypes.fields.types.datetime'),
            self::TIME => __('quickform::formbuilder-quickform.formtypes.fields.types.time'),
            self::NUMBER => __('quickform::formbuilder-quickform.formtypes.fields.types.number'),
            self::SELECT => __('quickform::formbuilder-quickform.formtypes.fields.types.select'),
            self::CHECKBOX => __('quickform::formbuilder-quickform.formtypes.fields.types.checkbox'),
            self::COLORPICKER => __('quickform::formbuilder-quickform.formtypes.fields.types.colorpicker'),
            self::FILEUPLOAD => __('quickform::formbuilder-quickform.formtypes.fields.types.fileupload'),
        ];
    }

    public function addToTable(Blueprint $table, array $field): void
    {
        $columnName = $field['name'];

        if ($field['foreign_key_enabled'] && $field['foreign_table']) {
            $this->addForeignKey($table, $columnName, $field['foreign_table']);
            return;
        }

        $columnType = $this->getColumnType();

        match($columnType) {
            'text' => $table->text($columnName)->nullable(),
            'boolean' => $table->boolean($columnName)->nullable(),
            'date' => $table->date($columnName)->nullable(),
            'datetime' => $table->datetime($columnName)->nullable(),
            'time' => $table->time($columnName)->nullable(),
            'decimal' => $table->decimal($columnName)->nullable(),
            'string' => $table->string($columnName)->nullable(),
            'json' => $table->json($columnName)->nullable(),
            default => $table->string($columnName)->nullable(),
        };
    }

    private function addForeignKey(Blueprint $table, string $columnName, string $foreignTable): void
    {
        $table->unsignedBigInteger($columnName)->nullable();
        $table->foreign($columnName)
            ->references('id')
            ->on($foreignTable)
            ->onDelete('cascade')
            ->onUpdate('cascade');
    }

    private function getColumnType(): string
    {
        return match($this->value) {
            self::TEXTAREA => 'text',
            self::RADIO, self::CHECK, self::TOGGLE => 'boolean',
            self::DATE => 'date',
            self::DATETIME => 'datetime',
            self::TIME => 'time',
            self::NUMBER => 'decimal',
            self::SELECT => 'string',
            self::CHECKBOX, self::FILEUPLOAD => 'json',
            self::COLORPICKER => 'string',
            self::TEXT => 'string',
        };
    }

    public static function flattenFields(array $layouts): array
    {
        $fields = [];
        
        foreach ($layouts as $layout) {
            foreach ($layout['fields'] as $field) {
                $fields[] = [
                    'name' => Str::snake($field['field_label'] ?? $field['label']),
                    'type' => $field['type'] ?? 'text',
                    'validation_type' => $field['validation_type'] ?? null,
                    'max_length' => $field['max_length'] ?? null,
                    'foreign_key_enabled' => $field['foreign_key_enabled'] ?? false,
                    'foreign_table' => $field['foreign_table'] ?? null,
                ];
            }
        }

        return $fields;
    }
}