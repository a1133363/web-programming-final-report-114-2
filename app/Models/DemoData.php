<?php

declare(strict_types=1);

namespace App\Models;

final class DemoData
{
    public static function auctions(): array
    {
        return [
            [
                'id' => 1,
                'lot_no' => 'N-013',
                'title' => '第十三時辰黑曜計時器',
                'slug' => 'thirteenth-hour-chronometer',
                'category_name' => '古代遺物',
                'seller_name' => '灰鴉收藏室',
                'seller_id' => 2,
                'seller_credit' => 92,
                'description' => '只在不存在的第十三個時辰運轉。紫晶擒縱每七夜偏移一次，據說能記下持有者沒有做出的選擇。所有異常皆為虛構世界觀設定。',
                'current_price' => 128000,
                'starting_price' => 68000,
                'min_increment' => 5000,
                'bid_count' => 18,
                'risk_level' => 'dangerous',
                'status' => 'active',
                'end_at' => date('Y-m-d H:i:s', time() + 8437),
                'image_path' => 'assets/images/item-chronometer.webp',
            ],
            [
                'id' => 2,
                'lot_no' => 'N-027',
                'title' => '月面製圖師的折疊星圖',
                'slug' => 'cartographers-moon-map',
                'category_name' => '禁忌文獻',
                'seller_name' => '無窗書房',
                'seller_id' => 3,
                'seller_credit' => 88,
                'description' => '一張記錄月面不存在海岸線的機械星圖。觀測環會隨瀏覽者的位置自行校正，紙層之間藏有微型穿孔星座。',
                'current_price' => 76000,
                'starting_price' => 42000,
                'min_increment' => 3000,
                'bid_count' => 11,
                'risk_level' => 'suspicious',
                'status' => 'active',
                'end_at' => date('Y-m-d H:i:s', time() + 22419),
                'image_path' => 'assets/images/item-moon-map.webp',
            ],
            [
                'id' => 3,
                'lot_no' => 'N-041',
                'title' => '靜默衛星共振線圈',
                'slug' => 'silent-satellite-coil',
                'category_name' => '異星科技',
                'seller_name' => '遠日點工程社',
                'seller_id' => 4,
                'seller_credit' => 96,
                'description' => '自失聯觀測站回收的陶瓷共振元件。未連接電源時仍以固定週期產生微弱琥珀光，功能未知。',
                'current_price' => 214000,
                'starting_price' => 150000,
                'min_increment' => 10000,
                'bid_count' => 7,
                'risk_level' => 'dangerous',
                'status' => 'active',
                'end_at' => date('Y-m-d H:i:s', time() + 49823),
                'image_path' => 'assets/images/item-resonance-coil.webp',
            ],
            [
                'id' => 4,
                'lot_no' => 'N-056',
                'title' => '低語檔案庫：零號捲',
                'slug' => 'whisper-archive-zero',
                'category_name' => '失落情報',
                'seller_name' => '無窗書房',
                'seller_id' => 3,
                'seller_credit' => 88,
                'description' => '煙晶圓筒內封存無法以現有設備讀取的金屬記憶帶，靠近時會聽見與環境無關的翻頁聲。',
                'current_price' => 59000,
                'starting_price' => 30000,
                'min_increment' => 2000,
                'bid_count' => 23,
                'risk_level' => 'suspicious',
                'status' => 'active',
                'end_at' => date('Y-m-d H:i:s', time() + 11783),
                'image_path' => 'assets/images/item-whisper-archive.webp',
            ],
        ];
    }

    public static function bids(int $auctionId): array
    {
        $amounts = $auctionId === 1 ? [128000, 123000, 118000, 108000] : [76000, 73000, 70000];
        $names = ['霧港來客', '買家 #7F2A', '北塔代理人', '匿名席位'];
        return array_map(static fn (int $amount, int $index): array => [
            'username' => $names[$index] ?? '匿名席位',
            'bid_amount' => $amount,
            'is_auto' => $index % 2 === 0,
            'created_at' => date('Y-m-d H:i:s', time() - ($index + 1) * 340),
        ], $amounts, array_keys($amounts));
    }

    public static function categories(): array
    {
        return [
            ['id' => 1, 'name' => '古代遺物', 'count' => 38, 'code' => 'REL'],
            ['id' => 2, 'name' => '異星科技', 'count' => 24, 'code' => 'XEN'],
            ['id' => 3, 'name' => '失落情報', 'count' => 51, 'code' => 'INT'],
            ['id' => 4, 'name' => '禁忌文獻', 'count' => 19, 'code' => 'ARC'],
        ];
    }

    public static function wanted(): array
    {
        return [
            ['username' => '空箱商人', 'reason' => '三次交付不存在的容器', 'level' => 'critical', 'reported_at' => '2026-06-18'],
            ['username' => '紅線買家', 'reason' => '異常取消 11 次得標', 'level' => 'high', 'reported_at' => '2026-06-14'],
            ['username' => '南井鑑定所', 'reason' => '重複提交偽造鑑定紀錄', 'level' => 'medium', 'reported_at' => '2026-06-03'],
        ];
    }
}
