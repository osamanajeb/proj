<?php
/**
 * اختبار الإخفاء التلقائي للإشعارات
 * Auto-dismiss notifications test
 */

$page_title = 'اختبار الإخفاء التلقائي';
require_once 'config/config.php';

// معالجة الاختبارات
if (isset($_GET['test'])) {
    switch ($_GET['test']) {
        case 'success':
            $_SESSION['message'] = ['text' => 'تم تسجيل الدخول بنجاح! (سيختفي بعد 3 ثوان)', 'type' => 'success'];
            break;
        case 'error':
            $_SESSION['message'] = ['text' => 'حدث خطأ في النظام (سيختفي بعد 5 ثوان)', 'type' => 'error'];
            break;
        case 'warning':
            $_SESSION['message'] = ['text' => 'تحذير: يرجى المراجعة (سيختفي بعد 5 ثوان)', 'type' => 'warning'];
            break;
        case 'info':
            $_SESSION['message'] = ['text' => 'معلومات مفيدة للمستخدم (سيختفي بعد 5 ثوان)', 'type' => 'info'];
            break;
    }
    redirect(SITE_URL . '/test_auto_dismiss.php');
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-clock"></i> اختبار الإخفاء التلقائي للإشعارات</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">اضغط على أي زر لاختبار الإخفاء التلقائي للإشعارات</p>
                    
                    <div class="d-grid gap-3">
                        <a href="?test=success" class="btn btn-success btn-lg">
                            <i class="fas fa-check-circle"></i> اختبار إشعار النجاح (3 ثوان)
                        </a>
                        
                        <a href="?test=error" class="btn btn-danger btn-lg">
                            <i class="fas fa-exclamation-circle"></i> اختبار إشعار الخطأ (5 ثوان)
                        </a>
                        
                        <a href="?test=warning" class="btn btn-warning btn-lg">
                            <i class="fas fa-exclamation-triangle"></i> اختبار إشعار التحذير (5 ثوان)
                        </a>
                        
                        <a href="?test=info" class="btn btn-info btn-lg">
                            <i class="fas fa-info-circle"></i> اختبار إشعار المعلومات (5 ثوان)
                        </a>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="alert alert-light">
                        <h6><i class="fas fa-info-circle"></i> كيف يعمل النظام:</h6>
                        <ul class="mb-0">
                            <li>إشعارات النجاح (الأخضر) تختفي بعد <strong>3 ثوان</strong></li>
                            <li>إشعارات الخطأ والتحذير والمعلومات تختفي بعد <strong>5 ثوان</strong></li>
                            <li>يمكن إغلاق الإشعار يدوياً بالضغط على زر <strong>×</strong></li>
                            <li>الإشعار يختفي تدريجياً مع تأثير الشفافية</li>
                        </ul>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="fas fa-home"></i> العودة للصفحة الرئيسية
                        </a>
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

.gap-3 {
    gap: 1rem;
}

.btn-lg {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.alert-light {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}
</style>

<?php include 'includes/footer.php'; ?>
