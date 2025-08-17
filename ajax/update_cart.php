<?php
/**
 * تحديث كمية المنتج في السلة
 * Update cart item quantity
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

if (!isset($_POST['quantity']) || !is_numeric($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'الكمية مطلوبة']);
    exit;
}

$cart_id = (int)$_POST['cart_id'];
$quantity = (int)$_POST['quantity'];

try {
    // الحصول على اتصال قاعدة البيانات
    $db = getDBConnection();
    
    // تحديث كمية المنتج في السلة
    $cartObj = new Cart($db);
    $result = $cartObj->updateCartItem($cart_id, $quantity);
    
    if ($result) {
        // الحصول على عدد المنتجات الجديد في السلة
        $cart_count = $cartObj->getCartCount();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث السلة بنجاح',
            'cart_count' => $cart_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء تحديث السلة']);
    }
    
} catch (Exception $e) {
    error_log("Error updating cart: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
}
?>
