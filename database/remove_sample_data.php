<?php
/**
 * حذف البيانات التجريبية من المتجر
 * Remove sample data from the store
 */

require_once __DIR__ . '/../config/config.php';

// التحقق من صلاحيات المشرف
session_start();
if (!is_logged_in() || !is_admin()) {
    die('غير مصرح لك بالوصول لهذه الصفحة');
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حذف البيانات التجريبية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-trash"></i> حذف البيانات التجريبية
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove'])): ?>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-spinner fa-spin"></i> جاري حذف البيانات التجريبية...
                            </div>
                            
                            <?php
                            try {
                                $db = getDBConnection();
                                
                                $success_count = 0;
                                $error_count = 0;
                                $errors = [];
                                
                                // حذف المنتجات التجريبية
                                $sample_products = [
                                    'هاتف ذكي سامسونج جالاكسي S23',
                                    'لابتوب ديل XPS 13',
                                    'سماعات أبل AirPods Pro',
                                    'تابلت آيباد Air',
                                    'ساعة ذكية أبل واتش Series 8',
                                    'قميص رجالي قطني كلاسيكي',
                                    'فستان نسائي صيفي',
                                    'جينز رجالي كلاسيكي',
                                    'حقيبة يد نسائية جلدية',
                                    'حذاء رياضي للجري'
                                ];
                                
                                foreach ($sample_products as $product_name) {
                                    try {
                                        $stmt = $db->prepare("DELETE FROM products WHERE name = ?");
                                        $stmt->execute([$product_name]);
                                        $success_count++;
                                    } catch (Exception $e) {
                                        $error_count++;
                                        $errors[] = "فشل في حذف المنتج: $product_name";
                                    }
                                }
                                
                                // حذف الفئات التجريبية
                                $sample_categories = [
                                    'الإلكترونيات',
                                    'الملابس',
                                    'المنزل والحديقة',
                                    'الرياضة واللياقة',
                                    'الكتب والقرطاسية',
                                    'الجمال والعناية',
                                    'الألعاب والهوايات',
                                    'السيارات والدراجات'
                                ];
                                
                                foreach ($sample_categories as $category_name) {
                                    try {
                                        $stmt = $db->prepare("DELETE FROM categories WHERE name = ?");
                                        $stmt->execute([$category_name]);
                                        $success_count++;
                                    } catch (Exception $e) {
                                        $error_count++;
                                        $errors[] = "فشل في حذف الفئة: $category_name";
                                    }
                                }
                                
                                // حذف الصور التجريبية
                                $upload_dir = __DIR__ . '/../uploads/';
                                $sample_images = [
                                    'electronics.jpg', 'clothing.jpg', 'home-garden.jpg', 'sports.jpg',
                                    'books.jpg', 'beauty.jpg', 'toys.jpg', 'automotive.jpg',
                                    'samsung-s23.jpg', 'dell-xps13.jpg', 'airpods-pro.jpg', 'ipad-air.jpg',
                                    'apple-watch-s8.jpg', 'mens-shirt.jpg', 'summer-dress.jpg', 'mens-jeans.jpg',
                                    'leather-handbag.jpg', 'running-shoes.jpg'
                                ];
                                
                                $deleted_images = 0;
                                foreach ($sample_images as $image) {
                                    $image_path = $upload_dir . $image;
                                    if (file_exists($image_path) && unlink($image_path)) {
                                        $deleted_images++;
                                    }
                                }
                                
                                if ($error_count == 0) {
                                    echo '<div class="alert alert-success">';
                                    echo '<i class="fas fa-check-circle"></i> تم حذف البيانات التجريبية بنجاح!';
                                    echo '<br><strong>العمليات المنجزة:</strong> ' . $success_count;
                                    echo '<br><strong>الصور المحذوفة:</strong> ' . $deleted_images;
                                    echo '</div>';
                                } else {
                                    echo '<div class="alert alert-warning">';
                                    echo '<i class="fas fa-exclamation-triangle"></i> تم حذف البيانات مع بعض الأخطاء';
                                    echo '<br><strong>نجح:</strong> ' . $success_count;
                                    echo '<br><strong>فشل:</strong> ' . $error_count;
                                    echo '<br><strong>الأخطاء:</strong><ul>';
                                    foreach (array_slice($errors, 0, 5) as $error) {
                                        echo '<li>' . htmlspecialchars($error) . '</li>';
                                    }
                                    echo '</ul></div>';
                                }
                                
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger">';
                                echo '<i class="fas fa-times-circle"></i> حدث خطأ: ' . htmlspecialchars($e->getMessage());
                                echo '</div>';
                            }
                            ?>
                            
                            <div class="mt-4">
                                <a href="../admin/" class="btn btn-primary">
                                    <i class="fas fa-arrow-left"></i> العودة للوحة التحكم
                                </a>
                            </div>
                            
                        <?php else: ?>
                            
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>تحذير:</strong> هذه العملية ستحذف جميع البيانات التجريبية نهائياً.
                                تأكد من أن هذا ما تريده قبل المتابعة.
                            </div>
                            
                            <h5>ما سيتم حذفه:</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title text-danger">
                                                <i class="fas fa-tags"></i> الفئات التجريبية
                                            </h6>
                                            <ul class="list-unstyled">
                                                <li>• الإلكترونيات</li>
                                                <li>• الملابس</li>
                                                <li>• المنزل والحديقة</li>
                                                <li>• الرياضة واللياقة</li>
                                                <li>• وباقي الفئات...</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title text-danger">
                                                <i class="fas fa-box"></i> المنتجات التجريبية
                                            </h6>
                                            <ul class="list-unstyled">
                                                <li>• جميع المنتجات التجريبية</li>
                                                <li>• الصور المرفقة</li>
                                                <li>• الأوصاف والأسعار</li>
                                                <li>• بيانات المخزون</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-4">
                                <i class="fas fa-info-circle"></i>
                                <strong>ملاحظة:</strong> لن يتم حذف البيانات الحقيقية التي أضفتها بنفسك.
                                سيتم حذف البيانات التجريبية فقط.
                            </div>
                            
                            <div class="mt-4">
                                <form method="POST">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="confirm" name="confirm" required>
                                        <label class="form-check-label" for="confirm">
                                            أؤكد أنني أريد حذف جميع البيانات التجريبية نهائياً
                                        </label>
                                    </div>
                                    
                                    <button type="submit" name="remove" class="btn btn-danger btn-lg">
                                        <i class="fas fa-trash"></i> حذف البيانات التجريبية
                                    </button>
                                    
                                    <a href="../admin/" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-arrow-left"></i> إلغاء والعودة
                                    </a>
                                </form>
                            </div>
                            
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
