<?php

namespace Quickform\Formbuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Quickform\Formbuilder\Jobs\ProcessFormDataInsertion;
use Quickform\Formbuilder\Enum\FieldType;
use Illuminate\Support\Facades\Config;

class FormTypes extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    protected $casts = [
        'fields' => 'array',
        'layouts' => 'array',
    ];

    public function draft()
    {
        $tableName = Str::slug($this->slug, '_');
        Schema::dropIfExists($tableName);
        $this->status = 'draft';
        $this->save();
    }

    public function getResourceMapping()
    {
        $mappingToDynamic = Config::get('quickform.resource_mappings', []);
        return $mappingToDynamic[$this->slug] ?? null;
    }

    public function publish()
    {
        $originalSlug = $this->slug;
        $tableName = Str::slug($originalSlug, '_'); 

        
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                
                $fields = FieldType::flattenFields($this->layouts);
                
                $resourceMapping = $this->getResourceMapping();
                if ($resourceMapping) {
                    $table->unsignedBigInteger($resourceMapping['foreign_key_id']);
                    $table->foreign($resourceMapping['foreign_key_id'])
                        ->references('id')
                        ->on($resourceMapping['table_name'])
                        ->onDelete('cascade')
                        ->onUpdate('cascade');
                }
                
                foreach ($fields as $field) {
                    $fieldType = new FieldType($field['type']); 
                    $fieldType->addToTable($table, $field);
                }
                
                $table->timestamps();
            });
        }

        ProcessFormDataInsertion::dispatch($tableName, $originalSlug);
        
        $this->status = 'published';
        $this->save();
        \Log::info("Form published and data insertion job dispatched for table: {$tableName}");
    }
}