<?php
/**
 * حذف منتج من السلة
 * Remove product from cart
 */

header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../classes/Cart.php';

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
    exit;
}

// التحقق من البيانات المطلوبة
if (!isset($_POST['cart_id']) || !is_numeric($_POST['cart_id'])) {
    echo json_encode(['success' => false, 'message' => 'معرف السلة مطلوب']);
    exit;
}

$cart_id = (int)$_POST['cart_id'];

try {
    // الحصول على اتصال قاعدة البيانات
    $db = getDBConnection();
    
    // حذف المنتج من السلة
    $cartObj = new Cart($db);
    $result = $cartObj->removeFromCart($cart_id);
    
    if ($result) {
        // الحصول على عدد المنتجات الجديد في السلة
        $cart_count = $cartObj->getCartCount();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم حذف المنتج من السلة بنجاح',
            'cart_count' => $cart_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء حذف المنتج من السلة']);
    }
    
} catch (Exception $e) {
    error_log("Error removing from cart: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
}
?>
