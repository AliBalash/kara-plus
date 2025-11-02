<?php

$defaultPublicPath = public_path();

$hostedPublicPath = null;
$hostCandidates = [
    base_path('../public_html'),
    base_path('..'),
];

foreach ($hostCandidates as $candidate) {
    if (!is_string($candidate) || $candidate === '') {
        continue;
    }

    $candidate = rtrim($candidate, DIRECTORY_SEPARATOR);

    if (!is_dir($candidate)) {
        continue;
    }

    $panelDirectory = $candidate . DIRECTORY_SEPARATOR . basename(base_path());

    if (
        !is_dir($panelDirectory) ||
        realpath($panelDirectory) !== base_path() ||
        !is_dir($candidate . DIRECTORY_SEPARATOR . 'assets') ||
        !is_dir($candidate . DIRECTORY_SEPARATOR . 'storage') ||
        !file_exists($candidate . DIRECTORY_SEPARATOR . 'index.php')
    ) {
        continue;
    }

    $hostedPublicPath = $candidate;

    break;
}

$publicRoot = rtrim(env('PUBLIC_PATH', $hostedPublicPath ?: $defaultPublicPath), DIRECTORY_SEPARATOR);
$publicUrl = rtrim(env('PUBLIC_URL', env('APP_URL')), '/');

$storageLinkPath = rtrim(env('PUBLIC_STORAGE_PATH', $publicRoot . '/storage'), DIRECTORY_SEPARATOR);
$storageLinkTarget = rtrim(env('PUBLIC_STORAGE_TARGET', storage_path('app/public')), DIRECTORY_SEPARATOR);
$publicDiskRoot = rtrim(env('PUBLIC_DISK_ROOT', $storageLinkTarget), DIRECTORY_SEPARATOR);
$publicDiskUrl = rtrim(env('PUBLIC_DISK_URL', $publicUrl . '/storage'), '/');

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'car_pics' => [
            'driver' => 'local',
            'root' => rtrim(env('CAR_PICS_ROOT', $publicRoot . '/assets/car-pics'), DIRECTORY_SEPARATOR),
            'url' => rtrim(env('CAR_PICS_URL', $publicUrl . '/assets/car-pics'), '/'),
            'visibility' => 'public',
        ],

        'public' => [
            'driver' => 'local',
            'root' => $publicDiskRoot,
            'url' => $publicDiskUrl,
            'visibility' => 'public',
        ],

        'myimage' => [
            'driver' => 'local',
            'root' => rtrim(env('MYIMAGE_ROOT', $storageLinkPath), DIRECTORY_SEPARATOR),
            'url' => rtrim(env('MYIMAGE_URL', $publicDiskUrl), '/'),
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
            'throw' => false,
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
        $storageLinkPath => $storageLinkTarget,
    ],

];
