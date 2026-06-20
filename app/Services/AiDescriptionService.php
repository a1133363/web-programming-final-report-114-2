<?php

declare(strict_types=1);

namespace App\Services;

final class AiDescriptionService
{
    public function generate(string $keywords): string
    {
        $clean = trim(strip_tags($keywords));
        if ($clean === '') {
            return '';
        }
        return sprintf(
            '此件「%s」最早出現在北塔封存目錄的缺頁中。外觀雖保持穩定，靠近時仍可觀測到不符合現有物理記錄的細微變化。拍賣品僅供虛構世界觀收藏；功能、來源與風險均待暗標局進一步審核。',
            mb_substr($clean, 0, 40)
        );
    }
}
