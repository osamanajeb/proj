<?php
/**
 * تحديث قاعدة البيانات لتتبع المبيعات
 * Update database for sales tracking
 */

require_once '../config/config.php';

try {
    echo "بدء تحديث قاعدة البيانات...\n";
    
    // قراءة ملف SQL
    $sql_file = __DIR__ . '/update_sales_tracking.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("ملف SQL غير موجود: " . $sql_file);
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // تقسيم الاستعلامات
    $queries = array_filter(
        array_map('trim', explode(';', $sql_content)),
        function($query) {
            return !empty($query) && !preg_match('/^\s*--/', $query);
        }
    );
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($queries as $query) {
        try {
            $stmt = $db->prepare($query);
            $stmt->execute();
            $success_count++;
            echo "✓ تم تنفيذ الاستعلام بنجاح\n";
        } catch (PDOException $e) {
            $error_count++;
            echo "✗ خطأ في الاستعلام: " . $e->getMessage() . "\n";
            echo "الاستعلام: " . substr($query, 0, 100) . "...\n";
        }
    }
    
    echo "\n=== ملخص التحديث ===\n";
    echo "الاستعلامات الناجحة: {$success_count}\n";
    echo "الاستعلامات الفاشلة: {$error_count}\n";
    
    if ($error_count === 0) {
        echo "\n🎉 تم تحديث قاعدة البيانات بنجاح!\n";
        echo "الآن ستعمل إحصائيات المبيعات بشكل صحيح.\n";
    } else {
        echo "\n⚠️ تم التحديث مع بعض الأخطاء. يرجى مراجعة الأخطاء أعلاه.\n";
    }
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
    exit(1);
}
?>
