<?php

declare(strict_types=1);

namespace App\Services;

final class RiskService
{
    public function suggest(string $title, string $description, int $sellerCredit = 80): array
    {
        $text = mb_strtolower($title . ' ' . $description);
        $dangerous = ['詛咒', '感染', '失控', '輻射', '自我複製', '精神干涉', '禁止'];
        $suspicious = ['未知', '低語', '異常', '未鑑定', '來源不明', '記憶'];
        $score = max(0, 100 - $sellerCredit);
        $matches = [];
        foreach ($dangerous as $keyword) {
            if (str_contains($text, $keyword)) {
                $score += 22;
                $matches[] = $keyword;
            }
        }
        foreach ($suspicious as $keyword) {
            if (str_contains($text, $keyword)) {
                $score += 10;
                $matches[] = $keyword;
            }
        }
        $level = $score >= 70 ? 'prohibited' : ($score >= 42 ? 'dangerous' : ($score >= 18 ? 'suspicious' : 'low'));
        return ['level' => $level, 'score' => min(100, $score), 'matches' => array_unique($matches)];
    }
}
