<?php
/**
 * إفراغ السلة
 * Clear cart
 */

header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../classes/Cart.php';

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
    exit;
}

try {
    // الحصول على اتصال قاعدة البيانات
    $db = getDBConnection();
    
    // إفراغ السلة
    $cartObj = new Cart($db);
    $result = $cartObj->clearCart();
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'تم إفراغ السلة بنجاح'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء إفراغ السلة']);
    }
    
} catch (Exception $e) {
    error_log("Error clearing cart: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
}
?>
