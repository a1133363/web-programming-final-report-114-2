-- NOCTURNE 暗標局
-- MySQL 8.0+ / utf8mb4 / InnoDB

CREATE DATABASE IF NOT EXISTS nocturne_auction
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE nocturne_auction;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW IF EXISTS v_monthly_platform_report;
DROP TABLE IF EXISTS admin_logs;
DROP TABLE IF EXISTS wanted_list;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS disputes;
DROP TABLE IF EXISTS deliveries;
DROP TABLE IF EXISTS wallet_transactions;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS wallets;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS watchlists;
DROP TABLE IF EXISTS proxy_bids;
DROP TABLE IF EXISTS bids;
DROP TABLE IF EXISTS auction_images;
DROP TABLE IF EXISTS auctions;
DROP TABLE IF EXISTS prohibited_keywords;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS system_settings;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS categories;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(40) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    avatar_path VARCHAR(255) NULL,
    credit_score TINYINT UNSIGNED NOT NULL DEFAULT 80,
    status ENUM('pending', 'active', 'suspended', 'banned') NOT NULL DEFAULT 'active',
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_username (username),
    KEY idx_users_status_credit (status, credit_score),
    CONSTRAINT chk_users_credit CHECK (credit_score BETWEEN 0 AND 100)
) ENGINE=InnoDB;

CREATE TABLE wallets (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    balance DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 500000.00,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_wallets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_wallets_balance CHECK (balance >= 0)
) ENGINE=InnoDB;

CREATE TABLE roles (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(30) NOT NULL,
    display_name VARCHAR(40) NOT NULL,
    description VARCHAR(255) NULL,
    UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB;

CREATE TABLE user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id TINYINT UNSIGNED NOT NULL,
    granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE categories (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    code CHAR(3) NOT NULL,
    description VARCHAR(255) NULL,
    risk_default ENUM('low', 'suspicious', 'dangerous', 'prohibited') NOT NULL DEFAULT 'suspicious',
    sort_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_categories_name (name),
    UNIQUE KEY uq_categories_code (code)
) ENGINE=InnoDB;

CREATE TABLE auctions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id BIGINT UNSIGNED NOT NULL,
    category_id SMALLINT UNSIGNED NOT NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    lot_no VARCHAR(24) NOT NULL,
    title VARCHAR(120) NOT NULL,
    slug VARCHAR(160) NOT NULL,
    description TEXT NOT NULL,
    provenance TEXT NULL,
    starting_price DECIMAL(12,2) UNSIGNED NOT NULL,
    current_price DECIMAL(12,2) UNSIGNED NOT NULL,
    reserve_price DECIMAL(12,2) UNSIGNED NULL,
    min_increment DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 100.00,
    risk_level ENUM('low', 'suspicious', 'dangerous', 'prohibited') NOT NULL DEFAULT 'suspicious',
    ai_risk_suggestion JSON NULL,
    status ENUM('draft', 'pending_review', 'approved', 'active', 'ended', 'rejected', 'cancelled', 'unsold') NOT NULL DEFAULT 'draft',
    featured BOOLEAN NOT NULL DEFAULT FALSE,
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    reviewed_at DATETIME NULL,
    rejection_reason VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_auctions_lot_no (lot_no),
    UNIQUE KEY uq_auctions_slug (slug),
    KEY idx_auctions_browse (status, end_at, risk_level),
    KEY idx_auctions_category_price (category_id, current_price),
    KEY idx_auctions_seller (seller_id, status),
    FULLTEXT KEY ftx_auctions_search (title, description, provenance),
    CONSTRAINT fk_auctions_seller FOREIGN KEY (seller_id) REFERENCES users(id),
    CONSTRAINT fk_auctions_category FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT fk_auctions_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_auctions_times CHECK (end_at > start_at),
    CONSTRAINT chk_auctions_prices CHECK (starting_price > 0 AND current_price >= starting_price AND min_increment > 0)
) ENGINE=InnoDB;

CREATE TABLE auction_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    auction_id BIGINT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(180) NULL,
    is_cover BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_auction_images_order (auction_id, is_cover, sort_order),
    CONSTRAINT fk_auction_images_auction FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE bids (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    auction_id BIGINT UNSIGNED NOT NULL,
    buyer_id BIGINT UNSIGNED NOT NULL,
    bid_amount DECIMAL(12,2) UNSIGNED NOT NULL,
    is_auto BOOLEAN NOT NULL DEFAULT FALSE,
    ip_address VARBINARY(16) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_bids_auction_amount (auction_id, bid_amount DESC, created_at),
    KEY idx_bids_buyer_created (buyer_id, created_at DESC),
    CONSTRAINT fk_bids_auction FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
    CONSTRAINT fk_bids_buyer FOREIGN KEY (buyer_id) REFERENCES users(id),
    CONSTRAINT chk_bids_amount CHECK (bid_amount > 0)
) ENGINE=InnoDB;

CREATE TABLE proxy_bids (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    auction_id BIGINT UNSIGNED NOT NULL,
    buyer_id BIGINT UNSIGNED NOT NULL,
    max_amount DECIMAL(12,2) UNSIGNED NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_proxy_auction_buyer (auction_id, buyer_id),
    KEY idx_proxy_rank (auction_id, is_active, max_amount DESC),
    CONSTRAINT fk_proxy_auction FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
    CONSTRAINT fk_proxy_buyer FOREIGN KEY (buyer_id) REFERENCES users(id),
    CONSTRAINT chk_proxy_amount CHECK (max_amount > 0)
) ENGINE=InnoDB;

CREATE TABLE watchlists (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    auction_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_watch_user_auction (user_id, auction_id),
    KEY idx_watch_auction (auction_id),
    CONSTRAINT fk_watch_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_watch_auction FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_no VARCHAR(32) NOT NULL,
    auction_id BIGINT UNSIGNED NOT NULL,
    buyer_id BIGINT UNSIGNED NOT NULL,
    seller_id BIGINT UNSIGNED NOT NULL,
    final_price DECIMAL(12,2) UNSIGNED NOT NULL,
    platform_fee DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('pending_payment', 'pending_delivery', 'completed', 'disputed', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending_payment',
    payment_due_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_orders_order_no (order_no),
    UNIQUE KEY uq_orders_auction (auction_id),
    KEY idx_orders_buyer_status (buyer_id, status),
    KEY idx_orders_seller_status (seller_id, status),
    KEY idx_orders_created_status (created_at, status),
    CONSTRAINT fk_orders_auction FOREIGN KEY (auction_id) REFERENCES auctions(id),
    CONSTRAINT fk_orders_buyer FOREIGN KEY (buyer_id) REFERENCES users(id),
    CONSTRAINT fk_orders_seller FOREIGN KEY (seller_id) REFERENCES users(id),
    CONSTRAINT chk_orders_price CHECK (final_price > 0)
) ENGINE=InnoDB;

CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    transaction_ref VARCHAR(80) NULL,
    method ENUM('bank_transfer', 'virtual_credit', 'escrow') NOT NULL,
    amount DECIMAL(12,2) UNSIGNED NOT NULL,
    status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payments_order (order_id),
    UNIQUE KEY uq_payments_ref (transaction_ref),
    KEY idx_payments_status_paid (status, paid_at),
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE wallet_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NULL,
    payment_id BIGINT UNSIGNED NULL,
    type ENUM('deposit', 'payment', 'refund', 'payout') NOT NULL,
    amount DECIMAL(12,2) UNSIGNED NOT NULL,
    balance_after DECIMAL(12,2) UNSIGNED NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_wallet_transactions_user_created (user_id, created_at DESC),
    KEY idx_wallet_transactions_order (order_id, type),
    CONSTRAINT fk_wallet_transactions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_wallet_transactions_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    CONSTRAINT fk_wallet_transactions_payment FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,
    CONSTRAINT chk_wallet_transactions_amount CHECK (amount > 0)
) ENGINE=InnoDB;

CREATE TABLE deliveries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    delivery_status ENUM('pending', 'prepared', 'in_transit', 'delivered', 'failed') NOT NULL DEFAULT 'pending',
    tracking_code VARCHAR(100) NULL,
    delivery_note TEXT NULL,
    shipped_at DATETIME NULL,
    delivered_at DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_deliveries_order (order_id),
    KEY idx_deliveries_status (delivery_status),
    CONSTRAINT fk_deliveries_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE disputes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    admin_id BIGINT UNSIGNED NULL,
    reason VARCHAR(500) NOT NULL,
    evidence JSON NULL,
    status ENUM('open', 'investigating', 'resolved_buyer', 'resolved_seller', 'dismissed') NOT NULL DEFAULT 'open',
    resolution TEXT NULL,
    resolved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_disputes_status_created (status, created_at),
    KEY idx_disputes_order (order_id),
    CONSTRAINT fk_disputes_order FOREIGN KEY (order_id) REFERENCES orders(id),
    CONSTRAINT fk_disputes_creator FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_disputes_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    reviewee_id BIGINT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reviews_order_reviewer (order_id, reviewer_id),
    KEY idx_reviews_reviewee_rating (reviewee_id, rating),
    CONSTRAINT fk_reviews_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id),
    CONSTRAINT fk_reviews_reviewee FOREIGN KEY (reviewee_id) REFERENCES users(id),
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT chk_reviews_not_self CHECK (reviewer_id <> reviewee_id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('auction_ending', 'outbid', 'won', 'payment_due', 'delivery', 'review', 'dispute', 'system') NOT NULL,
    title VARCHAR(120) NOT NULL,
    message VARCHAR(1000) NOT NULL,
    action_url VARCHAR(255) NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    sent_at DATETIME NULL,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notifications_user_read (user_id, is_read, created_at DESC),
    KEY idx_notifications_unsent (sent_at, created_at),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE wanted_list (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(500) NOT NULL,
    evidence JSON NULL,
    level ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    status ENUM('active', 'appealed', 'removed') NOT NULL DEFAULT 'active',
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_wanted_status_level (status, level, created_at),
    CONSTRAINT fk_wanted_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_wanted_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE system_settings (
    setting_key VARCHAR(80) PRIMARY KEY,
    setting_value JSON NOT NULL,
    description VARCHAR(255) NULL,
    updated_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_settings_admin FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE announcements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    author_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(120) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_announcements_author FOREIGN KEY (author_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE prohibited_keywords (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(80) NOT NULL,
    severity ENUM('review', 'dangerous', 'prohibited') NOT NULL DEFAULT 'review',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_keywords_keyword (keyword)
) ENGINE=InnoDB;

CREATE TABLE admin_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(80) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id BIGINT UNSIGNED NULL,
    details JSON NULL,
    ip_address VARBINARY(16) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_admin_logs_admin_created (admin_id, created_at DESC),
    KEY idx_admin_logs_target (target_type, target_id, created_at DESC),
    CONSTRAINT fk_admin_logs_admin FOREIGN KEY (admin_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE VIEW v_monthly_platform_report AS
SELECT
    DATE_FORMAT(o.created_at, '%Y-%m') AS report_month,
    COUNT(*) AS order_count,
    SUM(o.final_price) AS gross_volume,
    SUM(o.platform_fee) AS platform_revenue,
    SUM(o.status = 'completed') AS completed_count,
    SUM(o.status = 'disputed') AS disputed_count
FROM orders o
GROUP BY DATE_FORMAT(o.created_at, '%Y-%m');

SET FOREIGN_KEY_CHECKS = 1;
