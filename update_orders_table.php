<?php
/**
 * تحديث جدول الطلبات لإضافة عمود created_by_admin
 * Update orders table to add created_by_admin column
 */

require_once 'config/config.php';

echo "<h2>تحديث جدول الطلبات</h2>";

try {
    $db = getDBConnection();
    
    // التحقق من وجود العمود
    $check_query = "SHOW COLUMNS FROM orders LIKE 'created_by_admin'";
    $stmt = $db->prepare($check_query);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo "<p>إضافة عمود created_by_admin...</p>";
        
        $alter_query = "ALTER TABLE orders ADD COLUMN created_by_admin TINYINT(1) DEFAULT 0 AFTER status";
        $db->exec($alter_query);
        
        echo "✅ تم إضافة العمود بنجاح<br>";
    } else {
        echo "✅ العمود موجود بالفعل<br>";
    }
    
    // التحقق من وجود عمود admin_notes
    $check_notes_query = "SHOW COLUMNS FROM orders LIKE 'admin_notes'";
    $stmt = $db->prepare($check_notes_query);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo "<p>إضافة عمود admin_notes...</p>";
        
        $alter_notes_query = "ALTER TABLE orders ADD COLUMN admin_notes TEXT NULL AFTER created_by_admin";
        $db->exec($alter_notes_query);
        
        echo "✅ تم إضافة عمود الملاحظات بنجاح<br>";
    } else {
        echo "✅ عمود الملاحظات موجود بالفعل<br>";
    }
    
    // عرض هيكل الجدول المحدث
    echo "<h3>هيكل جدول الطلبات المحدث:</h3>";
    $describe_query = "DESCRIBE orders";
    $stmt = $db->prepare($describe_query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>اسم العمود</th><th>النوع</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h3>✅ تم تحديث قاعدة البيانات بنجاح!</h3>";
    echo "<p><a href='admin/orders.php'>الذهاب إلى إدارة الطلبات</a></p>";
    echo "<p><a href='test_cart_separation.php'>اختبار فصل سلة المشتريات</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ خطأ: " . $e->getMessage() . "</h3>";
    echo "<p>تفاصيل الخطأ: " . $e->getTraceAsString() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    direction: rtl;
    text-align: right;
    margin: 20px;
}

h2, h3 {
    color: #333;
}

table {
    margin: 20px 0;
    font-size: 14px;
}

th {
    background: #f8f9fa;
    padding: 10px;
    text-align: center;
}

td {
    padding: 8px;
    text-align: center;
    border: 1px solid #ddd;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>
