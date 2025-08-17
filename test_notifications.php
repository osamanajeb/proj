<?php
/**
 * صفحة اختبار الإشعارات
 * Notifications test page
 */

$page_title = 'اختبار الإشعارات';
require_once 'config/config.php';

// معالجة اختبار الإشعارات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_type = $_POST['message_type'] ?? 'info';
    $message_text = $_POST['message_text'] ?? 'رسالة تجريبية';
    
    $_SESSION['message'] = ['text' => $message_text, 'type' => $message_type];
    redirect(SITE_URL . '/test_notifications.php');
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-bell"></i> اختبار نظام الإشعارات</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">استخدم هذه الصفحة لاختبار نظام الإشعارات التلقائية</p>
                    
                    <form method="POST" action="">
                        <div class="form-group mb-3">
                            <label class="form-label">نوع الإشعار:</label>
                            <select name="message_type" class="form-select">
                                <option value="success">نجاح (يختفي بعد 3 ثوان)</option>
                                <option value="info">معلومات (يختفي بعد 5 ثوان)</option>
                                <option value="warning">تحذير (يختفي بعد 5 ثوان)</option>
                                <option value="error">خطأ (يختفي بعد 5 ثوان)</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">نص الرسالة:</label>
                            <input type="text" name="message_text" class="form-control" 
                                   value="هذه رسالة تجريبية للإشعارات" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> إرسال الإشعار
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <h5>اختبار الإشعارات بـ JavaScript:</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-success btn-sm" onclick="showAlert('تم الحفظ بنجاح!', 'success')">
                            إشعار نجاح
                        </button>
                        <button class="btn btn-info btn-sm" onclick="showAlert('معلومات مفيدة للمستخدم', 'info')">
                            إشعار معلومات
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="showAlert('تحذير: يرجى المراجعة', 'warning')">
                            إشعار تحذير
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="showAlert('حدث خطأ في النظام', 'danger')">
                            إشعار خطأ
                        </button>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5>ميزات نظام الإشعارات:</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="fas fa-check text-success"></i>
                            <strong>إخفاء تلقائي:</strong> إشعارات النجاح تختفي بعد 3 ثوان، والباقي بعد 5 ثوان
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check text-success"></i>
                            <strong>تأثيرات بصرية:</strong> انيميشن دخول وخروج سلس
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check text-success"></i>
                            <strong>أيقونات مميزة:</strong> كل نوع إشعار له أيقونة مناسبة
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check text-success"></i>
                            <strong>إغلاق يدوي:</strong> يمكن إغلاق الإشعار بالضغط على زر X
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check text-success"></i>
                            <strong>تصميم متجاوب:</strong> يعمل على جميع أحجام الشاشات
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check text-success"></i>
                            <strong>موضع ثابت:</strong> الإشعارات تظهر في أعلى يمين الشاشة
                        </li>
                    </ul>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i>
                        <strong>ملاحظة:</strong> هذا النظام يعمل في جميع صفحات الموقع ويدعم الإشعارات من PHP و JavaScript
                    </div>
                </div>
            </div>
            
            <!-- أمثلة على الاستخدام -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-code"></i> أمثلة على الاستخدام</h5>
                </div>
                <div class="card-body">
                    <h6>في PHP:</h6>
                    <pre class="bg-light p-3 rounded"><code>// إشعار نجاح
$_SESSION['message'] = ['text' => 'تم الحفظ بنجاح!', 'type' => 'success'];

// إشعار خطأ
$_SESSION['message'] = ['text' => 'حدث خطأ', 'type' => 'error'];

// إشعار معلومات
$_SESSION['message'] = ['text' => 'معلومات مفيدة', 'type' => 'info'];

// إشعار تحذير
$_SESSION['message'] = ['text' => 'تحذير مهم', 'type' => 'warning'];</code></pre>
                    
                    <h6 class="mt-3">في JavaScript:</h6>
                    <pre class="bg-light p-3 rounded"><code>// إشعار نجاح
showAlert('تم الحفظ بنجاح!', 'success');

// إشعار خطأ
showAlert('حدث خطأ', 'danger');

// إشعار معلومات
showAlert('معلومات مفيدة', 'info');

// إشعار تحذير
showAlert('تحذير مهم', 'warning');</code></pre>
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

.list-group-item {
    border: none;
    padding: 0.75rem 0;
}

.list-group-item i {
    margin-left: 0.5rem;
}

pre {
    font-size: 0.9rem;
    direction: ltr;
    text-align: left;
}

.btn {
    margin: 0.25rem;
}

.gap-2 {
    gap: 0.5rem !important;
}

.d-flex.gap-2 {
    display: flex;
    flex-wrap: wrap;
}
</style>

<?php include 'includes/footer.php'; ?>
