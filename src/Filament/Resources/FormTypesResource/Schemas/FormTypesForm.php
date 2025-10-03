<?php

namespace Quickform\Formbuilder\Filament\Resources\FormTypesResource\Schemas;

use Quickform\Formbuilder\Filament\Resources\FormTypesResource\Pages;
use Quickform\Formbuilder\Filament\Resources\FormTypesResource\RelationManagers;
use Quickform\Formbuilder\Models\FormTypes;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Hash;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\{ KeyValue, TimePicker, DateTimePicker, DatePicker, Tabs, Button, Modal, Card, TextInput, Textarea, Radio, Repeater, Toggle, Select, Hidden};
use Quickform\Formbuilder\Filament\Pages\Settings;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Filament\Forms\Set;
use Filament\Forms\Contracts\HasForms;
use Quickform\Formbuilder\Enum\FieldType;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Config;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\{Section, Wizard, Fieldset};

class FormTypesForm
{
    public static function getDefaultColor(): string
    {
        return Config::get('quickform.default_color', 'warning');
    }

    public static function configure(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (empty($state)) {
                                    return;
                                }
                                $slug = Str::slug($state);
                                $set('slug', $slug);
                            }),
                        TextInput::make('slug')->disabled()->dehydrated(true),
                        Textarea::make('description')->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Fields Configuration')
                    ->schema([
                        Radio::make('form_layout')
                            ->label('Show the form as')
                            ->options([
                                'one_page' => 'One Page',
                                'wizard' => 'Wizard',
                                'tabs' => 'Tabs',
                            ])
                            ->default('one_page')
                            ->reactive()
                            ->required(),

                        Radio::make('form_columns')
                        ->label(fn ($get) => $get('form_layout') === 'one_page' 
                            ? 'Number of Columns' 
                            : 'Layout Options'
                            )
                            ->options([
                                1 => '1',
                                2 => '2',
                                3 => '3',
                                4 => '4',
                            ])
                            ->default(1)
                            ->required()
                            ->reactive(),
                        ])
                        ->columns(2),   
                Repeater::make('layouts')
                    ->label('')
                    ->deleteAction(
                        fn (Action $action) => $action->requiresConfirmation(),
                    )
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('layout_name')
                            ->label('Layout Name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => 
                                $set('fields_label', "{$state} Fields"))
                            ->rules(['min:3', 'max:255']),
                        Repeater::make('fields')
                            ->label('Fields')
                            ->schema([
                                TextInput::make('label')
                                    ->label('Field Label')
                                    ->required()
                                    ->rules(['min:3', 'max:255'])
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('field_label', $state)),
                                Select::make('type')
                                    ->label('Field Type')
                                    ->options(FieldType::getLabelTypes())
                                    ->rules(['required', 'string', Rule::in(FieldType::getValues())])
                                    ->required()
                                    ->reactive(),
                                ])   
                                ->deleteAction(
                                    fn (Action $action) => $action->requiresConfirmation(),
                                )
                                ->extraItemActions([
                                    Action::make('Settings')
                                        ->slideOver()
                                        ->color(fn () => static::getDefaultColor())
                                        ->tooltip('More field options')
                                        ->icon('heroicon-m-cog')
                                        ->modalIcon('heroicon-m-cog')
                                        ->modalDescription(__('Advanced fields settings'))
                                        ->fillForm(
                                            fn (array $arguments, Repeater $component) => $component->getItemState($arguments['item'])
                                        )
                                        ->form(function (Get $get, array $arguments, Repeater $component) {
                                            $state = $component->getState();
                                            $itemIndex = $arguments['item'];
                                            $currentState = $state[$itemIndex] ?? [];
                                            
                                            return [
                                                
                                                Fieldset::make('Validation Options')
                                                    ->label('Validation Options')
                                                    ->visible(fn (callable $get) => $get('type') !== null)
                                                    ->schema(function (callable $get) use ($currentState) {
                                                        $type = $get('type');
                                                        return match ($type) {
                                                            'text' => [
                                                                Toggle::make('required')
                                                                    ->label('Required')
                                                                    ->default($currentState['required'] ?? false)
                                                                    ->onColor('success')
                                                                    ->reactive() 
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['required'] ?? false);
                                                                        }
                                                                    }),
                                                                TextInput::make('min_length')
                                                                    ->label('Minimum Length')
                                                                    ->numeric()
                                                                    ->nullable()
                                                                    ->default($currentState['min_length'] ?? null)
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['min_length'] ?? null);
                                                                        }
                                                                    }),
                                                                TextInput::make('max_length')
                                                                    ->label('Maximum Length')
                                                                    ->numeric()
                                                                    ->nullable()
                                                                    ->default($currentState['max_length'] ?? null) 
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['max_length'] ?? null);
                                                                        }
                                                                    }),
                                                                TextInput::make('placeholder')
                                                                    ->label('Placeholder')
                                                                    ->default($currentState['placeholder'] ?? '') 
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['placeholder'] ?? '');
                                                                        }
                                                                    }),
                                                                Toggle::make('readonly')
                                                                    ->label('Readonly')
                                                                    ->default($currentState['readonly'] ?? false)
                                                                    ->onColor('success')
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['readonly'] ?? false);
                                                                        }
                                                                    }),
                                                                Select::make('validation_type')
                                                                    ->label('Validation Type')
                                                                    ->options([
                                                                        'email' => 'Email',
                                                                        'numeric' => 'Numeric',
                                                                        'integer' => 'Integer',
                                                                        'password' => 'Password',
                                                                        'tel' => 'Telephone',
                                                                        'url' => 'URL',
                                                                    ])
                                                                    ->helperText('Choose the type of validation to apply to this text field.')
                                                                    ->default($currentState['validation_type'] ?? null) 
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['validation_type'] ?? null);
                                                                        }
                                                                    }),
                                                                Toggle::make('slug')
                                                                    ->label('Slug')
                                                                    ->default($currentState['slug'] ?? false)
                                                                    ->onColor('success')
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['slug'] ?? false);
                                                                        }
                                                                    }),
                                                                Toggle::make('foreign_key_enabled')
                                                                    ->label('Enable Foreign Key')
                                                                    ->default($currentState['foreign_key_enabled'] ?? false)
                                                                    ->onColor('primary')
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['foreign_key_enabled'] ?? false);
                                                                        }
                                                                    }),
                                                
                                                                Select::make('foreign_table')
                                                                    ->label('Select Table')
                                                                    ->options(function () {
                                                                        $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
                                                                        $dbName = config('database.connections.mysql.database');
                                                                        $tableKey = "Tables_in_{$dbName}";
                                                                        
                                                                        return collect($tables)
                                                                            ->pluck($tableKey, $tableKey)
                                                                            ->toArray();
                                                                    })
                                                                    ->visible(fn (callable $get) => $get('foreign_key_enabled') === true)
                                                                    ->placeholder('Select a table')
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['foreign_table'] ?? null);
                                                                        }
                                                                    }),
                                                            ],
                                                            'textarea' => [
                                                                Toggle::make('required')
                                                                    ->label('Required')
                                                                    ->default($currentState['required'] ?? false)
                                                                    ->onColor('success')
                                                                    ->reactive() 
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['required'] ?? false);
                                                                        }
                                                                    }),
                                                                TextInput::make('min_length')
                                                                    ->label('Minimum Length')
                                                                    ->numeric()
                                                                    ->nullable()
                                                                    ->default($currentState['min_length'] ?? null)
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['min_length'] ?? null);
                                                                        }
                                                                    }),
                                                                TextInput::make('max_length')
                                                                    ->label('Maximum Length')
                                                                    ->numeric()
                                                                    ->nullable()
                                                                    ->default($currentState['max_length'] ?? null) 
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['max_length'] ?? null);
                                                                        }
                                                                    }),
                                                                TextInput::make('placeholder')
                                                                    ->label('Placeholder')
                                                                    ->default($currentState['placeholder'] ?? '') 
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['placeholder'] ?? '');
                                                                        }
                                                                    }),
                                                                Toggle::make('readonly')
                                                                    ->label('Readonly')
                                                                    ->default($currentState['readonly'] ?? false)
                                                                    ->onColor('success')
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['readonly'] ?? false);
                                                                        }
                                                                    }),
                                                            ],
                                                            'fileupload' => [
                                                                Toggle::make('required')
                                                                    ->label('Required')
                                                                    ->default($currentState['required'] ?? false)
                                                                    ->onColor('success')
                                                                    ->reactive() 
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['required'] ?? false);
                                                                        }
                                                                    }),
                                                                Toggle::make('multiple')
                                                                    ->label('Allow Multiple Selections File')
                                                                    ->default($currentState['multiple'] ?? false)
                                                                    ->onColor('success')
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['multiple'] ?? false);
                                                                        }
                                                                    }),
                                                                TextInput::make('minSize')
                                                                    ->label('Minimum File Size (KB)')
                                                                    ->default($currentState['minSize'] ?? '1')
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['minSize'] ?? '1');
                                                                        }
                                                                    }),
                                                                TextInput::make('maxSize')
                                                                    ->label('Maximum File Size (KB)')
                                                                    ->default($currentState['maxSize'] ?? '5120')
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['maxSize'] ?? '5120');
                                                                        }
                                                                    }),
                                                                TextInput::make('minFiles')
                                                                    ->label('Minimum Files')
                                                                    ->default($currentState['minFiles'] ?? '1')
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['minFiles'] ?? '1');
                                                                        }
                                                                    }),
                                                            
                                                                TextInput::make('maxFiles')
                                                                    ->label('Maximum Files')
                                                                    ->default($currentState['maxFiles'] ?? '10')
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['maxFiles'] ?? '10');
                                                                        }
                                                                    }),
                                                            ],
                                                            'date' => [
                                                                Toggle::make('required')
                                                                    ->label('Required')
                                                                    ->default($currentState['required'] ?? false)
                                                                    ->onColor('success')
                                                                    ->reactive() 
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['required'] ?? false);
                                                                        }
                                                                    }),
                                                                TextInput::make('placeholder')
                                                                    ->label('Placeholder')
                                                                    ->default($currentState['placeholder'] ?? '') 
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['placeholder'] ?? '');
                                                                        }
                                                                    }),
                                                                DatePicker::make('min_date')
                                                                    ->label('Minimum Date')
                                                                    ->default($currentState['min_date'] ?? null)
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['min_date'] ?? null);
                                                                        }
                                                                    }),
                                                                DatePicker::make('max_date')
                                                                    ->label('Maximum Date')
                                                                    ->default($currentState['max_date'] ?? null)
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['max_date'] ?? null);
                                                                        }
                                                                    }),
                                                            ],
                                                            'time' => [
                                                                Toggle::make('required')
                                                                    ->label('Required')
                                                                    ->default($currentState['required'] ?? false)
                                                                    ->onColor('success')
                                                                    ->reactive() 
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['required'] ?? false);
                                                                        }
                                                                    }),
                                                                TextInput::make('placeholder')
                                                                    ->label('Placeholder')
                                                                    ->default($currentState['placeholder'] ?? '') 
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['placeholder'] ?? '');
                                                                        }
                                                                    }),
                                                                TimePicker::make('min_time')
                                                                    ->label('Minimum Time')
                                                                    ->default($currentState['min_time'] ?? null)
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['min_time'] ?? null);
                                                                        }
                                                                    }),
                                                                TimePicker::make('max_time')
                                                                    ->label('Maximum Time')
                                                                    ->default($currentState['max_time'] ?? null)
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['max_time'] ?? null);
                                                                        }
                                                                    }),
                                                            ],
                                                            'datetime' => [
                                                                Toggle::make('required')
                                                                    ->label('Required')
                                                                    ->default($currentState['required'] ?? false)
                                                                    ->onColor('success')
                                                                    ->reactive() 
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['required'] ?? false);
                                                                        }
                                                                    }),
                                                                TextInput::make('placeholder')
                                                                    ->label('Placeholder')
                                                                    ->default($currentState['placeholder'] ?? '') 
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['placeholder'] ?? '');
                                                                        }
                                                                    }),
                                                                DateTimePicker::make('min_time')
                                                                    ->label('Minimum Time')
                                                                    ->default($currentState['min_time'] ?? null)
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['min_time'] ?? null);
                                                                        }
                                                                    }),
                                                                DateTimePicker::make('max_time')
                                                                    ->label('Maximum Time')
                                                                    ->default($currentState['max_time'] ?? null)
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['max_time'] ?? null);
                                                                        }
                                                                    }),
                                                            ],
                                                            'checkbox','radio' => [
                                                                Section::make('')
                                                                    ->schema([
                                                                        Toggle::make('required')
                                                                            ->label('Required')
                                                                            ->default($currentState['required'] ?? false)
                                                                            ->onColor('success')
                                                                            ->reactive()
                                                                            ->afterStateHydrated(function ($component) use ($currentState) {
                                                                                if (!$component->getState()) {
                                                                                    $component->state($currentState['required'] ?? false);
                                                                                }
                                                                            }),
                                                                    ]),

                                                                KeyValue::make('options')
                                                                    ->label('Options (Key-Value Pairs)')
                                                                    ->keyLabel('Key')
                                                                    ->valueLabel('Value')
                                                                    ->default($currentState['options'] ?? [])
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['options'] ?? []);
                                                                        }
                                                                    })
                                                                    ->afterStateUpdated(function (callable $set, $state) {
                                                                        $set('options', $state); 
                                                                    }),
                                                            ],
                                                            'colorpicker' => [
                                                                Select::make('option_type')
                                                                    ->label('Option Type')
                                                                    ->options([
                                                                        'hsl' => 'HSL',
                                                                        'rgb' => 'RGB',
                                                                        'rgba' => 'RGBA',
                                                                    ])
                                                                    ->helperText('Choose the type of Color Option to apply to this.')
                                                                    ->default($currentState['option_type'] ?? null) 
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['option_type'] ?? null);
                                                                        }
                                                                    }),
                                                            ],
                                                            'select' => [
                                                                Section::make('')
                                                                    ->columns(2)
                                                                    ->columnSpan(2)
                                                                    ->schema([
                                                                        Toggle::make('required')
                                                                            ->label('Required')
                                                                            ->default($currentState['required'] ?? false)
                                                                            ->onColor('success')
                                                                            ->reactive()
                                                                            ->afterStateHydrated(function ($component) use ($currentState) {
                                                                                if (!$component->getState()) {
                                                                                    $component->state($currentState['required'] ?? false);
                                                                                }
                                                                            }),

                                                                        Toggle::make('multiple')
                                                                            ->label('Allow Multiple Selections')
                                                                            ->default($currentState['multiple'] ?? false)
                                                                            ->onColor('success')
                                                                            ->reactive()
                                                                            ->afterStateHydrated(function ($component) use ($currentState) {
                                                                                if (!$component->getState()) {
                                                                                    $component->state($currentState['multiple'] ?? false);
                                                                                }
                                                                            }),
                                                                        Toggle::make('foreign_key_enabled')
                                                                            ->label('Enable Foreign Key')
                                                                            ->default($currentState['foreign_key_enabled'] ?? false)
                                                                            ->onColor('primary')
                                                                            ->reactive()
                                                                            ->afterStateHydrated(function ($component) use ($currentState) {
                                                                                if (!$component->getState()) {
                                                                                    $component->state($currentState['foreign_key_enabled'] ?? false);
                                                                                }
                                                                            }),
                                                        
                                                                        Select::make('foreign_table')
                                                                            ->label('Select Table')
                                                                            ->options(function () {
                                                                                $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
                                                                                $dbName = config('database.connections.mysql.database');
                                                                                $tableKey = "Tables_in_{$dbName}";
                                                                                
                                                                                return collect($tables)
                                                                                    ->pluck($tableKey, $tableKey)
                                                                                    ->toArray();
                                                                            })
                                                                            ->visible(fn (callable $get) => $get('foreign_key_enabled') === true)
                                                                            ->placeholder('Select a table')
                                                                            ->reactive()
                                                                            ->afterStateHydrated(function ($component) use ($currentState) {
                                                                                if (!$component->getState()) {
                                                                                    $component->state($currentState['foreign_table'] ?? null);
                                                                                }
                                                                            }),
                                                                    ]),

                                                                KeyValue::make('options')
                                                                    ->label('Options (Key-Value Pairs)')
                                                                    ->keyLabel('Key')
                                                                    ->valueLabel('Value')
                                                                    ->default($currentState['options'] ?? [])
                                                                    ->reactive()
                                                                    ->afterStateHydrated(function ($component) use ($currentState) {
                                                                        if (!$component->getState()) {
                                                                            $component->state($currentState['options'] ?? []);
                                                                        }
                                                                    }),
                                                            ],
                                                            default => [],
                                                        };
                                                    }),
                                            ];
                                        })
                                        ->action(function (array $data, array $arguments, Repeater $component) {
                                            $state = $component->getState();
                                            $itemIndex = $arguments['item'];
                                            $state[$itemIndex] = array_merge($state[$itemIndex] ?? [], $data);
                                            $component->state($state);
                                        }),

                                Action::make('Dependency')
                                    ->label('Dependency')
                                    ->slideOver()
                                    ->tooltip('Manage field dependency')
                                    ->icon('heroicon-o-link')
                                    ->modalDescription(__('Set field dependencies'))
                                    ->form(function (callable $get, array $arguments, Repeater $component) {
                                        $state = $component->getState();
                                        $itemIndex = $arguments['item'];
                                        $currentDependsOn = $state[$itemIndex]['depends_on'] ?? null;
                                        $currentDependentCondition = $state[$itemIndex]['dependent_condition'] ?? null;

                                        $fields = collect($state);

                                        $fieldOptions = $fields
                                            ->pluck('label')
                                            ->filter()
                                            ->mapWithKeys(fn($label) => [$label => $label])
                                            ->toArray();

                                        $dependentOptions = [];
                                        $selectedField = $fields->firstWhere('label', $currentDependsOn);
                                        if ($selectedField && in_array($selectedField['type'], ['radio', 'checkbox', 'select'])) {
                                            $dependentOptions = collect($selectedField['options'] ?? [])
                                                ->mapWithKeys(fn($value, $key) => [$key => $value])
                                                ->toArray();
                                        }

                                        return [
                                            Select::make('depends_on')
                                                ->label('Depends On')
                                                ->options($fieldOptions)
                                                ->searchable()
                                                ->nullable()
                                                ->helperText('Select the field this field depends on.')
                                                ->default($currentDependsOn)
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, callable $set) {
                                                    $set('dependent_condition', null); 
                                                }),

                                            Select::make('dependent_condition')
                                                ->label('Condition')
                                                ->options(function (callable $get) use ($fields) {
                                                    $dependsOn = $get('depends_on');
                                                    $selectedField = $fields->firstWhere('label', $dependsOn);

                                                    if ($selectedField && in_array($selectedField['type'], ['radio', 'checkbox', 'select'])) {
                                                        return collect($selectedField['options'] ?? [])
                                                            ->mapWithKeys(fn($value, $key) => [$key => $value])
                                                            ->toArray();
                                                    }

                                                    return [];
                                                })
                                                ->searchable()
                                                ->nullable()
                                                ->helperText('Select the value this field depends on.')
                                                ->default($currentDependentCondition)
                                                ->visible(function (callable $get) use ($fields) {
                                                    $dependsOn = $get('depends_on');
                                                    $selectedField = $fields->firstWhere('label', $dependsOn);
                                                    return $selectedField && in_array($selectedField['type'], ['radio', 'checkbox', 'select']);
                                                }),

                                            TextInput::make('dependent_condition')
                                                ->label('Condition')
                                                ->helperText('Enter the value this field depends on.')
                                                ->nullable()
                                                ->default($currentDependentCondition)
                                                ->visible(function (callable $get) use ($fields) {
                                                    $dependsOn = $get('depends_on');
                                                    $selectedField = $fields->firstWhere('label', $dependsOn);
                                                    return !$selectedField || !in_array($selectedField['type'], ['radio', 'checkbox', 'select']);
                                                }),
                                        ];
                                    })
                                    ->action(function (array $data, array $arguments, Repeater $component) {
                                        $state = $component->getState();
                                        $itemIndex = $arguments['item'];

                                        $state[$itemIndex]['depends_on'] = $data['depends_on'] ?? null;
                                        $state[$itemIndex]['dependent_condition'] = $data['dependent_condition'] ?? null;

                                        $component->state($state);
                                    }),                                                        
                            
                                ])                    
                                ->collapsible()
                                ->reorderableWithButtons()
                                ->createItemButtonLabel('+ Add Field')
                                ->addActionAlignment(Alignment::End)
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                ->required()
                                ->defaultItems(0)
                                ->grid([
                                    'default' => 1,
                                    'md' => 2,
                                    'xl' => 3,
                                    '2xl' => 3,
                                ])
                                ->cloneable(),
                        ])
                    ->cloneable()
                    ->extraItemActions([])
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->createItemButtonLabel('+ Add Layout')
                    ->addActionAlignment(Alignment::Start)
                    ->itemLabel(fn (array $state): ?string => $state['layout_name'] ?? null)
                    ->required(),
            ]);
    }
}