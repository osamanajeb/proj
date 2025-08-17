<?php
/**
 * الحصول على عدد المنتجات في السلة
 * Get cart items count
 */

header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../classes/Cart.php';

try {
    // الحصول على اتصال قاعدة البيانات
    $db = getDBConnection();
    
    // الحصول على عدد المنتجات في السلة
    $cartObj = new Cart($db);
    $cart_count = $cartObj->getCartCount();
    
    echo json_encode([
        'success' => true,
        'count' => $cart_count
    ]);
    
} catch (Exception $e) {
    error_log("Error getting cart count: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'count' => 0,
        'message' => 'حدث خطأ في النظام'
    ]);
}
?>
