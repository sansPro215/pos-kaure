-- ==========================================================
-- WARUNG KAURE POS & MANAGEMENT SYSTEM
-- DATABASE SCHEMA: warung_kaure
-- MySQL 8.x / MariaDB 10.x compatible
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `warung_kaure` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `warung_kaure`;

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
    `default_regular_rate` DECIMAL(12,2) NOT NULL DEFAULT 15000.00,
    `default_overtime_rate` DECIMAL(12,2) NOT NULL DEFAULT 5000.00,
    `regular_hours_per_day` INT NOT NULL DEFAULT 8,
    `payroll_cycle_days` INT NOT NULL DEFAULT 14,
    `manager_incentive_percent` DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    `shareholder_percent` DECIMAL(5,2) NOT NULL DEFAULT 80.00,
    `pos_layout` VARCHAR(30) NOT NULL DEFAULT 'grid_large',
    `theme_color` VARCHAR(30) NOT NULL DEFAULT 'coffee',
    `timezone` VARCHAR(50) NOT NULL DEFAULT 'Asia/Jakarta',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
