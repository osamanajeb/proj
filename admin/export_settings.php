<?php
/**
 * تصدير إعدادات المتجر
 * Export store settings
 */

require_once __DIR__ . '/../config/config.php';

// التحقق من صلاحيات المشرف
if (!is_logged_in() || !is_admin()) {
    header('HTTP/1.0 403 Forbidden');
    exit('غير مصرح لك بالوصول');
}

try {
    $db = getDBConnection();
    
    // جلب جميع الإعدادات
    $stmt = $db->prepare("SELECT setting_key, setting_value, setting_type FROM settings ORDER BY setting_key");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // تحويل الإعدادات إلى مصفوفة مفاتيح => قيم
    $export_data = [];
    foreach ($settings as $setting) {
        $export_data[$setting['setting_key']] = [
            'value' => $setting['setting_value'],
            'type' => $setting['setting_type']
        ];
    }
    
    // إضافة معلومات التصدير
    $export_info = [
        'export_date' => date('Y-m-d H:i:s'),
        'site_name' => get_site_name(),
        'php_version' => PHP_VERSION,
        'settings_count' => count($export_data)
    ];
    
    $final_export = [
        'info' => $export_info,
        'settings' => $export_data
    ];
    
    // تحديد اسم الملف
    $filename = 'store_settings_' . date('Y-m-d_H-i-s') . '.json';
    
    // تحديد headers للتحميل
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // تصدير البيانات
    echo json_encode($final_export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Settings export error: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    echo json_encode(['error' => 'حدث خطأ أثناء تصدير الإعدادات']);
}
?>
