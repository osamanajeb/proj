<?php
/**
 * تثبيت البيانات التجريبية
 * Install sample data
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = getDBConnection();
    echo "بدء تثبيت البيانات التجريبية...\n";
    
    // قراءة ملف SQL
    $sql_file = __DIR__ . '/sample_data.sql';
    if (!file_exists($sql_file)) {
        throw new Exception('ملف البيانات التجريبية غير موجود');
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // تنظيف البيانات الموجودة (اختياري)
    echo "تنظيف البيانات الموجودة...\n";
    $db->exec('DELETE FROM product_images');
    $db->exec('DELETE FROM products');
    $db->exec('DELETE FROM categories');
    
    // إعادة تعيين AUTO_INCREMENT
    $db->exec('ALTER TABLE categories AUTO_INCREMENT = 1');
    $db->exec('ALTER TABLE products AUTO_INCREMENT = 1');
    $db->exec('ALTER TABLE product_images AUTO_INCREMENT = 1');
    
    // تقسيم الاستعلامات
    $queries = explode(';', $sql_content);
    
    $success_count = 0;
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query) && !preg_match('/^--/', $query)) {
            try {
                $db->exec($query);
                $success_count++;
            } catch (Exception $e) {
                echo "تحذير: فشل في تنفيذ استعلام: " . substr($query, 0, 50) . "...\n";
                echo "الخطأ: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "تم تنفيذ $success_count استعلام بنجاح\n";
    
    // التحقق من النتائج
    $result = $db->query('SELECT COUNT(*) as count FROM categories');
    $categories_count = $result->fetch()['count'];
    echo "عدد الفئات المثبتة: $categories_count\n";
    
    $result = $db->query('SELECT COUNT(*) as count FROM products');
    $products_count = $result->fetch()['count'];
    echo "عدد المنتجات المثبتة: $products_count\n";
    
    echo "تم تثبيت البيانات التجريبية بنجاح!\n";
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
}
?>
