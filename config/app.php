<?php

declare(strict_types=1);

return [
    'name' => 'NOCTURNE 暗標局',
    'tagline' => '只競標不存在的危險。',
    'timezone' => 'Asia/Taipei',
    'debug' => filter_var(getenv('APP_DEBUG') ?: 'true', FILTER_VALIDATE_BOOL),
    'url' => rtrim(getenv('APP_URL') ?: '', '/'),
    'upload_dir' => dirname(__DIR__) . '/storage/uploads',
    'max_upload_size' => 5 * 1024 * 1024,
    'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
];
