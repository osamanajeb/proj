<?php
/**
 * إلغاء الطلب
 * Cancel order
 */

header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../classes/Order.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
    exit;
}

// التحقق من البيانات المطلوبة
if (!isset($_POST['order_id']) || !is_numeric($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'معرف الطلب مطلوب']);
    exit;
}

$order_id = (int)$_POST['order_id'];

try {
    // الحصول على اتصال قاعدة البيانات
    $db = getDBConnection();
    
    // إلغاء الطلب
    $orderObj = new Order($db);
    $result = $orderObj->cancelOrder($order_id, $_SESSION['user_id']);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Error cancelling order: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
}
?>
