<?php

namespace FilamentQuickForm\FormBuilder\Models\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use FilamentQuickForm\FormBuilder\Models\FormTypes;

trait HandleFormSavedTraits
{
    protected static function booted()
    {
        static::saved(function ($model) {
            return static::handleFormSaved($model);
        });
    }
    
    protected static function handleFormSaved($model)
    {
        return DB::transaction(function () use ($model) {
            $data = $model->getAttributes();
            $data['fields'] = is_string($data['fields'] ?? null)
                ? json_decode($data['fields'], true)
                : ($data['fields'] ?? []);
                    
            if (isset($data['form_type_id']) && $formType = FormTypes::find($data['form_type_id'])) {
                $tableName = Str::slug($formType->slug, '_');
                $contentData = array_map(fn($value) => is_array($value) ? json_encode($value) : $value, $data['fields']);
    
                $resourceMapping = $formType->getResourceMapping();
                
                $RecordsData = new FormTypes();
                $RecordsData->setTable($tableName);
                
                if ($resourceMapping) {
                    $RecordsData->updateOrCreate(
                        [$resourceMapping['foreign_key_id'] => $model->id],
                        $contentData
                    );
                } else {
                    $RecordsData->updateOrCreate(
                        ['id' => $model->id],
                        $contentData
                    );
                }
            }
        });
    }
}