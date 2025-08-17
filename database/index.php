<?php
/**
 * فهرس أدوات قاعدة البيانات
 * Database tools index
 */

require_once __DIR__ . '/../config/config.php';

// التحقق من صلاحيات المشرف
session_start();
if (!is_logged_in() || !is_admin()) {
    header('Location: ../admin/login.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أدوات قاعدة البيانات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-database"></i> أدوات قاعدة البيانات
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <div class="row">
                            <!-- تثبيت البيانات التجريبية -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 border-success">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-download"></i> تثبيت البيانات التجريبية
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p>إضافة فئات ومنتجات تجريبية مع صور ملونة لبدء تشغيل المتجر بسرعة.</p>
                                        
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success"></i> 8 فئات متنوعة</li>
                                            <li><i class="fas fa-check text-success"></i> 40 منتج مع أوصاف</li>
                                            <li><i class="fas fa-check text-success"></i> صور ملونة تلقائية</li>
                                            <li><i class="fas fa-check text-success"></i> أسعار وخصومات متنوعة</li>
                                        </ul>
                                        
                                        <div class="mt-auto">
                                            <a href="install_sample_data.php" class="btn btn-success w-100">
                                                <i class="fas fa-download"></i> تثبيت البيانات
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- إنشاء الصور التجريبية -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 border-info">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-images"></i> إنشاء الصور التجريبية
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p>إنشاء صور ملونة تجريبية لجميع الفئات والمنتجات.</p>
                                        
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-info"></i> صور بألوان متنوعة</li>
                                            <li><i class="fas fa-check text-info"></i> أحجام مناسبة للعرض</li>
                                            <li><i class="fas fa-check text-info"></i> نصوص واضحة</li>
                                            <li><i class="fas fa-check text-info"></i> تلقائية بالكامل</li>
                                        </ul>
                                        
                                        <div class="mt-auto">
                                            <a href="create_sample_images.php" class="btn btn-info w-100">
                                                <i class="fas fa-images"></i> إنشاء الصور
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- حذف البيانات التجريبية -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-trash"></i> حذف البيانات التجريبية
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p>حذف جميع البيانات التجريبية والصور المرفقة نهائياً.</p>
                                        
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-times text-danger"></i> حذف الفئات التجريبية</li>
                                            <li><i class="fas fa-times text-danger"></i> حذف المنتجات التجريبية</li>
                                            <li><i class="fas fa-times text-danger"></i> حذف الصور التجريبية</li>
                                            <li><i class="fas fa-shield-alt text-warning"></i> الحفاظ على البيانات الحقيقية</li>
                                        </ul>
                                        
                                        <div class="mt-auto">
                                            <a href="remove_sample_data.php" class="btn btn-danger w-100">
                                                <i class="fas fa-trash"></i> حذف البيانات
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- التوثيق -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="mb-0">
                                            <i class="fas fa-book"></i> التوثيق والمساعدة
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p>دليل شامل لاستخدام البيانات التجريبية وحل المشاكل.</p>
                                        
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-file-alt text-warning"></i> دليل الاستخدام</li>
                                            <li><i class="fas fa-question-circle text-warning"></i> حل المشاكل الشائعة</li>
                                            <li><i class="fas fa-cog text-warning"></i> متطلبات النظام</li>
                                            <li><i class="fas fa-lightbulb text-warning"></i> نصائح وإرشادات</li>
                                        </ul>
                                        
                                        <div class="mt-auto">
                                            <a href="README_SAMPLE_DATA.md" target="_blank" class="btn btn-warning w-100">
                                                <i class="fas fa-book"></i> عرض التوثيق
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h5><i class="fas fa-info-circle"></i> معلومات مهمة</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>قبل البدء:</h6>
                                            <ul>
                                                <li>تأكد من عمل نسخة احتياطية</li>
                                                <li>تحقق من صلاحيات مجلد uploads</li>
                                                <li>تأكد من تفعيل مكتبة GD في PHP</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>بعد التثبيت:</h6>
                                            <ul>
                                                <li>يمكن تعديل البيانات من لوحة التحكم</li>
                                                <li>يمكن استبدال الصور بصور حقيقية</li>
                                                <li>يمكن حذف البيانات التجريبية لاحقاً</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <a href="../admin/" class="btn btn-secondary btn-lg">
                                <i class="fas fa-arrow-left"></i> العودة للوحة التحكم
                            </a>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
