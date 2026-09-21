<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

try {
    echo "Updating attendance table schema...\n";
    Database::execute("ALTER TABLE attendance MODIFY COLUMN status ENUM('HADIR','SELESAI','IZIN','SAKIT','ALPHA','LIBUR') NOT NULL DEFAULT 'HADIR'");
    echo "SUCCESS: status ENUM updated with SELESAI.\n";

    // Also update existing completed attendances (where clock_out is not null and status = 'HADIR') to 'SELESAI'
    $affected = Database::execute("UPDATE attendance SET status = 'SELESAI' WHERE clock_out IS NOT NULL AND status = 'HADIR'");
    echo "Updated {$affected} completed records to status = 'SELESAI'.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
