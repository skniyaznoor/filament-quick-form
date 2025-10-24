<?php

return [
    'resource_mappings' => [
        'slug-1' => [  //Dynamic Table Slug (FormTypes)
            'table_name' => 'resource_table_name_1', //Resource table where you want to use
            'foreign_key_id' => 'resource_table_name_id_1' //foreign_key
        ],
        'slug-2' => [ //Dynamic Table Slug (FormTypes)
            'table_name' => 'resource_table_name_1', //Resource table where you want to use
            'foreign_key_id' => 'resource_table_name_id_2' //foreign_key
        ],
        // Can use multiple resources
    ],
    'default_color' => 'primary', //can change the color
];