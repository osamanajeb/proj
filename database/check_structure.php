<?php
/**
 * فحص هيكل الجداول
 * Check table structure
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = getDBConnection();
    
    echo "هيكل جدول categories:\n";
    $result = $db->query('DESCRIBE categories');
    while ($row = $result->fetch()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    echo "\nهيكل جدول products:\n";
    $result = $db->query('DESCRIBE products');
    while ($row = $result->fetch()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
}
?>
