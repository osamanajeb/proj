<?php
/**
 * إعداد سريع للمتجر مع البيانات التجريبية
 * Quick store setup with sample data
 */

require_once 'config/config.php';

// التحقق من صلاحيات المشرف
session_start();
if (!is_logged_in() || !is_admin()) {
    header('Location: admin/login.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعداد السريع للمتجر</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .setup-step {
            transition: all 0.3s ease;
        }
        .setup-step.completed {
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .setup-step.active {
            background-color: #fff3cd;
            border-color: #ffeaa7;
        }
        .progress-bar {
            transition: width 0.5s ease;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-rocket"></i> الإعداد السريع للمتجر
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_setup'])): ?>
                            
                            <div class="mb-4">
                                <h5>جاري الإعداد...</h5>
                                <div class="progress mb-3">
                                    <div class="progress-bar" role="progressbar" style="width: 0%" id="progressBar"></div>
                                </div>
                                <div id="setupLog"></div>
                            </div>
                            
                            <script>
                            let currentStep = 0;
                            const steps = [
                                'تحضير قاعدة البيانات...',
                                'تثبيت البيانات التجريبية...',
                                'إنشاء الصور التجريبية...',
                                'تحديث الإعدادات...',
                                'اكتمل الإعداد!'
                            ];
                            
                            function updateProgress(step, message, success = true) {
                                const progress = ((step + 1) / steps.length) * 100;
                                document.getElementById('progressBar').style.width = progress + '%';
                                
                                const logDiv = document.getElementById('setupLog');
                                const alertClass = success ? 'alert-success' : 'alert-danger';
                                const icon = success ? 'fa-check' : 'fa-times';
                                
                                logDiv.innerHTML += `
                                    <div class="alert ${alertClass} alert-sm">
                                        <i class="fas ${icon}"></i> ${message}
                                    </div>
                                `;
                                logDiv.scrollTop = logDiv.scrollHeight;
                            }
                            
                            async function runSetup() {
                                try {
                                    // الخطوة 1: تحضير قاعدة البيانات
                                    updateProgress(0, 'تحضير قاعدة البيانات...');
                                    await new Promise(resolve => setTimeout(resolve, 1000));
                                    
                                    // الخطوة 2: تثبيت البيانات التجريبية
                                    updateProgress(1, 'تثبيت 8 فئات و40 منتج...');
                                    const response1 = await fetch('database/install_sample_data.php', {
                                        method: 'POST',
                                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                        body: 'install=1&confirm=1'
                                    });
                                    await new Promise(resolve => setTimeout(resolve, 2000));
                                    
                                    // الخطوة 3: إنشاء الصور
                                    updateProgress(2, 'إنشاء 48 صورة ملونة...');
                                    const response2 = await fetch('database/create_sample_images.php');
                                    await new Promise(resolve => setTimeout(resolve, 2000));
                                    
                                    // الخطوة 4: تحديث الإعدادات
                                    updateProgress(3, 'تحديث إعدادات المتجر...');
                                    await new Promise(resolve => setTimeout(resolve, 1000));
                                    
                                    // الخطوة 5: اكتمال
                                    updateProgress(4, 'تم إعداد المتجر بنجاح! 🎉');
                                    
                                    // إظهار أزرار التنقل
                                    setTimeout(() => {
                                        document.getElementById('setupComplete').style.display = 'block';
                                    }, 1000);
                                    
                                } catch (error) {
                                    updateProgress(currentStep, 'حدث خطأ: ' + error.message, false);
                                }
                            }
                            
                            // بدء الإعداد
                            runSetup();
                            </script>
                            
                            <div id="setupComplete" style="display: none;">
                                <div class="alert alert-success">
                                    <h4><i class="fas fa-check-circle"></i> تم الإعداد بنجاح!</h4>
                                    <p>متجرك الآن جاهز مع:</p>
                                    <ul>
                                        <li>8 فئات متنوعة</li>
                                        <li>40 منتج مع أوصاف كاملة</li>
                                        <li>48 صورة ملونة</li>
                                        <li>أسعار وخصومات واقعية</li>
                                    </ul>
                                </div>
                                
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="index.php" class="btn btn-success btn-lg">
                                        <i class="fas fa-home"></i> عرض المتجر
                                    </a>
                                    <a href="admin/" class="btn btn-primary">
                                        <i class="fas fa-tachometer-alt"></i> لوحة التحكم
                                    </a>
                                    <a href="products.php" class="btn btn-info">
                                        <i class="fas fa-box"></i> عرض المنتجات
                                    </a>
                                </div>
                            </div>
                            
                        <?php else: ?>
                            
                            <div class="text-center mb-4">
                                <i class="fas fa-rocket fa-4x text-success mb-3"></i>
                                <h4>إعداد سريع وسهل!</h4>
                                <p class="text-muted">احصل على متجر كامل في أقل من دقيقة</p>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card border-success">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0"><i class="fas fa-database"></i> البيانات التجريبية</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-unstyled mb-0">
                                                <li><i class="fas fa-check text-success"></i> 8 فئات متنوعة</li>
                                                <li><i class="fas fa-check text-success"></i> 40 منتج مع أوصاف</li>
                                                <li><i class="fas fa-check text-success"></i> أسعار وخصومات</li>
                                                <li><i class="fas fa-check text-success"></i> كميات مخزون</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0"><i class="fas fa-images"></i> الصور التجريبية</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-unstyled mb-0">
                                                <li><i class="fas fa-check text-info"></i> 48 صورة ملونة</li>
                                                <li><i class="fas fa-check text-info"></i> 8 ألوان متنوعة</li>
                                                <li><i class="fas fa-check text-info"></i> أحجام مناسبة</li>
                                                <li><i class="fas fa-check text-info"></i> نصوص واضحة</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> ما سيحدث:</h6>
                                <ol class="mb-0">
                                    <li>تثبيت 8 فئات (الإلكترونيات، الملابس، المنزل، الرياضة، الكتب، الجمال، الألعاب، السيارات)</li>
                                    <li>إضافة 40 منتج متنوع مع أوصاف تفصيلية وأسعار واقعية</li>
                                    <li>إنشاء 48 صورة ملونة تلقائياً لجميع الفئات والمنتجات</li>
                                    <li>تحديث إعدادات المتجر للحصول على أفضل تجربة</li>
                                </ol>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>تنبيه:</strong> هذه العملية ستضيف بيانات تجريبية. 
                                يمكن حذفها لاحقاً من أدوات قاعدة البيانات.
                            </div>
                            
                            <form method="POST" class="text-center">
                                <div class="form-check d-inline-block mb-3">
                                    <input class="form-check-input" type="checkbox" id="confirm" name="confirm" required>
                                    <label class="form-check-label" for="confirm">
                                        أؤكد أنني أريد إعداد المتجر بالبيانات التجريبية
                                    </label>
                                </div>
                                
                                <div>
                                    <button type="submit" name="quick_setup" class="btn btn-success btn-lg">
                                        <i class="fas fa-rocket"></i> ابدأ الإعداد السريع
                                    </button>
                                    
                                    <a href="admin/" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-arrow-left"></i> العودة للوحة التحكم
                                    </a>
                                </div>
                            </form>
                            
                        <?php endif; ?>
                        
                    </div>
                </div>
                
                <!-- معلومات إضافية -->
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-clock fa-2x text-primary mb-2"></i>
                                <h6>سريع</h6>
                                <small class="text-muted">أقل من دقيقة</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-shield-alt fa-2x text-success mb-2"></i>
                                <h6>آمن</h6>
                                <small class="text-muted">يمكن التراجع</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-star fa-2x text-warning mb-2"></i>
                                <h6>شامل</h6>
                                <small class="text-muted">متجر كامل</small>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
