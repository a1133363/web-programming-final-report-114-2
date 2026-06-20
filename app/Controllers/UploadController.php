<?php

declare(strict_types=1);

namespace App\Controllers;

final class UploadController
{
    public function show(): never
    {
        $filename = (string) ($_GET['file'] ?? '');
        if ($filename === '' || basename($filename) !== $filename || !preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $filename)) {
            http_response_code(404);
            exit;
        }
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $path = $config['upload_dir'] . '/' . $filename;
        if (!is_file($path)) {
            http_response_code(404);
            exit;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!in_array($mime, $config['allowed_mime_types'], true)) {
            http_response_code(415);
            exit;
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: public, max-age=86400, immutable');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}
