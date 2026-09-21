-- ==========================================================
-- WARUNG KAURE POS & MANAGEMENT SYSTEM
-- SEED DATA
-- ==========================================================

USE `warung_kaure`;

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
INSERT INTO `settings` (`id`, `shop_name`, `address`, `phone`, `receipt_footer`, `receipt_width`, `cashier_max_discount_percent`, `default_regular_rate`, `default_overtime_rate`, `regular_hours_per_day`, `payroll_cycle_days`, `manager_incentive_percent`, `shareholder_percent`, `pos_layout`, `theme_color`, `timezone`) VALUES
(1, 'Warung Kaure', 'Jl. Rinjani No. 12, Jakarta Selatan', '0812-3456-7890', 'Terima kasih atas kunjungan Anda!\nFollow IG: @warungkaure', 'auto', 20.00, 15000.00, 5000.00, 8, 14, 20.00, 80.00, 'grid_large', 'coffee', 'Asia/Jakarta');

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
