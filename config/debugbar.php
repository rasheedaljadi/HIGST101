<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Debugbar Settings
     |--------------------------------------------------------------------------
     |
     | Debugbar is disabled by default and should never run in production.
     |
     */

    'enabled' => env('DEBUGBAR_ENABLED', false),

    'except' => [
        'telescope*',
        'horizon*',
    ],

    'storage' => [
        'enabled' => false,
        'open' => env('DEBUGBAR_OPEN_STORAGE', false),
        'driver' => 'file',
        'path' => storage_path('debugbar'),
        'connection' => null,
        'provider' => '',
        'hostname' => '127.0.0.1',
        'port' => 2304,
    ],

    'editor' => env('DEBUGBAR_EDITOR', 'phpstorm'),

    'remote_sites_path' => env('DEBUGBAR_REMOTE_SITES_PATH', null),
    'local_sites_path' => env('DEBUGBAR_LOCAL_SITES_PATH', null),

    'include_vendors' => false,

    'capture_ajax' => false,
    'add_ajax_timing' => false,
    'ajax_handler_auto_show' => false,

    'error_handler' => false,

    'clockwork' => false,

    'collectors' => [
        'phpinfo' => false,
        'messages' => false,
        'time' => false,
        'memory' => false,
        'exceptions' => false,
        'log' => false,
        'db' => false,
        'views' => false,
        'route' => false,
        'auth' => false,
        'gate' => false,
        'session' => false,
        'symfony_mailer' => false,
        'mail' => false,
        'laravel' => false,
        'events' => false,
        'default_request' => false,
        'logs' => false,
        'files' => false,
        'config' => false,
        'cache' => false,
        'models' => false,
        'livewire' => false,
        'jobs' => false,
    ],

    'options' => [
        'time' => [
            'memory_usage' => false,
        ],
        'messages' => [
            'trace' => false,
        ],
        'memory' => [
            'reset_peak' => false,
            'with_baseline' => false,
            'precision' => 0,
        ],
        'auth' => [
            'show_name' => false,
            'show_guards' => false,
        ],
        'db' => [
            'with_params' => false,
            'backtrace' => false,
            'backtrace_exclude_paths' => [],
            'timeline' => false,
            'duration_background' => false,
            'explain' => [
                'enabled' => false,
                'types' => ['SELECT'],
            ],
            'hints' => false,
            'show_copy' => false,
            'slow_threshold' => false,
            'memory_usage' => false,
            'soft_limit' => 100,
            'hard_limit' => 500,
        ],
        'mail' => [
            'timeline' => false,
            'show_body' => false,
        ],
        'views' => [
            'timeline' => false,
            'data' => false,
            'group' => 50,
            'exclude_paths' => [],
        ],
        'route' => [
            'label' => false,
        ],
        'logs' => [
            'file' => null,
        ],
        'cache' => [
            'values' => false,
        ],
    ],

    'inject' => false,

    'route_prefix' => '_debugbar',

    'route_middleware' => [],

    'route_domain' => null,

    'theme' => env('DEBUGBAR_THEME', 'auto'),

    'debug_backtrace_limit' => 50,
];
