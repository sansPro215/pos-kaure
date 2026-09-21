<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

echo "--- RUNNING REALTIME AUDIT & SESSION MIGRATION ---\n";

// 1. Create active_sessions table
$sqlSessions = "CREATE TABLE IF NOT EXISTS `active_sessions` (
    `id` VARCHAR(128) PRIMARY KEY,
    `user_id` INT NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT NULL,
    `device_info` VARCHAR(150) NULL,
    `current_url` VARCHAR(255) NULL,
    `last_activity_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_session_user` (`user_id`),
    INDEX `idx_session_activity` (`last_activity_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

Database::execute($sqlSessions);
echo "1. Table active_sessions checked/created.\n";

// 2. Add last_activity_at and last_ip to users if not existing
$userCols = Database::fetchAll("SHOW COLUMNS FROM users");
$existingCols = array_column($userCols, 'Field');

if (!in_array('last_activity_at', $existingCols)) {
    Database::execute("ALTER TABLE users ADD COLUMN `last_activity_at` DATETIME NULL AFTER `last_login_at`");
    echo "2. Column users.last_activity_at added.\n";
} else {
    echo "2. Column users.last_activity_at already exists.\n";
}

if (!in_array('last_ip', $existingCols)) {
    Database::execute("ALTER TABLE users ADD COLUMN `last_ip` VARCHAR(45) NULL AFTER `last_activity_at`");
    echo "3. Column users.last_ip added.\n";
} else {
    echo "3. Column users.last_ip already exists.\n";
}

echo "--- MIGRATION COMPLETED SUCCESSFULLY ---\n";
