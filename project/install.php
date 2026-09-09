<?php

declare(strict_types=1);

/**
 * Installation Script - Set up database tables and initial data
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 * 
 * IMPORTANT: Table prefix changed from wk_ to hos_
 */

// Load configuration
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    die("Configuration file not found. Please create config.php first.\n");
}

$config = require $configFile;

echo "===========================================\n";
echo "  Project Komplace - Installation Script\n";
echo "===========================================\n\n";

// Database connection
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;charset=utf8mb4',
        $config['database']['host'],
        $config['database']['port']
    );
    
    $pdo = new PDO(
        $dsn,
        $config['database']['username'],
        $config['database']['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "[OK] Connected to database server\n\n";
    
    // Create database if not exists
    $dbName = $config['database']['database'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");
    
    echo "[OK] Database '$dbName' ready\n\n";
    
    // Get table prefix
    $prefix = $config['database']['prefix'];
    
    // Drop existing tables (for clean install)
    echo "Dropping existing tables...\n";
    $pdo->exec("DROP TABLE IF EXISTS `{$prefix}usermeta`");
    $pdo->exec("DROP TABLE IF EXISTS `{$prefix}sessions`");
    $pdo->exec("DROP TABLE IF EXISTS `{$prefix}settings`");
    $pdo->exec("DROP TABLE IF EXISTS `{$prefix}users`");
    echo "[OK] Existing tables dropped\n\n";
    
    // Create users table
    echo "Creating users table...\n";
    $pdo->exec("
        CREATE TABLE `{$prefix}users` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `full_name` varchar(255) NOT NULL,
            `email` varchar(255) NOT NULL,
            `email_hash` varchar(255) NOT NULL,
            `phone` varchar(50) DEFAULT NULL,
            `password` varchar(255) NOT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'pending',
            `metadata` text DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`),
            KEY `email_hash` (`email_hash`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "[OK] Users table created\n\n";
    
    // Create sessions table
    echo "Creating sessions table...\n";
    $pdo->exec("
        CREATE TABLE `{$prefix}sessions` (
            `id` varchar(36) NOT NULL,
            `user_id` bigint(20) UNSIGNED NOT NULL,
            `ip_address` varchar(45) NOT NULL,
            `user_agent` text DEFAULT NULL,
            `signature` varchar(64) DEFAULT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'active',
            `expires_at` timestamp NOT NULL,
            `last_active_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_user_status_exp` (`user_id`,`status`,`expires_at`),
            KEY `idx_expires_at` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "[OK] Sessions table created\n\n";
    
    // Create settings table
    echo "Creating settings table...\n";
    $pdo->exec("
        CREATE TABLE `{$prefix}settings` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `setting_key` varchar(255) NOT NULL,
            `setting_value` text DEFAULT NULL,
            `autoload` tinyint(1) NOT NULL DEFAULT 0,
            `data_type` varchar(50) NOT NULL,
            `title` varchar(255) NOT NULL,
            `description` text DEFAULT NULL,
            `default_value` text DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "[OK] Settings table created\n\n";
    
    // Create usermeta table
    echo "Creating usermeta table...\n";
    $pdo->exec("
        CREATE TABLE `{$prefix}usermeta` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` bigint(20) UNSIGNED NOT NULL,
            `meta_key` varchar(100) NOT NULL,
            `meta_value` text DEFAULT NULL,
            `data_type` varchar(50) NOT NULL,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `meta_key` (`meta_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "[OK] Usermeta table created\n\n";
    
    // Insert default settings
    echo "Inserting default settings...\n";
    $stmt = $pdo->prepare("
        INSERT INTO `{$prefix}settings` 
        (`setting_key`, `setting_value`, `autoload`, `data_type`, `title`, `description`, `default_value`, `created_at`, `updated_at`)
        VALUES 
        (:key, :value, :autoload, :type, :title, :desc, :default, NOW(), NOW())
    ");
    
    $defaultSettings = [
        [
            'key' => 'site_name',
            'value' => 'Project Komplace',
            'autoload' => 1,
            'type' => 'string',
            'title' => 'Site Name',
            'desc' => 'The name of your website',
            'default' => 'Project Komplace'
        ],
        [
            'key' => 'site_email',
            'value' => 'admin@example.com',
            'autoload' => 1,
            'type' => 'string',
            'title' => 'Site Email',
            'desc' => 'Contact email address',
            'default' => 'admin@example.com'
        ],
        [
            'key' => 'maintenance_mode',
            'value' => '0',
            'autoload' => 1,
            'type' => 'boolean',
            'title' => 'Maintenance Mode',
            'desc' => 'Enable/disable maintenance mode',
            'default' => '0'
        ],
        [
            'key' => 'registration_enabled',
            'value' => '1',
            'autoload' => 1,
            'type' => 'boolean',
            'title' => 'Registration Enabled',
            'desc' => 'Allow user registration',
            'default' => '1'
        ]
    ];
    
    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
    }
    echo "[OK] Default settings inserted (" . count($defaultSettings) . " settings)\n\n";
    
    // Create default admin user
    echo "Creating default admin user...\n";
    $adminEmail = 'admin@example.com';
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $emailHash = hash('sha256', $adminEmail);
    
    $pdo->exec("
        INSERT INTO `{$prefix}users` 
        (`full_name`, `email`, `email_hash`, `password`, `status`, `created_at`, `updated_at`)
        VALUES 
        ('Administrator', '$adminEmail', '$emailHash', '$adminPassword', 'active', NOW(), NOW())
    ");
    echo "[OK] Admin user created\n";
    echo "     Email: $adminEmail\n";
    echo "     Password: admin123\n\n";
    
    echo "===========================================\n";
    echo "  Installation completed successfully!\n";
    echo "===========================================\n\n";
    
} catch (PDOException $e) {
    echo "[ERROR] Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "[ERROR] Installation failed: " . $e->getMessage() . "\n";
    exit(1);
}
