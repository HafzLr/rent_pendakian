<?php
// migrate.php - Ensure columns and test data exist
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    // Add missing columns if they don't exist
    $columnsToAdd = [
        ['users', 'verification_notes', 'TEXT NULL AFTER verification_status'],
        ['users', 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP'],
        ['products', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1'],
        ['product_variants', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1'],
        ['rentals', 'deposit_refunded', 'DECIMAL(12,2) NOT NULL DEFAULT 0.00'],
        ['rentals', 'deposit_refunded_at', 'DATETIME NULL'],
        ['payments', 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP']
    ];

    foreach ($columnsToAdd as [$table, $col, $def]) {
        $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        if ($stmt->rowCount() === 0) {
            $db->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
            echo "Added $col to $table\n";
        } else {
            echo "Column $col already exists in $table\n";
        }
    }

    // Ensure default test admin and user accounts have known passwords
    // Admin: admin@rentgear.id / password123
    // User: pendaki@rentgear.id / password123
    $defaultHash = password_hash('password123', PASSWORD_BCRYPT);
    
    $checkAdmin = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $checkAdmin->execute(['admin@rentgear.id']);
    if ($checkAdmin->rowCount() > 0) {
        $db->prepare("UPDATE users SET password = ?, role = 'admin', verification_status = 'verified' WHERE email = ?")
           ->execute([$defaultHash, 'admin@rentgear.id']);
        echo "Updated admin@rentgear.id password to 'password123'\n";
    } else {
        $db->prepare("INSERT INTO users (name, email, password, user_number, role, verification_status) VALUES (?, ?, ?, ?, 'admin', 'verified')")
           ->execute(['Admin RentGear', 'admin@rentgear.id', $defaultHash, '081234567890']);
        echo "Created admin@rentgear.id\n";
    }

    $checkUser = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $checkUser->execute(['pendaki@rentgear.id']);
    if ($checkUser->rowCount() > 0) {
        $db->prepare("UPDATE users SET password = ?, role = 'user', verification_status = 'verified' WHERE email = ?")
           ->execute([$defaultHash, 'pendaki@rentgear.id']);
        echo "Updated pendaki@rentgear.id password to 'password123'\n";
    } else {
        $db->prepare("INSERT INTO users (name, email, password, user_number, role, verification_status) VALUES (?, ?, ?, ?, 'user', 'verified')")
           ->execute(['Budi Petualang', 'pendaki@rentgear.id', $defaultHash, '089876543210']);
        echo "Created pendaki@rentgear.id\n";
    }

    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
