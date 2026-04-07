<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    echo "MySQL connected successfully\n";
    
    // Try to show databases
    $stmt = $pdo->query("SHOW DATABASES;");
    $databases = $stmt->fetchAll();
    echo "Available databases:\n";
    foreach ($databases as $db) {
        echo "  - " . $db[0] . "\n";
    }
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?>
