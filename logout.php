<?php
/**
 * صفحة تسجيل الخروج
 * Logout page
 */

require_once 'config/config.php';
require_once 'classes/User.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    redirect(SITE_URL . '/login.php');
}

// تسجيل الخروج
$db = getDBConnection();
$userObj = new User($db);
$result = $userObj->logout();

// حذف كوكيز التذكر
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// رسالة النجاح
$_SESSION['message'] = ['text' => $result['message'], 'type' => 'success'];

// إعادة التوجيه إلى الصفحة الرئيسية
redirect(SITE_URL . '/index.php');
?>
