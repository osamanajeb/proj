<?php
/**
 * صفحة اختبار إشعار تسجيل الدخول
 * Login notification test page
 */

$page_title = 'اختبار إشعار تسجيل الدخول';
require_once 'config/config.php';

// محاكاة تسجيل دخول ناجح
if (isset($_GET['simulate_login'])) {
    $_SESSION['message'] = ['text' => 'تم تسجيل الدخول بنجاح! مرحباً بك في متجر أسامة', 'type' => 'success'];
    redirect(SITE_URL . '/test_login_notification.php');
}

// محاكاة تسجيل خروج
if (isset($_GET['simulate_logout'])) {
    $_SESSION['message'] = ['text' => 'تم تسجيل الخروج بنجاح. نراك قريباً!', 'type' => 'success'];
    redirect(SITE_URL . '/test_login_notification.php');
}

// محاكاة خطأ في تسجيل الدخول
if (isset($_GET['simulate_error'])) {
    $_SESSION['message'] = ['text' => 'خطأ في البريد الإلكتروني أو كلمة المرور', 'type' => 'error'];
    redirect(SITE_URL . '/test_login_notification.php');
}

// محاكاة تسجيل جديد
if (isset($_GET['simulate_register'])) {
    $_SESSION['message'] = ['text' => 'تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول', 'type' => 'success'];
    redirect(SITE_URL . '/test_login_notification.php');
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-user-check"></i> اختبار إشعارات تسجيل الدخول</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">اختبر الإشعارات التي تظهر عند تسجيل الدخول والخروج</p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-play-circle text-primary"></i> محاكاة العمليات:</h5>
                            <div class="d-grid gap-2">
                                <a href="?simulate_login" class="btn btn-success">
                                    <i class="fas fa-sign-in-alt"></i> محاكاة تسجيل دخول ناجح
                                </a>
                                <a href="?simulate_logout" class="btn btn-info">
                                    <i class="fas fa-sign-out-alt"></i> محاكاة تسجيل خروج
                                </a>
                                <a href="?simulate_register" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> محاكاة تسجيل جديد
                                </a>
                                <a href="?simulate_error" class="btn btn-danger">
                                    <i class="fas fa-exclamation-circle"></i> محاكاة خطأ في تسجيل الدخول
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5><i class="fas fa-info-circle text-info"></i> معلومات النظام:</h5>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> إشعارات النجاح تختفي بعد <strong>3 ثوان</strong></li>
                                <li><i class="fas fa-check text-success"></i> إشعارات الخطأ تختفي بعد <strong>5 ثوان</strong></li>
                                <li><i class="fas fa-check text-success"></i> يمكن إغلاق الإشعار يدوياً بالضغط على <strong>×</strong></li>
                                <li><i class="fas fa-check text-success"></i> الإشعارات تظهر في <strong>أعلى يمين الشاشة</strong></li>
                                <li><i class="fas fa-check text-success"></i> تأثيرات انيميشن سلسة للدخول والخروج</li>
                            </ul>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="row">
                        <div class="col-md-12">
                            <h5><i class="fas fa-cogs text-warning"></i> اختبارات إضافية:</h5>
                            <div class="btn-toolbar" role="toolbar">
                                <div class="btn-group me-2" role="group">
                                    <button class="btn btn-outline-success" onclick="showAlert('تم حفظ البيانات بنجاح!', 'success')">
                                        حفظ ناجح
                                    </button>
                                    <button class="btn btn-outline-info" onclick="showAlert('تم تحديث الملف الشخصي', 'info')">
                                        تحديث معلومات
                                    </button>
                                    <button class="btn btn-outline-warning" onclick="showAlert('يرجى التحقق من البيانات المدخلة', 'warning')">
                                        تحذير
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="showAlert('فشل في الاتصال بالخادم', 'danger')">
                                        خطأ في الشبكة
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="alert alert-light">
                        <h6><i class="fas fa-lightbulb text-warning"></i> نصائح للاستخدام:</h6>
                        <ul class="mb-0">
                            <li>استخدم إشعارات النجاح للعمليات المكتملة بنجاح</li>
                            <li>استخدم إشعارات الخطأ للأخطاء التي تحتاج تدخل المستخدم</li>
                            <li>استخدم إشعارات التحذير للتنبيهات المهمة</li>
                            <li>استخدم إشعارات المعلومات للرسائل العامة</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- عرض الكود المستخدم -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-code"></i> الكود المستخدم في النظام</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>في ملف login.php:</h6>
                            <pre class="bg-light p-3 rounded"><code>// عند نجاح تسجيل الدخول
$_SESSION['message'] = [
    'text' => 'تم تسجيل الدخول بنجاح!', 
    'type' => 'success'
];
redirect(SITE_URL . '/index.php');</code></pre>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>في ملف logout.php:</h6>
                            <pre class="bg-light p-3 rounded"><code>// عند تسجيل الخروج
$_SESSION['message'] = [
    'text' => 'تم تسجيل الخروج بنجاح', 
    'type' => 'success'
];
redirect(SITE_URL . '/index.php');</code></pre>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h6>في ملف register.php:</h6>
                            <pre class="bg-light p-3 rounded"><code>// عند نجاح التسجيل
$_SESSION['message'] = [
    'text' => 'تم التسجيل بنجاح!', 
    'type' => 'success'
];
redirect(SITE_URL . '/login.php');</code></pre>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>في JavaScript:</h6>
                            <pre class="bg-light p-3 rounded"><code>// إشعار فوري
showAlert('تم الحفظ!', 'success');

// إشعار خطأ
showAlert('حدث خطأ', 'danger');</code></pre>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- روابط مفيدة -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-link"></i> روابط مفيدة</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <a href="login.php" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-sign-in-alt"></i> صفحة تسجيل الدخول
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="register.php" class="btn btn-outline-success w-100 mb-2">
                                <i class="fas fa-user-plus"></i> صفحة التسجيل
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="test_notifications.php" class="btn btn-outline-info w-100 mb-2">
                                <i class="fas fa-bell"></i> اختبار الإشعارات العام
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border: none;
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom: none;
}

pre {
    font-size: 0.85rem;
    direction: ltr;
    text-align: left;
    max-height: 200px;
    overflow-y: auto;
}

.btn-toolbar .btn {
    margin: 0.25rem;
}

.list-unstyled li {
    padding: 0.25rem 0;
}

.d-grid {
    display: grid;
}

.gap-2 {
    gap: 0.5rem;
}

.me-2 {
    margin-left: 0.5rem;
}
</style>

<?php include 'includes/footer.php'; ?>
