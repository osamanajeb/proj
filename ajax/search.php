<?php
/**
 * البحث المباشر عبر AJAX
 * Live search via AJAX
 */

header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../classes/Product.php';

// التحقق من وجود كلمة البحث
if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    echo json_encode(['success' => false, 'message' => 'كلمة البحث مطلوبة']);
    exit;
}

$search_query = sanitize_input($_GET['q']);

// التحقق من طول كلمة البحث
if (strlen($search_query) < 2) {
    echo json_encode(['success' => false, 'message' => 'كلمة البحث قصيرة جداً']);
    exit;
}

try {
    // الحصول على اتصال قاعدة البيانات
    $db = getDBConnection();
    
    // البحث في المنتجات
    $productObj = new Product($db);
    $products = $productObj->searchProducts($search_query, null, null, null);
    
    // تحديد عدد النتائج المعروضة
    $products = array_slice($products, 0, 10);
    
    // تنسيق النتائج
    $formatted_products = [];
    foreach ($products as $product) {
        $formatted_products[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'description' => substr($product['description'], 0, 100) . '...',
            'price' => $product['price'],
            'discount_price' => $product['discount_price'],
            'main_image' => $product['main_image'],
            'category_name' => $product['category_name'],
            'stock_quantity' => $product['stock_quantity'],
            'url' => SITE_URL . '/product.php?id=' . $product['id']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'products' => $formatted_products,
        'total' => count($formatted_products),
        'query' => $search_query
    ]);
    
} catch (Exception $e) {
    error_log("Search error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء البحث',
        'products' => []
    ]);
}
?>
