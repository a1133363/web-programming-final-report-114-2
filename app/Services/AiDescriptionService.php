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

        $tokens = preg_split('/[\s,，、;；]+/u', $clean) ?: [];
        $tokens = array_values(array_unique(array_filter(array_map(
            static fn (string $token): string => mb_substr(trim($token), 0, 16),
            $tokens
        ))));
        $subject = $tokens ? implode('、', array_slice($tokens, 0, 4)) : mb_substr($clean, 0, 40);
        $seed = abs(crc32($clean));
        $pick = static fn (array $items, int $offset = 0): string => $items[($seed + $offset) % count($items)];

        return $pick([
            '委託資料顯示，' . $subject . '曾在一次無紀錄的夜間交割後留下編號。',
            '賣方提供的殘缺筆記將' . $subject . '描述為可被攜帶的異常收藏。',
            '暗標局初步封存' . $subject . '時，外觀與重量紀錄出現了兩組互斥數值。',
        ]) . $pick([
            '表面痕跡不像自然磨損，更接近長期被低溫、鹽霧或靜電共同保存。',
            '靠近光源時，細節會短暫浮出，離開視線後又回到普通器物的狀態。',
            '隨附標籤缺少來源簽章，只保留材質、用途與交付警語的片段。',
        ], 1) . $pick([
            '建議以密封容器交付，並在驗收時拍照留存狀態差異。',
            '功能與來源仍待買方自行判讀，平台僅保證交付內容與描述一致。',
            '所有異常效應均屬虛構世界觀設定，實際交易以照片、尺寸與配件清單為準。',
        ], 2);
    }
}
