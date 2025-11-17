<?php

return [
    'master_password' => env('COMMAND_MASTER_PASSWORD', env('ADMIN_TOOL_PASSWORD')),

    'backup_path' => env('COMMAND_BACKUP_PATH', storage_path('command-backups')),
    'mysql_dump_binary' => env('COMMAND_MYSQL_DUMP', 'mysqldump'),

    'file_roots' => [
        [
            'key' => 'config',
            'label' => 'Config',
            'path' => base_path('config'),
        ],
        [
            'key' => 'routes',
            'label' => 'Routes',
            'path' => base_path('routes'),
        ],
        [
            'key' => 'resources',
            'label' => 'Resources',
            'path' => base_path('resources'),
        ],
        [
            'key' => 'app',
            'label' => 'App',
            'path' => base_path('app'),
        ],
        [
            'key' => 'env',
            'label' => '.env',
            'path' => base_path('.env'),
            'type' => 'file',
        ],
    ],

    'git' => [
        'default_branch' => env('COMMAND_GIT_BRANCH', 'main'),
    ],

    'routes' => [
        'middleware' => ['web', 'auth', 'verified'],
        'prefix' => 'command',
        'name' => 'command.',
    ],
];
