USE nocturne_auction;
SET NAMES utf8mb4;

INSERT INTO roles (id, name, display_name, description) VALUES
    (1, 'user', '使用者', '瀏覽、收藏、出價、付款、刊登拍賣品與管理交易'),
    (2, 'admin', '拍賣管理員', '商品審核、會員管理、爭議裁決與報表');

-- 所有示範帳號密碼：demo1234
INSERT INTO users (id, username, email, password_hash, credit_score, status, email_verified_at) VALUES
    (1, '夜班監察員', 'admin@example.com', '$2y$10$OEQTe/V.8LtsJmo/hWJjMOtxJ6OaJNpqe3OfggecyIRw0Pxo5/JOi', 100, 'active', NOW()),
    (2, '灰鴉收藏室', 'seller@example.com', '$2y$10$OEQTe/V.8LtsJmo/hWJjMOtxJ6OaJNpqe3OfggecyIRw0Pxo5/JOi', 92, 'active', NOW()),
    (3, '無窗書房', 'archive@example.com', '$2y$10$OEQTe/V.8LtsJmo/hWJjMOtxJ6OaJNpqe3OfggecyIRw0Pxo5/JOi', 88, 'active', NOW()),
    (4, '霧港來客', 'buyer@example.com', '$2y$10$OEQTe/V.8LtsJmo/hWJjMOtxJ6OaJNpqe3OfggecyIRw0Pxo5/JOi', 86, 'active', NOW()),
    (5, '北塔代理人', 'tower@example.com', '$2y$10$OEQTe/V.8LtsJmo/hWJjMOtxJ6OaJNpqe3OfggecyIRw0Pxo5/JOi', 79, 'active', NOW()),
    (6, '空箱商人', 'emptybox@example.com', '$2y$10$OEQTe/V.8LtsJmo/hWJjMOtxJ6OaJNpqe3OfggecyIRw0Pxo5/JOi', 24, 'suspended', NOW());

INSERT INTO wallets (user_id, balance) VALUES
    (1, 1000000.00),
    (2, 500000.00),
    (3, 500000.00),
    (4, 557000.00),
    (5, 500000.00),
    (6, 500000.00);

INSERT INTO user_roles (user_id, role_id) VALUES
    (1, 2), (2, 1), (3, 1), (4, 1), (5, 1), (6, 1);

INSERT INTO categories (id, name, code, description, risk_default, sort_order) VALUES
    (1, '古代遺物', 'REL', '來源跨越已失落文明的器物', 'suspicious', 10),
    (2, '異星科技', 'XEN', '非地球工藝或功能未知的科技零件', 'dangerous', 20),
    (3, '失落情報', 'INT', '封存檔案、密碼與不可驗證的情報', 'suspicious', 30),
    (4, '禁忌文獻', 'ARC', '記錄異常知識的手稿與地圖', 'dangerous', 40),
    (5, '奇異收藏', 'CUR', '低危險性的世界觀收藏物', 'low', 50);

INSERT INTO auctions
(id, seller_id, category_id, reviewed_by, lot_no, title, slug, description, starting_price, current_price, reserve_price, min_increment, risk_level, ai_risk_suggestion, status, featured, start_at, end_at, reviewed_at) VALUES
    (1, 2, 1, 1, 'N-013', '第十三時辰黑曜計時器', 'thirteenth-hour-chronometer', '只在不存在的第十三個時辰運轉。紫晶擒縱每七夜偏移一次，據說能記下持有者沒有做出的選擇。所有異常皆為虛構世界觀設定。', 68000, 128000, 100000, 5000, 'dangerous', JSON_OBJECT('level','dangerous','score',58,'matches',JSON_ARRAY('異常')), 'active', TRUE, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 3 HOUR), NOW()),
    (2, 3, 4, 1, 'N-027', '月面製圖師的折疊星圖', 'cartographers-moon-map', '一張記錄月面不存在海岸線的機械星圖。觀測環會隨瀏覽者的位置自行校正，紙層之間藏有微型穿孔星座。', 42000, 76000, NULL, 3000, 'suspicious', JSON_OBJECT('level','suspicious','score',24), 'active', TRUE, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 7 HOUR), NOW()),
    (3, 2, 2, 1, 'N-041', '靜默衛星共振線圈', 'silent-satellite-coil', '自失聯觀測站回收的陶瓷共振元件。未連接電源時仍以固定週期產生微弱琥珀光，功能未知。', 150000, 214000, 200000, 10000, 'dangerous', JSON_OBJECT('level','dangerous','score',48,'matches',JSON_ARRAY('未知')), 'active', TRUE, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL 14 HOUR), NOW()),
    (4, 3, 3, 1, 'N-056', '低語檔案庫：零號捲', 'whisper-archive-zero', '煙晶圓筒內封存無法以現有設備讀取的金屬記憶帶，靠近時會聽見與環境無關的翻頁聲。', 30000, 59000, NULL, 2000, 'suspicious', JSON_OBJECT('level','suspicious','score',31,'matches',JSON_ARRAY('低語','記憶')), 'active', FALSE, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 4 HOUR), NOW()),
    (5, 2, 5, 1, 'N-008', '北境玻璃種子', 'northern-glass-seed', '在低溫環境發出微光的玻璃狀收藏物，未觀測到主動生長現象。', 25000, 43000, NULL, 2000, 'low', JSON_OBJECT('level','low','score',4), 'ended', FALSE, DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY)),
    (6, 2, 1, NULL, 'P-062', '逆向燃燒的銀色燭台', 'reverse-burning-silver-candlestick', '燭焰會將周圍光線向內收束，來源與使用痕跡尚待鑑定。', 36000, 36000, 60000, 2500, 'suspicious', JSON_OBJECT('level','dangerous','score',44,'matches',JSON_ARRAY('來源不明','異常')), 'pending_review', FALSE, DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 5 DAY), NULL);

INSERT INTO auction_images (auction_id, file_path, alt_text, is_cover, sort_order) VALUES
    (1, 'assets/images/item-chronometer.webp', '黑曜計時器拍賣品影像', TRUE, 0),
    (2, 'assets/images/item-moon-map.webp', '月面折疊星圖拍賣品影像', TRUE, 0),
    (3, 'assets/images/item-resonance-coil.webp', '異星共振線圈拍賣品影像', TRUE, 0),
    (4, 'assets/images/item-whisper-archive.webp', '低語檔案庫拍賣品影像', TRUE, 0),
    (5, 'assets/images/item-moon-map.webp', '北境玻璃種子示意影像', TRUE, 0);

INSERT INTO bids (auction_id, buyer_id, bid_amount, is_auto, created_at) VALUES
    (1, 4, 108000, FALSE, DATE_SUB(NOW(), INTERVAL 42 MINUTE)),
    (1, 5, 118000, TRUE, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
    (1, 4, 123000, FALSE, DATE_SUB(NOW(), INTERVAL 18 MINUTE)),
    (1, 5, 128000, TRUE, DATE_SUB(NOW(), INTERVAL 8 MINUTE)),
    (2, 4, 70000, FALSE, DATE_SUB(NOW(), INTERVAL 70 MINUTE)),
    (2, 5, 73000, FALSE, DATE_SUB(NOW(), INTERVAL 53 MINUTE)),
    (2, 4, 76000, TRUE, DATE_SUB(NOW(), INTERVAL 40 MINUTE)),
    (3, 5, 214000, FALSE, DATE_SUB(NOW(), INTERVAL 20 MINUTE)),
    (4, 4, 59000, FALSE, DATE_SUB(NOW(), INTERVAL 12 MINUTE)),
    (5, 4, 43000, FALSE, DATE_SUB(NOW(), INTERVAL 2 DAY));

INSERT INTO proxy_bids (auction_id, buyer_id, max_amount, is_active) VALUES
    (1, 5, 180000, TRUE),
    (2, 4, 95000, TRUE),
    (3, 5, 280000, TRUE);

INSERT INTO watchlists (user_id, auction_id) VALUES (4,1), (4,2), (4,3), (5,1), (5,4);

INSERT INTO orders (id, order_no, auction_id, buyer_id, seller_id, final_price, platform_fee, status, payment_due_at, created_at) VALUES
    (1, 'NO-20260618-0001', 5, 4, 2, 43000, 2150, 'pending_delivery', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY));
INSERT INTO payments (order_id, transaction_ref, method, amount, status, paid_at) VALUES
    (1, 'ESC-20260618-A91', 'escrow', 43000, 'paid', DATE_SUB(NOW(), INTERVAL 1 DAY));
INSERT INTO wallet_transactions (user_id, order_id, payment_id, type, amount, balance_after, description, created_at)
SELECT 4, 1, p.id, 'payment', 43000, 557000, '訂單 NO-20260618-0001 模擬付款', DATE_SUB(NOW(), INTERVAL 1 DAY)
FROM payments p WHERE p.order_id = 1;
INSERT INTO deliveries (order_id, delivery_status, tracking_code, shipped_at) VALUES
    (1, 'in_transit', 'NCT-X7F2A9', DATE_SUB(NOW(), INTERVAL 8 HOUR));

INSERT INTO notifications (user_id, type, title, message, action_url, is_read, sent_at) VALUES
    (4, 'delivery', '交付狀態已更新', '北境玻璃種子已由賣家交付，等待確認。', 'index.php?page=buyer', FALSE, NOW()),
    (4, 'auction_ending', '拍賣即將截標', '第十三時辰黑曜計時器將於三小時內截標。', 'index.php?page=auction&id=1', FALSE, NOW()),
    (2, 'system', '商品審核完成', '靜默衛星共振線圈已核准上架。', 'index.php?page=seller', TRUE, NOW());

INSERT INTO wanted_list (user_id, created_by, reason, evidence, level, status) VALUES
    (6, 1, '三次交付不存在的容器', JSON_OBJECT('failed_orders', 3, 'case', 'DS-019'), 'critical', 'active');

INSERT INTO prohibited_keywords (keyword, severity) VALUES
    ('真實毒品', 'prohibited'), ('真實武器', 'prohibited'), ('個人資料', 'dangerous'), ('來源不明', 'review'), ('精神干涉', 'dangerous');

INSERT INTO system_settings (setting_key, setting_value, description, updated_by) VALUES
    ('platform_fee_rate', JSON_OBJECT('percent', 5), '成交手續費百分比', 1),
    ('auction_extension', JSON_OBJECT('seconds', 300, 'trigger_before_seconds', 60), '最後一分鐘出價延長規則', 1),
    ('mail_from', JSON_OBJECT('name', 'NOCTURNE 暗標局', 'address', 'noreply@example.test'), '通知寄件人', 1);

INSERT INTO announcements (author_id, title, body, status, published_at) VALUES
    (1, '第六夜場風險協議更新', '危險級商品將增加交付前二次確認。', 'published', NOW());

INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, created_at) VALUES
    (1, 'auction.approved', 'auction', 1, JSON_OBJECT('risk_level','dangerous'), DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (1, 'auction.approved', 'auction', 3, JSON_OBJECT('risk_level','dangerous'), DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (1, 'user.wanted_added', 'user', 6, JSON_OBJECT('level','critical'), DATE_SUB(NOW(), INTERVAL 2 DAY));
