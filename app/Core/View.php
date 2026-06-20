<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $template, array $data = [], string $layout = 'layouts/main'): void
    {
        $root = dirname(__DIR__, 2) . '/views/';
        $templateFile = $root . $template . '.php';
        $layoutFile = $root . $layout . '.php';

        if (!is_file($templateFile) || !is_file($layoutFile)) {
            throw new RuntimeException('找不到指定的頁面樣板。');
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $templateFile;
        $content = (string) ob_get_clean();
        require $layoutFile;
    }
}
