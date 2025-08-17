<?php
/**
 * صفحة تسجيل خروج المشرف
 * Admin logout page
 */

require_once '../config/config.php';

// إزالة جميع متغيرات الجلسة الخاصة بالمشرف
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);

// رسالة تأكيد تسجيل الخروج
$_SESSION['message'] = ['text' => 'تم تسجيل الخروج بنجاح', 'type' => 'success'];

// توجيه إلى صفحة تسجيل الدخول
redirect(SITE_URL . '/admin/login.php');
?>
