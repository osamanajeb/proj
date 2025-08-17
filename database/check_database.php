<?php
/**
 * فحص حالة قاعدة البيانات
 * Check database status
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = getDBConnection();
    echo "اتصال قاعدة البيانات: نجح\n";
    
    // التحقق من وجود الجداول
    $tables = ['categories', 'products', 'users', 'orders'];
    foreach ($tables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "جدول $table: موجود\n";
        } else {
            echo "جدول $table: غير موجود\n";
        }
    }
    
    // التحقق من وجود بيانات في الجداول
    $result = $db->query('SELECT COUNT(*) as count FROM categories');
    $categories_count = $result->fetch()['count'];
    echo "عدد الفئات الحالية: $categories_count\n";
    
    $result = $db->query('SELECT COUNT(*) as count FROM products');
    $products_count = $result->fetch()['count'];
    echo "عدد المنتجات الحالية: $products_count\n";
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
}
?>
