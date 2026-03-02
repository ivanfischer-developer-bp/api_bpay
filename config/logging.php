<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'alfabeta' => [
            'driver' => 'single',
            'path' => storage_path('logs/alfabeta.log'),
            'ignore_exceptions' => false,
            'level' => 'debug'
        ],

        'external' => [
            'driver' => 'single',
            'path' => storage_path('logs/external.log'),
            'ignore_exceptions' => false,
            'level' => 'debug'
        ],

        'validaciones' => [
            'driver' => 'single',
            'path' => storage_path('logs/validaciones.log'),
            'ignore_exceptions' => false,
            'level' => 'debug'
        ],

        'siaf' => [
            'driver' => 'single',
            'path' => storage_path('logs/siaf.log'),
            'ignore_exceptions' => false,
            'level' => 'debug'
        ],

        'tach' => [
            'driver' => 'single',
            'path' => storage_path('logs/tach.log'),
            'ignore_exceptions' => false,
            'level' => 'debug'
        ],

        'recetas' => [
            'driver' => 'single',
            'path' => storage_path('logs/recetas.log'),
            'ignore_exceptions' => false,
            'level' => 'debug'
        ],

        'cambios' => [
            'driver' => 'single',
            'path' => storage_path('logs/cambios.log'),
            'ignore_exceptions' => false,
            'level' => 'debug'
        ],

        'pusher' => [
            'driver' => 'single',
            'path' => storage_path('logs/pusher.log'),
            'ignore_exceptions' => false,
            'level' => 'debug'
        ],

        'email' => [
            'driver' => 'single',
            'path' => storage_path('logs/email.log'),
            'ignore_exceptions' => false,
            'level' => 'debug'
        ],

        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
    ],

];
