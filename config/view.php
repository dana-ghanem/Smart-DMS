<?php

$compiledViewPath = env(
    'VIEW_COMPILED_PATH',
    sys_get_temp_dir().DIRECTORY_SEPARATOR.'smart-dms-framework-views'
);

return [
    'paths' => [
        resource_path('views'),
    ],

    'compiled' => $compiledViewPath,
];
