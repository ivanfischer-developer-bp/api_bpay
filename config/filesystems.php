<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'avatares' => [
            'driver' => 'local',
            'root' => storage_path('avatares'),
            'url' => storage_path('avatares'),
            'visibility' => 'public',
        ],

        'avatars' => [
            'driver' => 'local',
            'root' => env('STORAGE_PATH_EXTERNO') . '/avatars',
            'url' => env('STORAGE_PATH_EXTERNO') . '/avatars',
            'visibility' => 'public',
        ],

        'avatars_externo' => [
            'driver' => 'local',
            'root' => env('AVATARS_PATH_EXTERNO'),
            'url' => env('AVATARS_PATH_EXTERNO'),
            'visibility' => 'public',
        ],

        'firma_medicos' => [
            'driver' => 'local',
            'root' => env('FIRMA_MEDICOS'),
            'url' => env('FIRMA_MEDICOS'),
            'visibility' => 'public',
        ],

        'manuales' => [
            'driver' => 'local',
            'root' => env('MANUALES'),
            'url' => env('MANUALES'),
            'visibility' => 'public',
        ],

        'uploads' => [
            'driver' => 'local',
            'root' => env('STORAGE_PATH').'uploads',
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],
        
        'uploads_externo' => [
            'driver' => 'local',
            'root' => env('UPLOADS_PATH_EXTERNO'),
            'url' => env('UPLOADS_PATH_EXTERNO'),
            // 'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'certificados_emails_enviados' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reportes/certificados_emails_enviados'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'coberturas_especiales_afiliado' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reportes/coberturas_especiales_afiliado'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'certificados_afiliacion' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reportes/certificados_afiliacion'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'facturacion_global' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reportes/facturacion_global'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'formularios' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reportes/formularios'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'historias_clinicas' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reportes/historias_clinicas'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'informes_sistema' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reportes/informes_sistema'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'informes_afiliados' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reportes/informes_afiliados'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'listados_turnos_medicos' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reportes/listados_turnos_medicos'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'prestaciones' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reportes/prestaciones'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'programas_especiales' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reportes/programas_especiales'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'recetas_afiliados' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reportes/recetas_afiliados'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'situacion_terapeutica' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reportes/situacion_terapeutica'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'usuarios' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reportes/sistema/usuarios/'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'roles' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reportes/sistema/roles/'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'permisos' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reportes/sistema/permisos/'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'validaciones' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reportes/validaciones'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
