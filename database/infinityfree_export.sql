-- ==========================================================
-- WARUNG KAURE - EXPORT KHUSUS INFINITYFREE / SHARED HOSTING
-- Siap di-import langsung via phpMyAdmin InfinityFree
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================================
-- WARUNG KAURE POS & MANAGEMENT SYSTEM
-- DATABASE SCHEMA: warung_kaure
-- MySQL 8.x / MariaDB 10.x compatible
-- ==========================================================





-- Disable foreign key checks for table creation
SET FOREIGN_KEY_CHECKS = 0;

-- 1. USERS TABLE
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('OWNER', 'CASHIER') NOT NULL DEFAULT 'CASHIER',
    `phone` VARCHAR(20) NULL,
    `hourly_rate` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `overtime_rate` DECIMAL(12,2) NOT NULL DEFAULT 5000.00,
    `status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    `last_login_at` DATETIME NULL,
    `last_activity_at` DATETIME NULL,
    `last_ip` VARCHAR(45) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL,
    INDEX `idx_users_username` (`username`),
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. CATEGORIES TABLE
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL,
    INDEX `idx_categories_name` (`name`),
    INDEX `idx_categories_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. PRODUCTS TABLE
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NULL,
    `sku` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `image` VARCHAR(255) NULL,
    `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `cost_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `stock_tracking_type` ENUM('DIRECT', 'RECIPE', 'NONE') NOT NULL DEFAULT 'DIRECT',
    `stock` INT NOT NULL DEFAULT 0,
    `minimum_stock` INT NOT NULL DEFAULT 5,
    `status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    INDEX `idx_products_sku` (`sku`),
    INDEX `idx_products_name` (`name`),
    INDEX `idx_products_category` (`category_id`),
    INDEX `idx_products_status` (`status`),
    INDEX `idx_products_tracking` (`stock_tracking_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. INGREDIENTS TABLE (Bahan Baku)
DROP TABLE IF EXISTS `ingredients`;
CREATE TABLE `ingredients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `unit` ENUM('gram', 'ml', 'pcs') NOT NULL DEFAULT 'gram',
    `current_stock` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `minimum_stock` DECIMAL(12,2) NOT NULL DEFAULT 100.00,
    `average_cost` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL,
    INDEX `idx_ingredients_name` (`name`),
    INDEX `idx_ingredients_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. PRODUCT RECIPES TABLE (Bill of Materials)
DROP TABLE IF EXISTS `product_recipes`;
CREATE TABLE `product_recipes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `ingredient_id` INT NOT NULL,
    `quantity` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_product_ingredient` (`product_id`, `ingredient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. STOCK MOVEMENTS TABLE
DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE `stock_movements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reference_type` VARCHAR(50) NOT NULL,
    `reference_id` INT NULL,
    `item_type` ENUM('PRODUCT', 'INGREDIENT') NOT NULL,
    `item_id` INT NOT NULL,
    `movement_type` ENUM('OPENING', 'IN', 'OUT', 'SALE', 'SALE_REVERSAL', 'ADJUSTMENT', 'VOID', 'REFUND', 'WASTE') NOT NULL,
    `qty` DECIMAL(12,2) NOT NULL,
    `stock_before` DECIMAL(12,2) NOT NULL,
    `stock_after` DECIMAL(12,2) NOT NULL,
    `unit` VARCHAR(20) NOT NULL DEFAULT 'pcs',
    `note` TEXT NULL,
    `created_by` INT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_stock_item` (`item_type`, `item_id`),
    INDEX `idx_stock_movement_type` (`movement_type`),
    INDEX `idx_stock_created_at` (`created_at`),
    INDEX `idx_stock_ref` (`reference_type`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TRANSACTIONS TABLE
DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transaction_code` VARCHAR(50) NOT NULL UNIQUE,
    `cashier_id` INT NOT NULL,
    `transaction_date` DATETIME NOT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_type` ENUM('NONE', 'PERCENT', 'FIXED') NOT NULL DEFAULT 'NONE',
    `discount_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_by` INT NULL,
    `grand_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_cogs` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_method` ENUM('CASH', 'QRIS', 'TRANSFER', 'EWALLET') NOT NULL DEFAULT 'CASH',
    `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `change_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('PAID', 'HELD', 'VOID', 'REFUNDED', 'PARTIAL_REFUND') NOT NULL DEFAULT 'PAID',
    `void_reason` TEXT NULL,
    `void_by` INT NULL,
    `void_at` DATETIME NULL,
    `refund_reason` TEXT NULL,
    `refund_by` INT NULL,
    `refund_at` DATETIME NULL,
    `hold_note` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL,
    FOREIGN KEY (`cashier_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`discount_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`void_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`refund_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_transactions_code` (`transaction_code`),
    INDEX `idx_transactions_cashier` (`cashier_id`),
    INDEX `idx_transactions_date` (`transaction_date`),
    INDEX `idx_transactions_status` (`status`),
    INDEX `idx_transactions_payment_method` (`payment_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. TRANSACTION ITEMS TABLE
DROP TABLE IF EXISTS `transaction_items`;
CREATE TABLE `transaction_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transaction_id` INT NOT NULL,
    `product_id` INT NULL,
    `product_name` VARCHAR(150) NOT NULL,
    `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `cost_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `qty` INT NOT NULL DEFAULT 1,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `hpp` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `refunded_qty` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL,
    INDEX `idx_item_transaction` (`transaction_id`),
    INDEX `idx_item_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. PAYMENTS TABLE
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transaction_id` INT NOT NULL,
    `payment_method` ENUM('CASH', 'QRIS', 'TRANSFER', 'EWALLET') NOT NULL,
    `provider` VARCHAR(50) NULL,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `reference_number` VARCHAR(100) NULL,
    `received_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `change_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `paid_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` INT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_payments_trans` (`transaction_id`),
    INDEX `idx_payments_method` (`payment_method`),
    INDEX `idx_payments_date` (`paid_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. ATTENDANCE TABLE
DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `clock_in` TIME NULL,
    `clock_out` TIME NULL,
    `worked_minutes` INT NOT NULL DEFAULT 0,
    `regular_minutes` INT NOT NULL DEFAULT 0,
    `overtime_minutes` INT NOT NULL DEFAULT 0,
    `status` ENUM('HADIR', 'SELESAI', 'IZIN', 'SAKIT', 'ALPHA', 'LIBUR') NOT NULL DEFAULT 'HADIR',
    `note` TEXT NULL,
    `auto_clock_in` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_date` (`user_id`, `date`),
    INDEX `idx_attendance_user` (`user_id`),
    INDEX `idx_attendance_date` (`date`),
    INDEX `idx_attendance_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. PAYROLL PERIODS TABLE
DROP TABLE IF EXISTS `payroll_periods`;
CREATE TABLE `payroll_periods` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `is_closed` TINYINT(1) NOT NULL DEFAULT 0,
    `closed_at` DATETIME NULL,
    `created_by` INT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_period_dates` (`start_date`, `end_date`),
    INDEX `idx_period_closed` (`is_closed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. PAYROLLS TABLE
DROP TABLE IF EXISTS `payrolls`;
CREATE TABLE `payrolls` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `period_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `regular_hours` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    `overtime_hours` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    `hourly_rate_snapshot` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `overtime_rate_snapshot` DECIMAL(12,2) NOT NULL DEFAULT 5000.00,
    `regular_pay` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `overtime_pay` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `bonus` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `deduction` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `gross_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `net_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_status` ENUM('UNPAID', 'PAID') NOT NULL DEFAULT 'UNPAID',
    `payment_date` DATE NULL,
    `payment_method` VARCHAR(50) NULL,
    `notes` TEXT NULL,
    `paid_by` INT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`period_id`) REFERENCES `payroll_periods`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`paid_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `unique_period_user` (`period_id`, `user_id`),
    INDEX `idx_payroll_period` (`period_id`),
    INDEX `idx_payroll_user` (`user_id`),
    INDEX `idx_payroll_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. OPERATIONAL EXPENSES TABLE
DROP TABLE IF EXISTS `operational_expenses`;
CREATE TABLE `operational_expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `date` DATE NOT NULL,
    `category` ENUM('LISTRIK', 'AIR', 'GAS', 'INTERNET', 'TRANSPORT', 'KEBERSIHAN', 'LAINNYA') NOT NULL DEFAULT 'LAINNYA',
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `description` TEXT NOT NULL,
    `receipt_image` VARCHAR(255) NULL,
    `created_by` INT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_expense_date` (`date`),
    INDEX `idx_expense_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. AUDIT LOGS TABLE
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `module` VARCHAR(100) NOT NULL,
    `reference_type` VARCHAR(50) NULL,
    `reference_id` INT NULL,
    `old_values_json` LONGTEXT NULL,
    `new_values_json` LONGTEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_module` (`module`),
    INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. ACTIVE SESSIONS TABLE (Realtime User Tracking)
DROP TABLE IF EXISTS `active_sessions`;
CREATE TABLE `active_sessions` (
    `id` VARCHAR(128) PRIMARY KEY,
    `user_id` INT NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT NULL,
    `device_info` VARCHAR(150) NULL,
    `current_url` VARCHAR(255) NULL,
    `last_activity_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_session_user` (`user_id`),
    INDEX `idx_session_activity` (`last_activity_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. SETTINGS TABLE
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `shop_name` VARCHAR(150) NOT NULL DEFAULT 'Warung Kaure',
    `address` TEXT NULL,
    `phone` VARCHAR(50) NULL,
    `receipt_footer` TEXT NULL,
    `logo` VARCHAR(255) NULL,
    `receipt_width` VARCHAR(10) NOT NULL DEFAULT 'auto',
    `cashier_max_discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    `default_overtime_rate` DECIMAL(12,2) NOT NULL DEFAULT 5000.00,
    `regular_hours_per_day` INT NOT NULL DEFAULT 8,
    `payroll_cycle_days` INT NOT NULL DEFAULT 14,
    `timezone` VARCHAR(50) NOT NULL DEFAULT 'Asia/Jakarta',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- WARUNG KAURE POS & MANAGEMENT SYSTEM
-- SEED DATA
-- ==========================================================



SET FOREIGN_KEY_CHECKS = 0;

-- Clean existing data
TRUNCATE TABLE `product_recipes`;
TRUNCATE TABLE `transaction_items`;
TRUNCATE TABLE `payments`;
TRUNCATE TABLE `transactions`;
TRUNCATE TABLE `stock_movements`;
TRUNCATE TABLE `products`;
TRUNCATE TABLE `ingredients`;
TRUNCATE TABLE `categories`;
TRUNCATE TABLE `attendance`;
TRUNCATE TABLE `payrolls`;
TRUNCATE TABLE `payroll_periods`;
TRUNCATE TABLE `operational_expenses`;
TRUNCATE TABLE `audit_logs`;
TRUNCATE TABLE `users`;
TRUNCATE TABLE `settings`;

-- 1. SETTINGS
INSERT INTO `settings` (`id`, `shop_name`, `address`, `phone`, `receipt_footer`, `receipt_width`, `cashier_max_discount_percent`, `default_overtime_rate`, `regular_hours_per_day`, `payroll_cycle_days`, `timezone`) VALUES
(1, 'Warung Kaure', 'Jl. Rinjani No. 12, Jakarta Selatan', '0812-3456-7890', 'Terima kasih atas kunjungan Anda!\nFollow IG: @warungkaure', 'auto', 20.00, 5000.00, 8, 14, 'Asia/Jakarta');

-- 2. USERS (Pass owner: owner123, kasir: kasir123)
INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `phone`, `hourly_rate`, `overtime_rate`, `status`) VALUES
(1, 'Budi Santoso (Owner)', 'owner', '$2y$10$tOIxiC5xfvqM0/FiSMrYmeULKPd3V3iAouqRBuLhnknVt4U601I5K', 'OWNER', '081234567890', 25000.00, 5000.00, 'ACTIVE'),
(2, 'Andi Pratama (Kasir)', 'kasir', '$2y$10$PAMNnVFIJSJkdv5VG9c2HexgbiBY09/COtJqb6gm8OnbkZb658ioK', 'CASHIER', '081298765432', 15000.00, 5000.00, 'ACTIVE'),
(3, 'Siti Rahma (Kasir)', 'siti', '$2y$10$PAMNnVFIJSJkdv5VG9c2HexgbiBY09/COtJqb6gm8OnbkZb658ioK', 'CASHIER', '081311223344', 15000.00, 5000.00, 'ACTIVE');

-- 3. CATEGORIES
INSERT INTO `categories` (`id`, `name`, `status`) VALUES
(1, 'Kopi', 'ACTIVE'),
(2, 'Non Kopi', 'ACTIVE'),
(3, 'Makanan', 'ACTIVE'),
(4, 'Snack & Pastry', 'ACTIVE');

-- 4. INGREDIENTS (Bahan Baku)
INSERT INTO `ingredients` (`id`, `name`, `unit`, `current_stock`, `minimum_stock`, `average_cost`, `status`) VALUES
(1, 'Biji Kopi Arabika', 'gram', 5000.00, 1000.00, 250.0000, 'ACTIVE'),
(2, 'Susu UHT Fresh', 'ml', 10000.00, 2000.00, 20.0000, 'ACTIVE'),
(3, 'Gula Aren Cair', 'ml', 3000.00, 500.00, 35.0000, 'ACTIVE'),
(4, 'Cup Kaure 16oz', 'pcs', 500.00, 100.00, 800.0000, 'ACTIVE'),
(5, 'Sedotan & Lid', 'pcs', 500.00, 100.00, 300.0000, 'ACTIVE'),
(6, 'Sirup Karamel', 'ml', 2000.00, 400.00, 45.0000, 'ACTIVE'),
(7, 'Bubuk Matcha Premium', 'gram', 1500.00, 300.00, 300.0000, 'ACTIVE');

-- 5. PRODUCTS
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `image`, `selling_price`, `cost_price`, `stock_tracking_type`, `stock`, `minimum_stock`, `status`) VALUES
(1, 1, 'WK-KOP-01', 'Es Kopi Susu Kaure', 'kopi_susu.jpg', 18000.00, 8000.00, 'RECIPE', 0, 5, 'ACTIVE'),
(2, 1, 'WK-KOP-02', 'Americano Dingin', 'americano.jpg', 15000.00, 5600.00, 'RECIPE', 0, 5, 'ACTIVE'),
(3, 1, 'WK-KOP-03', 'Caramel Macchiato', 'caramel_macchiato.jpg', 22000.00, 9800.00, 'RECIPE', 0, 5, 'ACTIVE'),
(4, 2, 'WK-NKP-01', 'Matcha Latte Ice', 'matcha_latte.jpg', 20000.00, 8500.00, 'RECIPE', 0, 5, 'ACTIVE'),
(5, 2, 'WK-DRC-01', 'Air Mineral 600ml', 'air_mineral.jpg', 5000.00, 2800.00, 'DIRECT', 48, 10, 'ACTIVE'),
(6, 4, 'WK-DRC-02', 'Croissant Coklat', 'croissant.jpg', 15000.00, 8500.00, 'DIRECT', 25, 5, 'ACTIVE'),
(7, 4, 'WK-DRC-03', 'Keripik Kentang Balado', 'keripik.jpg', 12000.00, 6500.00, 'DIRECT', 30, 5, 'ACTIVE'),
(8, 3, 'WK-MKN-01', 'Nasi Goreng Kaure', 'nasi_goreng.jpg', 25000.00, 14000.00, 'DIRECT', 20, 5, 'ACTIVE'),
(9, 3, 'WK-MKN-02', 'Mie Goreng Spesial', 'mie_goreng.jpg', 22000.00, 12000.00, 'DIRECT', 20, 5, 'ACTIVE');

-- 6. PRODUCT RECIPES (BOM)
-- Es Kopi Susu Kaure: 18g kopi + 120ml susu + 20ml aren + 1 cup + 1 lid
INSERT INTO `product_recipes` (`product_id`, `ingredient_id`, `quantity`) VALUES
(1, 1, 18.00),
(1, 2, 120.00),
(1, 3, 20.00),
(1, 4, 1.00),
(1, 5, 1.00);

-- Americano Dingin: 18g kopi + 1 cup + 1 lid
INSERT INTO `product_recipes` (`product_id`, `ingredient_id`, `quantity`) VALUES
(2, 1, 18.00),
(2, 4, 1.00),
(2, 5, 1.00);

-- Caramel Macchiato: 18g kopi + 120ml susu + 20ml sirup karamel + 1 cup + 1 lid
INSERT INTO `product_recipes` (`product_id`, `ingredient_id`, `quantity`) VALUES
(3, 1, 18.00),
(3, 2, 120.00),
(3, 6, 20.00),
(3, 4, 1.00),
(3, 5, 1.00);

-- Matcha Latte Ice: 15g bubuk matcha + 150ml susu + 15ml gula aren + 1 cup + 1 lid
INSERT INTO `product_recipes` (`product_id`, `ingredient_id`, `quantity`) VALUES
(4, 7, 15.00),
(4, 2, 150.00),
(4, 3, 15.00),
(4, 4, 1.00),
(4, 5, 1.00);

-- 7. INITIAL STOCK MOVEMENTS (Opening stock)
INSERT INTO `stock_movements` (`reference_type`, `reference_id`, `item_type`, `item_id`, `movement_type`, `qty`, `stock_before`, `stock_after`, `unit`, `note`, `created_by`) VALUES
('OPENING', NULL, 'INGREDIENT', 1, 'OPENING', 5000.00, 0.00, 5000.00, 'gram', 'Stok awal Biji Kopi Arabika', 1),
('OPENING', NULL, 'INGREDIENT', 2, 'OPENING', 10000.00, 0.00, 10000.00, 'ml', 'Stok awal Susu UHT', 1),
('OPENING', NULL, 'INGREDIENT', 3, 'OPENING', 3000.00, 0.00, 3000.00, 'ml', 'Stok awal Gula Aren', 1),
('OPENING', NULL, 'INGREDIENT', 4, 'OPENING', 500.00, 0.00, 500.00, 'pcs', 'Stok awal Cup Kaure 16oz', 1),
('OPENING', NULL, 'INGREDIENT', 5, 'OPENING', 500.00, 0.00, 500.00, 'pcs', 'Stok awal Sedotan & Lid', 1),
('OPENING', NULL, 'INGREDIENT', 6, 'OPENING', 2000.00, 0.00, 2000.00, 'ml', 'Stok awal Sirup Karamel', 1),
('OPENING', NULL, 'INGREDIENT', 7, 'OPENING', 1500.00, 0.00, 1500.00, 'gram', 'Stok awal Bubuk Matcha', 1),
('OPENING', NULL, 'PRODUCT', 5, 'OPENING', 48.00, 0.00, 48.00, 'pcs', 'Stok awal Air Mineral', 1),
('OPENING', NULL, 'PRODUCT', 6, 'OPENING', 25.00, 0.00, 25.00, 'pcs', 'Stok awal Croissant Coklat', 1),
('OPENING', NULL, 'PRODUCT', 7, 'OPENING', 30.00, 0.00, 30.00, 'pcs', 'Stok awal Keripik Kentang Balado', 1),
('OPENING', NULL, 'PRODUCT', 8, 'OPENING', 20.00, 0.00, 20.00, 'pcs', 'Stok awal Nasi Goreng Kaure', 1),
('OPENING', NULL, 'PRODUCT', 9, 'OPENING', 20.00, 0.00, 20.00, 'pcs', 'Stok awal Mie Goreng Spesial', 1);

-- 8. PAYROLL PERIOD
INSERT INTO `payroll_periods` (`id`, `name`, `start_date`, `end_date`, `is_closed`, `created_by`) VALUES
(1, 'Periode 01 Sep 2026 - 14 Sep 2026', '2026-09-01', '2026-09-14', 0, 1);

-- 9. OPERATIONAL EXPENSES
INSERT INTO `operational_expenses` (`id`, `date`, `category`, `amount`, `description`, `created_by`) VALUES
(1, '2026-09-01', 'LISTRIK', 450000.00, 'Pembayaran Token Listrik PLN Kedai', 1),
(2, '2026-09-02', 'INTERNET', 350000.00, 'Langganan Internet WiFi Kedai IndiHome', 1),
(3, '2026-09-05', 'GAS', 65000.00, 'Isi Ulang Tabung Gas Elpiji 3kg', 1);

-- 10. INITIAL AUDIT LOG
INSERT INTO `audit_logs` (`user_id`, `action`, `module`, `reference_type`, `reference_id`, `new_values_json`, `ip_address`, `user_agent`) VALUES
(1, 'DATABASE_SEED', 'SYSTEM', 'DATABASE', 1, '{"message":"Database seed initial data inserted successfully"}', '127.0.0.1', 'CLI Seeder');

SET FOREIGN_KEY_CHECKS = 1;

SET FOREIGN_KEY_CHECKS = 1;
