<?php

namespace FilamentQuickForm\FormBuilder\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use FilamentQuickForm\FormBuilder\Models\FormTypes;
use Illuminate\Support\Facades\Schema;

class ProcessFormDataInsertion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tableName;
    protected $originalSlug;

    public function __construct($tableName, $originalSlug)
    {
        $this->tableName = $tableName;
        $this->originalSlug = $originalSlug;
    }

    public function handle()
    { 
        $formType = FormTypes::where('slug', $this->originalSlug)->first();
         
        if (!$formType) {
            Log::error("Form type not found for table: {$this->originalSlug}");
            return;
        }

        $resourceMapping = $formType->getResourceMapping();
        if (!$resourceMapping) {
            return;
        }

        $resources = DB::table($resourceMapping['table_name'])
            ->where('form_type_id', $formType->id)
            ->get();

        if ($resources->isEmpty()) {
            Log::info("No data found to transfer for table: {$this->tableName} from {$resourceMapping['table']}");
            return;
        }

        $columns = Schema::getColumnListing($this->tableName);
        $validColumns = array_diff($columns, ['id', 'created_at', 'updated_at']);

        DB::beginTransaction();

        try {
            foreach ($resources as $resource) {
                $fields = json_decode($resource->fields, true);
                
                if (!$fields) {
                    continue;
                }

                $validData = array_intersect_key($fields, array_flip($validColumns));
                
                $validData = array_map(function($value) {
                    if (is_array($value)) {
                        return json_encode($value);
                    }
                    if (is_string($value) && strpos($value, 'uploads/') === 0) {
                        return $value; 
                    }
                    return $value;
                }, $validData);

                $validData[$resourceMapping['foreign_key_id']] = $resource->id;
                $validData['created_at'] = $resource->created_at ?? now();
                $validData['updated_at'] = $resource->updated_at ?? now();
                
                if (!empty($validData)) {
                    DB::table($this->tableName)->insert($validData);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error processing resources for table {$this->originalSlug}: " . $e->getMessage());
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("ProcessFormDataInsertion job failed for table {$this->originalSlug}: " . $exception->getMessage());
    }
}