<?php
/**
 * تثبيت البيانات التجريبية للمتجر
 * Install sample data for the store
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
    <title>تثبيت البيانات التجريبية</title>
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
                            <i class="fas fa-database"></i> تثبيت البيانات التجريبية للمتجر
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])): ?>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-spinner fa-spin"></i> جاري تثبيت البيانات التجريبية...
                            </div>
                            
                            <?php
                            try {
                                $db = getDBConnection();
                                
                                // قراءة ملف SQL
                                $sql_file = __DIR__ . '/sample_data.sql';
                                if (!file_exists($sql_file)) {
                                    throw new Exception('ملف البيانات التجريبية غير موجود');
                                }
                                
                                $sql_content = file_get_contents($sql_file);
                                
                                // تقسيم الاستعلامات
                                $queries = explode(';', $sql_content);
                                
                                $success_count = 0;
                                $error_count = 0;
                                $errors = [];
                                
                                foreach ($queries as $query) {
                                    $query = trim($query);
                                    if (empty($query) || strpos($query, '--') === 0) {
                                        continue;
                                    }
                                    
                                    try {
                                        $db->exec($query);
                                        $success_count++;
                                    } catch (Exception $e) {
                                        $error_count++;
                                        $errors[] = $e->getMessage();
                                    }
                                }
                                
                                if ($error_count == 0) {
                                    echo '<div class="alert alert-success">';
                                    echo '<i class="fas fa-check-circle"></i> تم تثبيت البيانات التجريبية بنجاح!';
                                    echo '<br><strong>عدد الاستعلامات المنفذة:</strong> ' . $success_count;
                                    echo '</div>';
                                } else {
                                    echo '<div class="alert alert-warning">';
                                    echo '<i class="fas fa-exclamation-triangle"></i> تم تثبيت البيانات مع بعض الأخطاء';
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
                                <h5>الخطوة التالية: إنشاء الصور التجريبية</h5>
                                <a href="create_sample_images.php" class="btn btn-info">
                                    <i class="fas fa-images"></i> إنشاء الصور التجريبية
                                </a>
                            </div>
                            
                        <?php else: ?>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>تحذير:</strong> هذه العملية ستضيف بيانات تجريبية إلى قاعدة البيانات.
                                تأكد من أن هذا ما تريده قبل المتابعة.
                            </div>
                            
                            <h5>ما سيتم إضافته:</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class="fas fa-tags text-primary"></i> الفئات
                                            </h6>
                                            <ul class="list-unstyled">
                                                <li>• الإلكترونيات</li>
                                                <li>• الملابس</li>
                                                <li>• المنزل والحديقة</li>
                                                <li>• الرياضة واللياقة</li>
                                                <li>• الكتب والقرطاسية</li>
                                                <li>• الجمال والعناية</li>
                                                <li>• الألعاب والهوايات</li>
                                                <li>• السيارات والدراجات</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class="fas fa-box text-success"></i> المنتجات
                                            </h6>
                                            <ul class="list-unstyled">
                                                <li>• <strong>40 منتج</strong> متنوع</li>
                                                <li>• أسعار متدرجة</li>
                                                <li>• خصومات على بعض المنتجات</li>
                                                <li>• كميات مخزون متنوعة</li>
                                                <li>• منتجات مميزة</li>
                                                <li>• أوصاف تفصيلية</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <form method="POST">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="confirm" name="confirm" required>
                                        <label class="form-check-label" for="confirm">
                                            أؤكد أنني أريد تثبيت البيانات التجريبية
                                        </label>
                                    </div>
                                    
                                    <button type="submit" name="install" class="btn btn-primary btn-lg">
                                        <i class="fas fa-download"></i> تثبيت البيانات التجريبية
                                    </button>
                                    
                                    <a href="../admin/" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-arrow-left"></i> العودة للوحة التحكم
                                    </a>
                                </form>
                            </div>
                            
                        <?php endif; ?>
                        
                        <hr class="mt-5">
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <i class="fas fa-database fa-3x text-primary mb-2"></i>
                                    <h6>بيانات شاملة</h6>
                                    <small class="text-muted">فئات ومنتجات متنوعة</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <i class="fas fa-images fa-3x text-success mb-2"></i>
                                    <h6>صور تجريبية</h6>
                                    <small class="text-muted">صور ملونة لجميع المنتجات</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <i class="fas fa-rocket fa-3x text-warning mb-2"></i>
                                    <h6>جاهز للاستخدام</h6>
                                    <small class="text-muted">متجر كامل في دقائق</small>
                                </div>
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
