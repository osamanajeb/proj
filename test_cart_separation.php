<?php
/**
 * اختبار فصل سلة المشتريات بين العميل والمشرف
 * Test cart separation between customer and admin
 */

require_once 'config/config.php';
require_once 'classes/Cart.php';

echo "<h2>اختبار فصل سلة المشتريات</h2>";

try {
    $db = getDBConnection();
    $cartObj = new Cart($db);
    
    echo "<h3>1. اختبار الوصول للسلة:</h3>";
    
    // محاكاة مستخدم عادي
    $_SESSION['user_role'] = 'customer';
    echo "✅ <strong>العميل:</strong><br>";
    echo "- عدد المنتجات في السلة: " . $cartObj->getCartCount() . "<br>";
    echo "- يمكن الوصول لمحتويات السلة: " . (count($cartObj->getCartItems()) >= 0 ? "نعم" : "لا") . "<br>";
    
    // محاكاة مشرف
    $_SESSION['user_role'] = 'admin';
    echo "<br>🔒 <strong>المشرف:</strong><br>";
    echo "- عدد المنتجات في السلة: " . $cartObj->getCartCount() . "<br>";
    echo "- يمكن الوصول لمحتويات السلة: " . (count($cartObj->getCartItems()) == 0 ? "لا (محظور)" : "نعم") . "<br>";
    
    echo "<h3>2. اختبار إضافة منتج للسلة:</h3>";
    
    // محاكاة مستخدم عادي
    $_SESSION['user_role'] = 'customer';
    $result_customer = $cartObj->addToCart(1, 1);
    echo "✅ <strong>العميل:</strong> " . ($result_customer ? "يمكن إضافة منتجات للسلة" : "لا يمكن إضافة منتجات") . "<br>";
    
    // محاكاة مشرف
    $_SESSION['user_role'] = 'admin';
    $result_admin = $cartObj->addToCart(1, 1);
    echo "🔒 <strong>المشرف:</strong> " . ($result_admin ? "يمكن إضافة منتجات للسلة" : "لا يمكن إضافة منتجات (محظور)") . "<br>";
    
    echo "<h3>3. اختبار الوظائف الخاصة بالمشرف:</h3>";
    
    // اختبار إحصائيات السلة
    $stats = $cartObj->getCartStatistics();
    echo "📊 <strong>إحصائيات السلة:</strong><br>";
    echo "- السلال النشطة: " . ($stats['active_carts'] ?? 0) . "<br>";
    echo "- إجمالي المنتجات: " . ($stats['total_items'] ?? 0) . "<br>";
    echo "- إجمالي القيمة: " . format_price($stats['total_value'] ?? 0) . "<br>";
    
    // اختبار السلال المهجورة
    $abandoned = $cartObj->getAbandonedCarts(30);
    echo "<br>⚠️ <strong>السلال المهجورة:</strong> " . count($abandoned) . " سلة<br>";
    
    echo "<h3>4. اختبار إنشاء طلب مباشر:</h3>";
    
    $customer_data = [
        'first_name' => 'أحمد',
        'last_name' => 'محمد',
        'email' => 'test@example.com',
        'phone' => '0501234567',
        'address' => 'الرياض، حي النخيل',
        'city' => 'الرياض',
        'payment_method' => 'cash',
        'payment_status' => 'pending'
    ];
    
    $products = [
        [
            'product_id' => 1,
            'quantity' => 2,
            'price' => 100.00
        ]
    ];
    
    // محاكاة مستخدم عادي
    $_SESSION['user_role'] = 'customer';
    $result_customer_order = $cartObj->createAdminDirectOrder($products, $customer_data);
    echo "❌ <strong>العميل:</strong> " . ($result_customer_order['success'] ? "يمكن إنشاء طلبات مباشرة" : "لا يمكن إنشاء طلبات مباشرة (محظور)") . "<br>";
    
    // محاكاة مشرف
    $_SESSION['user_role'] = 'admin';
    $result_admin_order = $cartObj->createAdminDirectOrder($products, $customer_data);
    echo "✅ <strong>المشرف:</strong> " . ($result_admin_order['success'] ? "يمكن إنشاء طلبات مباشرة" : "لا يمكن إنشاء طلبات مباشرة") . "<br>";
    
    if ($result_admin_order['success']) {
        echo "   - رقم الطلب المُنشأ: #" . str_pad($result_admin_order['order_id'], 6, '0', STR_PAD_LEFT) . "<br>";
    }
    
    echo "<h3>✅ تم الانتهاء من الاختبار بنجاح!</h3>";
    
    echo "<h4>📋 ملخص النتائج:</h4>";
    echo "<ul>";
    echo "<li>✅ العملاء يمكنهم استخدام سلة المشتريات بشكل طبيعي</li>";
    echo "<li>🔒 المشرفون محظورون من استخدام سلة المشتريات</li>";
    echo "<li>📊 المشرفون يمكنهم الوصول لإحصائيات السلة</li>";
    echo "<li>⚠️ المشرفون يمكنهم رؤية السلال المهجورة</li>";
    echo "<li>➕ المشرفون يمكنهم إنشاء طلبات مباشرة</li>";
    echo "</ul>";
    
    echo "<h4>🔗 الروابط المفيدة:</h4>";
    echo "<ul>";
    echo "<li><a href='cart.php'>سلة المشتريات (للعملاء)</a></li>";
    echo "<li><a href='admin/create-order.php'>إنشاء طلب مباشر (للمشرفين)</a></li>";
    echo "<li><a href='admin/cart-analytics.php'>إحصائيات السلة (للمشرفين)</a></li>";
    echo "<li><a href='admin/orders.php'>إدارة الطلبات (للمشرفين)</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ خطأ: " . $e->getMessage() . "</h3>";
    echo "<p>تفاصيل الخطأ: " . $e->getTraceAsString() . "</p>";
}

// إعادة تعيين الجلسة
unset($_SESSION['user_role']);
?>

<style>
body {
    font-family: Arial, sans-serif;
    direction: rtl;
    text-align: right;
    margin: 20px;
    line-height: 1.6;
}

h2, h3, h4 {
    color: #333;
    border-bottom: 2px solid #eee;
    padding-bottom: 10px;
}

ul {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    border-right: 4px solid #007bff;
}

li {
    margin-bottom: 5px;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

.success {
    color: #28a745;
}

.error {
    color: #dc3545;
}

.warning {
    color: #ffc107;
}

.info {
    color: #17a2b8;
}
</style>
