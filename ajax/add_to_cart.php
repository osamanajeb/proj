<?php
/**
 * إضافة منتج إلى السلة عبر AJAX
 * Add product to cart via AJAX
 */

header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../classes/Cart.php';
require_once '../classes/Product.php';

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
    exit;
}

// منع المشرفين من إضافة منتجات للسلة
if (is_admin()) {
    echo json_encode(['success' => false, 'message' => 'المشرفون لا يمكنهم استخدام سلة المشتريات']);
    exit;
}

// التحقق من البيانات المطلوبة
if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'معرف المنتج مطلوب']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// التحقق من صحة الكمية
if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'الكمية يجب أن تكون أكبر من صفر']);
    exit;
}

try {
    // منع الطلبات المتكررة السريعة
    $request_key = 'add_to_cart_' . $product_id . '_' . session_id();
    $last_request_time = $_SESSION[$request_key] ?? 0;
    $current_time = time();

    

    $_SESSION[$request_key] = $current_time;

    // الحصول على اتصال قاعدة البيانات
    $db = getDBConnection();
    
    // التحقق من وجود المنتج
    $productObj = new Product($db);
    $product = $productObj->getProductById($product_id);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'المنتج غير موجود']);
        exit;
    }
    
    // التحقق من توفر المنتج في المخزون
    if ($product['stock_quantity'] < $quantity) {
        echo json_encode([
            'success' => false, 
            'message' => 'الكمية المطلوبة غير متوفرة. المتوفر: ' . $product['stock_quantity']
        ]);
        exit;
    }
    
    // إضافة المنتج إلى السلة
    $cartObj = new Cart($db);
    $result = $cartObj->addToCart($product_id, $quantity);
    
    if ($result) {
        // الحصول على عدد المنتجات الجديد في السلة
        $cart_count = $cartObj->getCartCount();
        
        echo json_encode([
            'success' => true,
            'cart_count' => $cart_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء إضافة المنتج إلى السلة']);
    }
    
} catch (Exception $e) {
    error_log("Error adding to cart: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
}
?>
