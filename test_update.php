<?php
/**
 * اختبار تحديث قاعدة البيانات
 * Test database update
 */

require_once 'config/config.php';

echo "<h2>اختبار تحديث قاعدة البيانات</h2>";

try {
    // التحقق من وجود عمود sales_count
    echo "<h3>1. التحقق من عمود sales_count:</h3>";
    $check_query = "SHOW COLUMNS FROM products LIKE 'sales_count'";
    $stmt = $db->prepare($check_query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "✅ عمود sales_count موجود<br>";
    } else {
        echo "❌ عمود sales_count غير موجود - سيتم إضافته<br>";
        $alter_query = "ALTER TABLE products ADD COLUMN sales_count INT DEFAULT 0";
        $db->exec($alter_query);
        echo "✅ تم إضافة عمود sales_count<br>";
    }
    
    // التحقق من وجود عمود delivered_at
    echo "<h3>2. التحقق من عمود delivered_at:</h3>";
    $check_query = "SHOW COLUMNS FROM orders LIKE 'delivered_at'";
    $stmt = $db->prepare($check_query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "✅ عمود delivered_at موجود<br>";
    } else {
        echo "❌ عمود delivered_at غير موجود - سيتم إضافته<br>";
        $alter_query = "ALTER TABLE orders ADD COLUMN delivered_at TIMESTAMP NULL";
        $db->exec($alter_query);
        echo "✅ تم إضافة عمود delivered_at<br>";
    }
    
    // اختبار تحديث حالة طلب
    echo "<h3>3. اختبار تحديث حالة الطلب:</h3>";
    
    // الحصول على أول طلب
    $query = "SELECT * FROM orders LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        echo "طلب للاختبار: #" . str_pad($order['id'], 6, '0', STR_PAD_LEFT) . "<br>";
        
        require_once 'classes/Order.php';
        $orderObj = new Order($db);
        
        // اختبار تحديث الحالة
        $result = $orderObj->updateOrderStatus($order['id'], 'delivered');
        
        if ($result) {
            echo "✅ تم تحديث حالة الطلب بنجاح<br>";
            
            // اختبار تحديث الإحصائيات
            $stats_result = $orderObj->updateSalesStatistics($order['id']);
            if ($stats_result) {
                echo "✅ تم تحديث إحصائيات المبيعات بنجاح<br>";
            } else {
                echo "❌ فشل في تحديث إحصائيات المبيعات<br>";
            }
        } else {
            echo "❌ فشل في تحديث حالة الطلب<br>";
        }
    } else {
        echo "❌ لا توجد طلبات للاختبار<br>";
    }
    
    // عرض الإحصائيات
    echo "<h3>4. الإحصائيات الحالية:</h3>";
    require_once 'classes/Order.php';
    $orderObj = new Order($db);
    $stats = $orderObj->getOrderStats();
    
    echo "إجمالي الطلبات: " . $stats['total_orders'] . "<br>";
    echo "الطلبات المعلقة: " . $stats['pending_orders'] . "<br>";
    echo "إجمالي المبيعات: " . number_format($stats['total_sales'], 2) . " ر.س<br>";
    
    echo "<h3>✅ تم الانتهاء من الاختبار بنجاح!</h3>";
    echo "<p><a href='admin/orders.php'>الذهاب إلى إدارة الطلبات</a></p>";
    
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
</style>
