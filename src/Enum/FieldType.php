<?php

namespace FilamentQuickForm\FormBuilder\Enum;

use BenSampo\Enum\Enum;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;

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
            self::TEXT => __('filament-quick-form::form-builder.form-type.field.type.text'),
            self::TEXTAREA => __('filament-quick-form::form-builder.form-type.field.type.textarea'),
            self::RADIO => __('filament-quick-form::form-builder.form-type.field.type.radio'),
            self::CHECK => __('filament-quick-form::form-builder.form-type.field.type.check'),
            self::TOGGLE => __('filament-quick-form::form-builder.form-type.field.type.toggle'),
            self::DATE => __('filament-quick-form::form-builder.form-type.field.type.date'),
            self::DATETIME => __('filament-quick-form::form-builder.form-type.field.type.datetime'),
            self::TIME => __('filament-quick-form::form-builder.form-type.field.type.time'),
            self::NUMBER => __('filament-quick-form::form-builder.form-type.field.type.number'),
            self::SELECT => __('filament-quick-form::form-builder.form-type.field.type.select'),
            self::CHECKBOX => __('filament-quick-form::form-builder.form-type.field.type.checkbox'),
            self::COLORPICKER => __('filament-quick-form::form-builder.form-type.field.type.colorpicker'),
            self::FILEUPLOAD => __('filament-quick-form::form-builder.form-type.field.type.fileupload'),
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