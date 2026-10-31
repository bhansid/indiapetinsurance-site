<?php
/** Database schema. Same tables for MySQL (Hostinger) and SQLite (local testing). */
declare(strict_types=1);

function schema_statements(string $driver): array {
    $my = $driver !== 'sqlite';
    $id = $my ? 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fk = $my ? 'INT UNSIGNED' : 'INTEGER';
    $txt = $my ? 'MEDIUMTEXT' : 'TEXT';
    $tail = $my ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
    $s = [];
    $s[] = "CREATE TABLE IF NOT EXISTS users (
        id $id,
        role VARCHAR(20) NOT NULL DEFAULT 'owner',
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NULL,
        mobile VARCHAR(15) NULL,
        password_hash VARCHAR(255) NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        mobile_verified TINYINT NOT NULL DEFAULT 0,
        marketing TINYINT NOT NULL DEFAULT 0,
        created_by $fk NULL,
        created_at DATETIME NOT NULL,
        last_login_at DATETIME NULL,
        UNIQUE (email), UNIQUE (mobile)
    )$tail";
    $s[] = "CREATE TABLE IF NOT EXISTS pets (
        id $id,
        id_no VARCHAR(32) NOT NULL UNIQUE,
        owner_id $fk NOT NULL,
        species VARCHAR(10) NOT NULL,
        name VARCHAR(60) NOT NULL,
        breed VARCHAR(80) NOT NULL,
        gender VARCHAR(2) NULL,
        city VARCHAR(80) NULL,
        state VARCHAR(80) NULL,
        clinic VARCHAR(160) NULL,
        doctor_mobile VARCHAR(15) NULL,
        pub_phone TINYINT NOT NULL DEFAULT 1,
        pub_addr VARCHAR(10) NOT NULL DEFAULT 'locality',
        pub_vet TINYINT NOT NULL DEFAULT 1,
        pub_allergy TINYINT NOT NULL DEFAULT 1,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        data $txt NOT NULL,
        scan_count INT NOT NULL DEFAULT 0,
        last_scan_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    )$tail";
    $s[] = "CREATE TABLE IF NOT EXISTS documents (
        id $id,
        pet_id $fk NOT NULL,
        kind VARCHAR(20) NOT NULL,
        label VARCHAR(60) NULL,
        original_name VARCHAR(160) NULL,
        path VARCHAR(255) NOT NULL,
        mime VARCHAR(60) NOT NULL,
        size INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL
    )$tail";
    $s[] = "CREATE TABLE IF NOT EXISTS orders (
        id $id,
        order_no VARCHAR(20) NOT NULL UNIQUE,
        pet_id $fk NOT NULL,
        owner_id $fk NOT NULL,
        product VARCHAR(20) NOT NULL,
        ship_name VARCHAR(120) NOT NULL,
        ship_mobile VARCHAR(15) NOT NULL,
        ship_address VARCHAR(255) NOT NULL,
        ship_city VARCHAR(80) NOT NULL,
        ship_state VARCHAR(80) NOT NULL,
        ship_pin VARCHAR(6) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        notes $txt NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    )$tail";
    $s[] = "CREATE TABLE IF NOT EXISTS quotes (
        id $id,
        quote_no VARCHAR(20) NOT NULL UNIQUE,
        pet_id $fk NOT NULL,
        owner_id $fk NOT NULL,
        plan VARCHAR(20) NOT NULL,
        sum_insured INT NOT NULL,
        ill_12m VARCHAR(5) NOT NULL,
        ongoing VARCHAR(5) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        notes $txt NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    )$tail";
    $s[] = "CREATE TABLE IF NOT EXISTS notifications (
        id $id,
        type VARCHAR(40) NOT NULL,
        channel VARCHAR(20) NOT NULL DEFAULT 'admin',
        recipient VARCHAR(190) NULL,
        title VARCHAR(190) NOT NULL,
        body $txt NULL,
        ref_table VARCHAR(30) NULL,
        ref_id $fk NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'unread',
        attempts INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        sent_at DATETIME NULL
    )$tail";
    $s[] = "CREATE TABLE IF NOT EXISTS activity_log (
        id $id,
        user_id $fk NULL,
        action VARCHAR(60) NOT NULL,
        details VARCHAR(1000) NULL,
        ip VARCHAR(45) NULL,
        created_at DATETIME NOT NULL
    )$tail";
    $s[] = "CREATE TABLE IF NOT EXISTS rate_limits (
        id $id,
        k VARCHAR(120) NOT NULL,
        created_at DATETIME NOT NULL
    )$tail";
    $s[] = 'CREATE INDEX idx_pets_owner ON pets (owner_id)';
    $s[] = 'CREATE INDEX idx_docs_pet ON documents (pet_id)';
    $s[] = 'CREATE INDEX idx_orders_pet ON orders (pet_id)';
    $s[] = 'CREATE INDEX idx_quotes_pet ON quotes (pet_id)';
    $s[] = 'CREATE INDEX idx_notif_status ON notifications (status, channel)';
    $s[] = 'CREATE INDEX idx_rl ON rate_limits (k, created_at)';
    return $s;
}

/** Small upgrades for databases created by an older version. Safe to run more than once. */
function migrations(): array {
    return [
        'ALTER TABLE users ADD COLUMN marketing TINYINT NOT NULL DEFAULT 0',
        // Payments (Razorpay)
        "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(10) NOT NULL DEFAULT 'cod'",
        "ALTER TABLE orders ADD COLUMN payment_status VARCHAR(20) NOT NULL DEFAULT 'cod'",
        'ALTER TABLE orders ADD COLUMN amount_paise INT NOT NULL DEFAULT 0',
        'ALTER TABLE orders ADD COLUMN rzp_order_id VARCHAR(40) NULL',
        'ALTER TABLE orders ADD COLUMN rzp_payment_id VARCHAR(40) NULL',
        'ALTER TABLE orders ADD COLUMN paid_at DATETIME NULL',
        'ALTER TABLE orders ADD COLUMN rzp_mode VARCHAR(5) NULL',
        'CREATE INDEX idx_orders_rzp ON orders (rzp_order_id)',
    ];
}
function run_migrations(PDO $pdo): void {
    foreach (migrations() as $sql) { try { $pdo->exec($sql); } catch (PDOException $e) { /* already applied */ } }
}

function run_schema(PDO $pdo, string $driver): void {
    foreach (schema_statements($driver) as $sql) {
        try { $pdo->exec($sql); }
        catch (PDOException $e) { if (stripos($sql, 'CREATE INDEX') !== 0) throw $e; } // index may already exist
    }
}
