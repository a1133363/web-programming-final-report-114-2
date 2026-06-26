<?php

declare(strict_types=1);

$basePath = realpath(dirname(__DIR__)) ?: dirname(__DIR__);

return [
    'name' => 'NOCTURNE 暗標局',
    'tagline' => '只競標不存在的危險。',
    'timezone' => 'Asia/Taipei',
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOL),
    'url' => rtrim(getenv('APP_URL') ?: '', '/'),
    'upload_dir' => $basePath . '/storage/uploads',
    'max_upload_size' => 5 * 1024 * 1024,
    'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
];
