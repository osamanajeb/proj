<?php
/**
 * الاختبار النهائي للإشعارات التلقائية
 * Final auto-dismiss notifications test
 */

$page_title = 'الاختبار النهائي للإشعارات';
require_once 'config/config.php';

// معالجة الاختبارات
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'login_success':
            $_SESSION['message'] = ['text' => '✅ تم تسجيل الدخول بنجاح! مرحباً بك', 'type' => 'success'];
            break;
        case 'logout_success':
            $_SESSION['message'] = ['text' => '👋 تم تسجيل الخروج بنجاح. نراك قريباً!', 'type' => 'success'];
            break;
        case 'save_success':
            $_SESSION['message'] = ['text' => '💾 تم حفظ البيانات بنجاح', 'type' => 'success'];
            break;
        case 'login_error':
            $_SESSION['message'] = ['text' => '❌ خطأ في البريد الإلكتروني أو كلمة المرور', 'type' => 'error'];
            break;
        case 'network_error':
            $_SESSION['message'] = ['text' => '🌐 فشل في الاتصال بالخادم. يرجى المحاولة مرة أخرى', 'type' => 'error'];
            break;
        case 'validation_warning':
            $_SESSION['message'] = ['text' => '⚠️ يرجى التحقق من البيانات المدخلة قبل المتابعة', 'type' => 'warning'];
            break;
        case 'info_message':
            $_SESSION['message'] = ['text' => 'ℹ️ تم تحديث النظام. قد تحتاج لتحديث الصفحة', 'type' => 'info'];
            break;
    }
    redirect(SITE_URL . '/final_test_notifications.php');
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-vial"></i> الاختبار النهائي للإشعارات التلقائية</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>تعليمات الاختبار:</strong>
                        <ul class="mb-0 mt-2">
                            <li>اضغط على أي زر لاختبار نوع الإشعار</li>
                            <li>راقب الإشعار في أعلى يمين الشاشة</li>
                            <li>الإشعارات الخضراء تختفي بعد 3 ثوان</li>
                            <li>الإشعارات الأخرى تختفي بعد 5 ثوان</li>
                            <li>يمكنك إغلاق الإشعار يدوياً بالضغط على ×</li>
                        </ul>
                    </div>
                    
                    <div class="row">
                        <!-- إشعارات النجاح -->
                        <div class="col-md-6">
                            <h5><i class="fas fa-check-circle text-success"></i> إشعارات النجاح (3 ثوان)</h5>
                            <div class="d-grid gap-2 mb-4">
                                <a href="?action=login_success" class="btn btn-success">
                                    <i class="fas fa-sign-in-alt"></i> تسجيل دخول ناجح
                                </a>
                                <a href="?action=logout_success" class="btn btn-success">
                                    <i class="fas fa-sign-out-alt"></i> تسجيل خروج ناجح
                                </a>
                                <a href="?action=save_success" class="btn btn-success">
                                    <i class="fas fa-save"></i> حفظ ناجح
                                </a>
                            </div>
                        </div>
                        
                        <!-- إشعارات أخرى -->
                        <div class="col-md-6">
                            <h5><i class="fas fa-exclamation-triangle text-warning"></i> إشعارات أخرى (5 ثوان)</h5>
                            <div class="d-grid gap-2 mb-4">
                                <a href="?action=login_error" class="btn btn-danger">
                                    <i class="fas fa-times-circle"></i> خطأ في تسجيل الدخول
                                </a>
                                <a href="?action=network_error" class="btn btn-danger">
                                    <i class="fas fa-wifi"></i> خطأ في الشبكة
                                </a>
                                <a href="?action=validation_warning" class="btn btn-warning">
                                    <i class="fas fa-exclamation-triangle"></i> تحذير التحقق
                                </a>
                                <a href="?action=info_message" class="btn btn-info">
                                    <i class="fas fa-info-circle"></i> رسالة معلومات
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- اختبار JavaScript -->
                    <h5><i class="fas fa-code text-primary"></i> اختبار JavaScript المباشر</h5>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="btn-group-vertical w-100" role="group">
                                <button class="btn btn-outline-success mb-2" onclick="showSuccessAlert('🎉 تم بنجاح!')">
                                    JavaScript - نجاح
                                </button>
                                <button class="btn btn-outline-danger mb-2" onclick="showErrorAlert('💥 حدث خطأ!')">
                                    JavaScript - خطأ
                                </button>
                                <button class="btn btn-outline-warning mb-2" onclick="showWarningAlert('⚠️ تحذير!')">
                                    JavaScript - تحذير
                                </button>
                                <button class="btn btn-outline-info mb-2" onclick="showInfoAlert('📢 معلومات!')">
                                    JavaScript - معلومات
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- معلومات تقنية -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-cogs"></i> المعلومات التقنية:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> إخفاء تلقائي بـ CSS Transitions</li>
                                <li><i class="fas fa-check text-success"></i> دعم PHP و JavaScript</li>
                                <li><i class="fas fa-check text-success"></i> تصميم متجاوب</li>
                                <li><i class="fas fa-check text-success"></i> أيقونات Font Awesome</li>
                                <li><i class="fas fa-check text-success"></i> معرفات فريدة لكل إشعار</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-clock"></i> أوقات الإخفاء:</h6>
                            <ul class="list-unstyled">
                                <li><span class="badge bg-success">النجاح</span> 3 ثوان</li>
                                <li><span class="badge bg-danger">الخطأ</span> 5 ثوان</li>
                                <li><span class="badge bg-warning">التحذير</span> 5 ثوان</li>
                                <li><span class="badge bg-info">المعلومات</span> 5 ثوان</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home"></i> العودة للصفحة الرئيسية
                        </a>
                        <a href="login.php" class="btn btn-outline-secondary">
                            <i class="fas fa-sign-in-alt"></i> اختبار تسجيل الدخول الحقيقي
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- كود المثال -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-code"></i> أمثلة الكود</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>PHP:</h6>
                            <pre class="bg-light p-3 rounded"><code>// إشعار نجاح
$_SESSION['message'] = [
    'text' => 'تم بنجاح!', 
    'type' => 'success'
];

// إشعار خطأ
$_SESSION['message'] = [
    'text' => 'حدث خطأ!', 
    'type' => 'error'
];</code></pre>
                        </div>
                        <div class="col-md-6">
                            <h6>JavaScript:</h6>
                            <pre class="bg-light p-3 rounded"><code>// إشعار نجاح
showSuccessAlert('تم بنجاح!');

// إشعار خطأ
showErrorAlert('حدث خطأ!');

// إشعار مخصص
createAlert('رسالة', 'info', 3000);</code></pre>
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

.d-grid {
    display: grid;
}

.gap-2 {
    gap: 0.5rem;
}

pre {
    font-size: 0.85rem;
    direction: ltr;
    text-align: left;
}

.btn-group-vertical .btn {
    margin-bottom: 0.5rem;
}

.list-unstyled li {
    padding: 0.25rem 0;
}

.badge {
    margin-left: 0.5rem;
}
</style>

<?php include 'includes/footer.php'; ?>
