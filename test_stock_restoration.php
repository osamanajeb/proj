<?php
/**
 * اختبار وظيفة إرجاع المخزون عند إلغاء الطلب
 * Test stock restoration when cancelling orders
 */

require_once 'config/config.php';
require_once 'classes/Order.php';
require_once 'classes/Product.php';

// التحقق من صلاحيات المشرف
if (!is_logged_in() || !is_admin()) {
    die('يجب أن تكون مشرفاً لتشغيل هذا الاختبار');
}

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائنات الفئات
$orderObj = new Order($db);
$productObj = new Product($db);

echo "<h2>اختبار وظيفة إرجاع المخزون عند إلغاء الطلب</h2>";
echo "<hr>";

// الحصول على طلب للاختبار
$orders = $orderObj->getAllOrders();
$test_order = null;

foreach ($orders as $order) {
    if ($order['status'] === 'pending' || $order['status'] === 'confirmed') {
        $test_order = $order;
        break;
    }
}

if (!$test_order) {
    echo "<p style='color: red;'>لا توجد طلبات متاحة للاختبار (يجب أن تكون في حالة pending أو confirmed)</p>";
    exit;
}

$order_id = $test_order['id'];
echo "<h3>اختبار الطلب رقم: #{$order_id}</h3>";

// الحصول على عناصر الطلب
$order_items = $orderObj->getOrderItems($order_id);
echo "<h4>منتجات الطلب:</h4>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>المنتج</th><th>الكمية المطلوبة</th><th>المخزون الحالي</th></tr>";

$stock_before = [];
foreach ($order_items as $item) {
    $product = $productObj->getProductById($item['product_id']);
    $stock_before[$item['product_id']] = $product['stock_quantity'];
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($item['name']) . "</td>";
    echo "<td>" . $item['quantity'] . "</td>";
    echo "<td>" . $product['stock_quantity'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><h4>إلغاء الطلب...</h4>";

// إلغاء الطلب
$result = $orderObj->cancelOrder($order_id);

if ($result['success']) {
    echo "<p style='color: green;'>✓ " . $result['message'] . "</p>";
    
    echo "<h4>حالة المخزون بعد الإلغاء:</h4>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>المنتج</th><th>الكمية المطلوبة</th><th>المخزون قبل الإلغاء</th><th>المخزون بعد الإلغاء</th><th>الفرق</th><th>النتيجة</th></tr>";
    
    $all_correct = true;
    foreach ($order_items as $item) {
        $product = $productObj->getProductById($item['product_id']);
        $stock_after = $product['stock_quantity'];
        $expected_stock = $stock_before[$item['product_id']] + $item['quantity'];
        $difference = $stock_after - $stock_before[$item['product_id']];
        $is_correct = ($stock_after == $expected_stock);
        
        if (!$is_correct) {
            $all_correct = false;
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['name']) . "</td>";
        echo "<td>" . $item['quantity'] . "</td>";
        echo "<td>" . $stock_before[$item['product_id']] . "</td>";
        echo "<td>" . $stock_after . "</td>";
        echo "<td style='color: " . ($difference > 0 ? 'green' : 'red') . ";'>+" . $difference . "</td>";
        echo "<td style='color: " . ($is_correct ? 'green' : 'red') . ";'>" . ($is_correct ? '✓ صحيح' : '✗ خطأ') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($all_correct) {
        echo "<h3 style='color: green;'>✓ نجح الاختبار! تم إرجاع جميع المنتجات للمخزون بشكل صحيح</h3>";
    } else {
        echo "<h3 style='color: red;'>✗ فشل الاختبار! هناك خطأ في إرجاع المخزون</h3>";
    }
    
} else {
    echo "<p style='color: red;'>✗ فشل في إلغاء الطلب: " . $result['message'] . "</p>";
}

echo "<br><hr>";
echo "<h4>ملاحظات:</h4>";
echo "<ul>";
echo "<li>هذا الاختبار يلغي طلباً حقيقياً من قاعدة البيانات</li>";
echo "<li>تأكد من أن لديك نسخة احتياطية قبل تشغيل الاختبار</li>";
echo "<li>يمكنك مراجعة ملف السجل (error log) لمزيد من التفاصيل</li>";
echo "</ul>";

echo "<br><a href='admin/orders.php'>العودة لإدارة الطلبات</a>";
?>
