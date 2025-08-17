<?php
/**
 * استيراد إعدادات المتجر
 * Import store settings
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';

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

// التحقق من وجود الملف
if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'لم يتم رفع الملف بشكل صحيح']);
    exit;
}

$file = $_FILES['import_file'];

// التحقق من نوع الملف
if ($file['type'] !== 'application/json' && pathinfo($file['name'], PATHINFO_EXTENSION) !== 'json') {
    echo json_encode(['success' => false, 'message' => 'يجب أن يكون الملف من نوع JSON']);
    exit;
}

// التحقق من حجم الملف (حد أقصى 1MB)
if ($file['size'] > 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'حجم الملف كبير جداً']);
    exit;
}

try {
    // قراءة محتوى الملف
    $json_content = file_get_contents($file['tmp_name']);
    if ($json_content === false) {
        throw new Exception('فشل في قراءة الملف');
    }
    
    // تحليل JSON
    $import_data = json_decode($json_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('ملف JSON غير صحيح: ' . json_last_error_msg());
    }
    
    // التحقق من بنية البيانات
    if (!isset($import_data['settings']) || !is_array($import_data['settings'])) {
        throw new Exception('بنية الملف غير صحيحة');
    }
    
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
    
    // قائمة الإعدادات المسموح باستيرادها
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
        'allow_registration' => 'boolean',
        'require_email_verification' => 'boolean'
        // ملاحظة: لا نستورد maintenance_mode لأسباب أمنية
    ];
    
    $imported_count = 0;
    $skipped_count = 0;
    $errors = [];
    
    $db->beginTransaction();
    
    foreach ($import_data['settings'] as $key => $setting_data) {
        // التحقق من أن الإعداد مسموح
        if (!array_key_exists($key, $allowed_settings)) {
            $skipped_count++;
            continue;
        }
        
        $value = $setting_data['value'] ?? '';
        $type = $allowed_settings[$key];
        
        // تنظيف القيمة حسب النوع
        try {
            switch ($type) {
                case 'number':
                    $value = floatval($value);
                    break;
                case 'boolean':
                    $value = $value ? '1' : '0';
                    break;
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "بريد إلكتروني غير صحيح للإعداد: $key";
                        continue 2;
                    }
                    break;
                default:
                    $value = sanitize_input($value);
            }
            
            // حفظ الإعداد
            $stmt = $db->prepare("
                INSERT INTO settings (setting_key, setting_value, setting_type) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP
            ");
            
            if ($stmt->execute([$key, $value, $type])) {
                $imported_count++;
            } else {
                $errors[] = "فشل في حفظ الإعداد: $key";
            }
            
        } catch (Exception $e) {
            $errors[] = "خطأ في معالجة الإعداد $key: " . $e->getMessage();
        }
    }
    
    $db->commit();
    
    // تحضير رسالة النتيجة
    $message = "تم استيراد $imported_count إعداد بنجاح";
    if ($skipped_count > 0) {
        $message .= "، تم تجاهل $skipped_count إعداد";
    }
    if (!empty($errors)) {
        $message .= ". أخطاء: " . implode(', ', array_slice($errors, 0, 3));
        if (count($errors) > 3) {
            $message .= " و" . (count($errors) - 3) . " أخطاء أخرى";
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'imported_count' => $imported_count,
        'skipped_count' => $skipped_count,
        'errors_count' => count($errors)
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Settings import error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الاستيراد: ' . $e->getMessage()]);
}
?>
