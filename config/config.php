<?php
/**
 * الإعدادات العامة للمتجر الإلكتروني
 * General configuration for the e-commerce store
 */

// بدء الجلسة
session_start();

// تضمين ملف قاعدة البيانات
require_once __DIR__ . '/database.php';

// إعدادات الموقع العامة
define('SITE_URL', 'http://localhost/proj');

// إعدادات افتراضية (يتم استبدالها بالإعدادات من قاعدة البيانات)
define('DEFAULT_SITE_NAME', 'متجر أسامة');
define('DEFAULT_ADMIN_EMAIL', 'admin@store.com');

// إعدادات الصور
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// إعدادات الأمان
define('HASH_ALGO', PASSWORD_DEFAULT);
define('SESSION_TIMEOUT', 3600); // ساعة واحدة

// إعدادات العملة
define('CURRENCY', 'ريال');
define('CURRENCY_SYMBOL', 'ر.س');

/**
 * دالة لجلب إعداد من قاعدة البيانات
 * Function to get setting from database
 */
function get_setting($key, $default = '') {
    static $settings_cache = [];
    static $cache_cleared = false;

    // مسح الكاش إذا تم طلب ذلك
    if (isset($_SESSION['clear_settings_cache']) && !$cache_cleared) {
        $settings_cache = [];
        $cache_cleared = true;
        unset($_SESSION['clear_settings_cache']);
    }

    // استخدام الكاش لتجنب استعلامات متكررة
    if (isset($settings_cache[$key])) {
        return $settings_cache[$key];
    }

    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $value = $result ? $result['setting_value'] : $default;
        $settings_cache[$key] = $value;
        return $value;
    } catch (Exception $e) {
        error_log("Error getting setting {$key}: " . $e->getMessage());
        return $default;
    }
}

/**
 * دالة للحصول على اسم المتجر
 * Function to get site name
 */
function get_site_name() {
    return get_setting('site_name', DEFAULT_SITE_NAME);
}

/**
 * دالة للحصول على البريد الإلكتروني للمشرف
 * Function to get admin email
 */
function get_admin_email() {
    return get_setting('admin_email', DEFAULT_ADMIN_EMAIL);
}

/**
 * دالة للحصول على رمز العملة
 * Function to get currency symbol
 */
function get_currency_symbol() {
    return get_setting('currency_symbol', CURRENCY_SYMBOL);
}

/**
 * دالة لمسح كاش الإعدادات
 * Function to clear settings cache
 */
function clear_settings_cache() {
    $_SESSION['clear_settings_cache'] = true;
}

/**
 * دالة لتنظيف البيانات المدخلة
 * Function to sanitize input data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * دالة للتحقق من تسجيل الدخول
 * Function to check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * دالة للتحقق من تسجيل دخول المشرف
 * Function to check if admin is logged in
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * دالة للتحقق من صلاحيات المشرف
 * Function to check if user is admin
 */
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * دالة لإعادة التوجيه
 * Function to redirect
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * دالة لعرض الرسائل
 * Function to display messages
 */
function display_message($message, $type = 'info') {
    if (!empty($message)) {
        $class = '';
        $icon = '';
        switch ($type) {
            case 'success':
                $class = 'alert-success';
                $icon = 'fas fa-check-circle';
                break;
            case 'error':
                $class = 'alert-danger';
                $icon = 'fas fa-exclamation-circle';
                break;
            case 'warning':
                $class = 'alert-warning';
                $icon = 'fas fa-exclamation-triangle';
                break;
            default:
                $class = 'alert-info';
                $icon = 'fas fa-info-circle';
        }

        // إنشاء معرف فريد للإشعار
        $alert_id = 'alert_' . uniqid();
        $timeout = ($type === 'success') ? 3000 : 5000; // 3 ثوان للنجاح، 5 ثوان للباقي

        echo '<div id="' . $alert_id . '" class="alert ' . $class . ' alert-dismissible fade show auto-dismiss" role="alert">';
        echo '<i class="' . $icon . '"></i> ' . $message;
        echo '<button type="button" class="btn-close" onclick="removeAlert(\'' . $alert_id . '\')">×</button>';
        echo '</div>';

        // JavaScript بسيط لإخفاء الإشعار
        echo '<script>';
        echo 'document.addEventListener("DOMContentLoaded", function() {';
        echo '    setTimeout(function() {';
        echo '        var alertElement = document.getElementById("' . $alert_id . '");';
        echo '        if (alertElement) {';
        echo '            alertElement.style.transition = "opacity 0.5s ease-out";';
        echo '            alertElement.style.opacity = "0";';
        echo '            setTimeout(function() {';
        echo '                if (alertElement.parentNode) {';
        echo '                    alertElement.parentNode.removeChild(alertElement);';
        echo '                }';
        echo '            }, 500);';
        echo '        }';
        echo '    }, ' . $timeout . ');';
        echo '});';
        echo 'function removeAlert(id) {';
        echo '    var alertElement = document.getElementById(id);';
        echo '    if (alertElement && alertElement.parentNode) {';
        echo '        alertElement.parentNode.removeChild(alertElement);';
        echo '    }';
        echo '}';
        echo '</script>';
    }
}

/**
 * دالة لتنسيق السعر
 * Function to format price
 */
function format_price($price) {
    return number_format($price, 2) . ' ' . get_currency_symbol();
}

/**
 * دالة لرفع صورة المنتج
 * Function to upload product image
 */
function upload_product_image($file) {
    $upload_dir = '../uploads/';

    // التحقق من وجود المجلد
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // التحقق من نوع الملف
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'نوع الملف غير مدعوم. يُسمح فقط بـ JPG, PNG, GIF'];
    }

    // التحقق من حجم الملف (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'حجم الملف كبير جداً. الحد الأقصى 5MB'];
    }

    // إنشاء اسم ملف فريد
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;

    // رفع الملف
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'message' => 'فشل في رفع الملف'];
    }
}

/**
 * دالة لتنسيق التاريخ
 * Function to format date
 */
function format_date($date) {
    return date('Y-m-d H:i', strtotime($date));
}

/**
 * دالة للحصول على معرف الجلسة للسلة
 * Function to get cart session ID
 */
function get_cart_session_id() {
    if (!isset($_SESSION['cart_session_id'])) {
        $_SESSION['cart_session_id'] = session_id();
    }
    return $_SESSION['cart_session_id'];
}

/**
 * دالة لحساب عدد المنتجات في السلة
 * Function to count cart items
 */
function get_cart_count() {
    $db = getDBConnection();
    $session_id = get_cart_session_id();
    
    if (is_logged_in()) {
        $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE session_id = ?");
        $stmt->execute([$session_id]);
    }
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ? $result['total'] : 0;
}

// تعيين المنطقة الزمنية
date_default_timezone_set('Asia/Riyadh');

// تعيين ترميز الصفحة
header('Content-Type: text/html; charset=UTF-8');
?>
