<?php
/**
 * حفظ إعداد واحد عبر AJAX
 * Save single setting via AJAX
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';

// التحقق من صلاحيات المشرف
if (!is_logged_in() || !is_admin()) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit;
}

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
    exit;
}

// التحقق من البيانات المطلوبة
if (!isset($_POST['setting_key']) || !isset($_POST['setting_value'])) {
    echo json_encode(['success' => false, 'message' => 'بيانات ناقصة']);
    exit;
}

$setting_key = sanitize_input($_POST['setting_key']);
$setting_value = $_POST['setting_value'];
$setting_type = $_POST['setting_type'] ?? 'text';

// قائمة الإعدادات المسموح بتحديثها
$allowed_settings = [
    'site_name' => 'text',
    'site_description' => 'text',
    'admin_email' => 'email',
    'contact_phone' => 'text',
    'contact_address' => 'text',
    'shipping_cost' => 'number',
    'free_shipping_threshold' => 'number',
    'tax_rate' => 'number',
    'currency_symbol' => 'text',
    'items_per_page' => 'number',
    'maintenance_mode' => 'boolean',
    'allow_registration' => 'boolean',
    'require_email_verification' => 'boolean'
];

// التحقق من أن الإعداد مسموح
if (!array_key_exists($setting_key, $allowed_settings)) {
    echo json_encode(['success' => false, 'message' => 'إعداد غير مسموح']);
    exit;
}

$setting_type = $allowed_settings[$setting_key];

try {
    $db = getDBConnection();
    
    // إنشاء جدول الإعدادات إذا لم يكن موجوداً
    $create_table_query = "
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(255) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type ENUM('text', 'number', 'boolean', 'email', 'url') DEFAULT 'text',
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $db->exec($create_table_query);
    
    // تنظيف القيمة حسب النوع
    switch ($setting_type) {
        case 'number':
            $setting_value = floatval($setting_value);
            break;
        case 'boolean':
            $setting_value = $setting_value ? '1' : '0';
            break;
        case 'email':
            if (!filter_var($setting_value, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'بريد إلكتروني غير صحيح']);
                exit;
            }
            break;
        default:
            $setting_value = sanitize_input($setting_value);
    }
    
    // حفظ أو تحديث الإعداد
    $stmt = $db->prepare("
        INSERT INTO settings (setting_key, setting_value, setting_type) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP
    ");
    
    if ($stmt->execute([$setting_key, $setting_value, $setting_type])) {
        // مسح كاش الإعدادات لضمان تحديث القيم
        clear_settings_cache();

        echo json_encode([
            'success' => true,
            'message' => 'تم حفظ الإعداد بنجاح',
            'setting_key' => $setting_key,
            'setting_value' => $setting_value
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل في حفظ الإعداد']);
    }
    
} catch (Exception $e) {
    error_log("Setting save error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
}
?>
