<?php

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    echo "✓ MySQL connection successful\n";
    
    // Create database
    $pdo->exec('CREATE DATABASE IF NOT EXISTS qttenzy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo "✓ Database 'qttenzy' created/verified\n";
    
    // Test connection to the new database
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=qttenzy', 'root', '');
    echo "✓ Successfully connected to 'qttenzy' database\n";
    
    echo "\n✅ Database setup complete! You can now run migrations.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    echo "Possible solutions:\n";
    echo "1. Make sure MySQL/XAMPP is running\n";
    echo "2. Check if MySQL password is set (update .env if needed)\n";
    echo "3. Verify MySQL is running on port 3306\n";
    exit(1);
}
