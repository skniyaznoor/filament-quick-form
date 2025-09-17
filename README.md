# Quick-Form/Form-Builder

A dynamic form builder package for Laravel Filament that allows you to create, manage, and render custom forms with validation and dependencies.

## Features

- Create dynamic forms with custom fields and validations
- Support for field dependencies
- JSON storage for form data
- Automatic table creation for published forms
- Draft and publish workflow
- Data recovery system during form modifications
- Integration with Laravel Filament resources

## Installation

### Step 1: Configure Repository

Add the following to your project's `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "git@code.nettantra.com:skniyaz.noor/form-builder-package.git"
    }
]
```

### Step 2: Install Package

```bash
composer require quickform/formbuilder:dev-master
php artisan vendor:publish --tag=config
php artisan vendor:publish --tag=quickform-translations
php artisan quickform:install
```

### Step 3: Configuration Options

#### Resource Mappings Configuration

The **Resource Mappings** configuration allows you to dynamically map multiple database tables to their corresponding form types. This provides flexibility in handling different types of resources while maintaining a structured relationship between dynamic forms and database storage.

To configure resource mappings, use the following structure in your configuration file:

```php
'resource_mappings' => [
    'slug-1' => [ // Dynamic table slug name (must match the FormType slug)
        'table_name' => 'resource_table_1',    // The name of the database table
        'foreign_key_id' => 'resource_id_1'    // The custom foreign key identifier
    ],
    'slug-2' => [
        'table_name' => 'resource_table_2',    // Another resource table
        'foreign_key_id' => 'resource_id_2'    // Its corresponding foreign key identifier
    ],
    // Add more mappings as needed
    'default_color' => 'primary', //can change the color
],
```

##### Explanation of Configuration Keys:
1. **`slug-1`, `slug-2`, etc.**
   - These are unique identifiers (slugs) for different dynamic tables.
   - They must match the **slug name** of the corresponding form type.

2. **`table_name`**
   - Specifies the name of the database table where the resource data will be stored.
   - This table should exist in your database.

3. **`foreign_key_id`**
   - Defines the foreign key column name that will be used to establish relationships between records.
   - This should correspond to the appropriate foreign key field in the related table.

##### Key Benefits
This configuration setup allows you to:

- **Map dynamic forms** to specific database tables based on their type.
- **Customize foreign key relationships** for different resources.
- **Support multiple resource types** with distinct table structures.
- **Maintain clear separation** between different form types and their data storage, ensuring better organization and maintainability.

##### Usage Guidelines
- Ensure that the **slug keys** in the `resource_mappings` array exactly match the slug names used in your dynamic tables.
- Verify that the specified `table_name` values correspond to existing tables in your database.
- Ensure that the `foreign_key_id` values correctly match the foreign key columns in the related tables.

### Step 4: Configure Filament

Add the FormBuilder plugin to your Filament AdminPanelProvider:

```php
use Quickform\Formbuilder\FormBuilderPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FormBuilderPlugin::make(),
        ]);
}
```

## Usage

### Database Setup

Add the required columns to your resource's table migration:

```php
$table->json('fields')->nullable();
$table->unsignedBigInteger('form_type_id')->nullable();
$table->foreign('form_type_id')->references('id')->on('form_types')->onDelete('cascade');
```

### Resource Implementation

#### 1. Model Traits Implementation

The `HandleFormSavedTraits` is a crucial trait for dynamic form handling:

* **Purpose**: Automatically saves form data to a dynamically created database table based on the form type

* **Key Functionality**:
   * Overrides the `saved` model event
   * Processes form fields, converting them to JSON if needed
   * Creates or updates records in a dynamically named table
   * Uses database transactions for data integrity

```php
use Quickform\Formbuilder\Models\FormTypes;
use Quickform\Formbuilder\Models\Traits\HandleFormSavedTraits;


class YourResourceModel extends Model 
{
    use HandleFormSavedTraits;

    protected $fillable = ['form_type_id', 'fields']; //add your static fields;
    protected $casts = ['fields' => 'array'];

    public function formType() 
    {
        return $this->belongsTo(FormTypes::class);
    }
}
```

##### How It Works

When a form is saved, it automatically:
1. Decodes form fields
2. Identifies the corresponding form type
3. Creates a table name based on form type slug
4. Stores form-specific data in the dynamically created table
5. Links the data to the original resource via `resource_id`

This trait enables the package's dynamic form storage and retrieval mechanism.

#### 2. Form Schema Implementation

##### Case 1: Basic Implementation

You can use this schema or customize as per your need:

```php
use Quickform\Formbuilder\Models\FormTypes;
use Quickform\Formbuilder\Filament\Resources\Schemas\DynamicFormBuilder;

public static function form(Form $form): Form
{
    return $form
    ->schema([
        Card::make()
            ->schema([
                TextInput::make('reg_num')
                    ->required()
                    ->label('Register No.'), //static fields
               Select::make('form_type_id')
                    ->options(FormTypes::orderBy('title')
                        ->where('status', 'published')
                        ->pluck('title', 'id')
                    )
                    ->reactive() 
                    ->required()
                    ->disabled(fn ($record) => $record !== null) 
                    ->afterStateUpdated(function ($state, callable $set) {
                        $formType = FormTypes::find($state);
                        if ($formType) {
                            $set('slug', $formType->slug);
                        }
                    })
                    ->afterStateHydrated(function ($state, callable $set, $record) {
                        if ($record) {
                            $formType = FormTypes::find($record->form_type_id);
                            if ($formType) {
                                $set('slug', $formType->slug);
                            }
                        }
                    }),
                Card::make()
                    ->statePath('fields')
                    ->schema(function (callable $get) {
                        $selectedSlug = $get('slug');
                        if (!$selectedSlug) {
                            return [
                                Placeholder::make('dynamic_schema')
                                    ->content('')
                                    ->label(''),
                            ];
                        }
                        return DynamicFormBuilder::buildFormSchema($selectedSlug);
                    }),
            ]),
    ]);
}
```

##### Case 2: Using QuickForm Validation

If you want to utilize the validation capabilities of QuickForm, add the following to your resource:

```php
use Quickform\Formbuilder\Filament\Resources\FormTypesResource;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            Card::make()
                ->schema([
                    // Use the QuickForm validation
                    self::findRepeater(FormTypesResource::form($form)->getComponents(), 'fields') 
                        ?? Repeater::make('fields'),
                ]),
        ]);
}

public static function findRepeater(array $schema, string $repeaterName): ?Repeater
{
    foreach ($schema as $field) {
        if ($field instanceof Repeater && $field->getName() === $repeaterName) {
            return $field;
        }
        if (method_exists($field, 'getChildComponents')) {
            $result = self::findRepeater($field->getChildComponents(), $repeaterName);
            if ($result) {
                return $result;
            }
        }
    }
    return null;
}
```

For Case 2, if you need to override this functionality, extend the appropriate classes in your app:

### Extending and Customizing (For Case 2 Only, Otherwise ignore)

You can extend or override core functionality by creating custom implementations in your application. Here's how to override the DynamicFormBuilder:

#### 1. Create a Custom DynamicFormBuilder

Create a custom implementation in your app:

```php
<?php

namespace App\Extensions;

use Illuminate\Support\Str;
use Filament\Forms\Components\Grid;
use Quickform\Formbuilder\Filament\Components\Fields\FieldCreatorFactory;
use Quickform\Formbuilder\Filament\Resources\Schemas\DynamicFormBuilder as VendorDynamicFormBuilder;
use Quickform\Formbuilder\Models\FormTypes;

class CustomDynamicFormBuilder extends VendorDynamicFormBuilder
{
    public static function buildFormSchema(string $slug, string $modelClass = null, array $queryConditions = [])
    {
        $modelClass = $modelClass ?? \App\Models\YourModel::class; //Can keep your by default model
        
        $baseQuery = $modelClass::where('slug', $slug);
        
        foreach ($queryConditions as $column => $value) {
            $baseQuery->where($column, $value);
        }
        
        $formType = $baseQuery->first();

        if (!$formType) {
            return [];
        }

        $allFields = [];
        $fields = [];

        if (isset($formType['fields']) && is_array($formType['fields'])) {
            foreach ($formType['fields'] as $fieldConfig) {
                $fieldData = static::prepareFieldData($fieldConfig);
                $columnName = Str::snake($fieldData['label']);

                $field = FieldCreatorFactory::createField($fieldData, $modelClass)
                    ->name($columnName)
                    ->statePath($columnName)
                    ->reactive()
                    ->afterStateUpdated(function ($component, $state) {
                        $component->fill();
                        $component->state($state);
                    });

                if ($field) {
                    $fields[] = $field;
                    $allFields[$columnName] = $fieldData;
                }
            }
        }

        return [
            Grid::make()->schema($fields)
        ];
    }
    

    public static function forModel(string $slug, string $modelClass, array $additionalConditions = [])
    {
        return static::buildFormSchema($slug, $modelClass, $additionalConditions);
    }
    
}
```

#### 2. Register Your Custom Implementation

Create a service provider to bind your custom implementation:

```php
<?php
namespace App\Providers;

use App\Extensions\CustomDynamicFormBuilder;
use Illuminate\Support\ServiceProvider;
use Quickform\Formbuilder\Filament\Resources\Schemas\DynamicFormBuilder;

class FormBuilderServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(DynamicFormBuilder::class, CustomDynamicFormBuilder::class);
    }
    
    public function boot()
    {
        //
    }
}
```

#### 3. Register the Service Provider

Add your service provider to the `providers` array in `config/app.php`:

```php
'providers' => [
    // Other service providers...
    App\Providers\FormBuilderServiceProvider::class,
],
```

#### 4. Call this override schema builder as below

```php
$selectedSlug = 'your-slug';
\App\Extensions\CustomDynamicFormBuilder::forModel($selectedSlug, \App\Models\YourModel::class);
```

This approach allows you to customize how forms are built and rendered while maintaining compatibility with the core package functionality.

## Form Management

### Publishing Forms

1. Create forms in the "Quick Form Types" resource
2. Forms must be published before use in resources
3. Publishing creates a dedicated table for the form
4. Draft mode allows for field modifications
5. Data recovery is handled automatically when republishing modified forms

## Configuration

You can customize the package configuration by modifying the published config file.