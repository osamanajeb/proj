<?php
/**
 * فحص حالة البيانات في المتجر
 * Check data status in the store
 */

require_once 'config/config.php';

try {
    $db = getDBConnection();
    
    // فحص الفئات
    $stmt = $db->query("SELECT COUNT(*) as count FROM categories WHERE status = 'active'");
    $categories_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // فحص المنتجات
    $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
    $products_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // فحص المنتجات المميزة
    $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE is_featured = 1 AND status = 'active'");
    $featured_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // فحص المنتجات بخصم
    $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE discount_price IS NOT NULL AND discount_price > 0 AND status = 'active'");
    $discount_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // فحص الصور
    $upload_dir = __DIR__ . '/uploads/';
    $images_count = 0;
    if (is_dir($upload_dir)) {
        $files = scandir($upload_dir);
        foreach ($files as $file) {
            if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
                $images_count++;
            }
        }
    }
    
    // فحص وجود البيانات التجريبية
    $sample_categories = ['الإلكترونيات', 'الملابس', 'المنزل والحديقة'];
    $sample_found = 0;
    foreach ($sample_categories as $cat) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM categories WHERE name = ?");
        $stmt->execute([$cat]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
            $sample_found++;
        }
    }
    
    $has_sample_data = $sample_found >= 2;
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حالة البيانات - <?php echo get_site_name(); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-chart-bar"></i> حالة البيانات في المتجر
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> خطأ في الاتصال: <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php else: ?>
                            
                            <!-- حالة البيانات التجريبية -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <?php if ($has_sample_data): ?>
                                        <div class="alert alert-success">
                                            <h5><i class="fas fa-check-circle"></i> البيانات التجريبية مثبتة</h5>
                                            <p class="mb-0">تم العثور على البيانات التجريبية في المتجر.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <h5><i class="fas fa-exclamation-triangle"></i> البيانات التجريبية غير مثبتة</h5>
                                            <p class="mb-0">المتجر فارغ أو يحتوي على بيانات قليلة. يُنصح بتثبيت البيانات التجريبية.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- إحصائيات البيانات -->
                            <div class="row mb-4">
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-primary text-white text-center">
                                        <div class="card-body">
                                            <h2><?php echo $categories_count; ?></h2>
                                            <p class="mb-0">الفئات</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-success text-white text-center">
                                        <div class="card-body">
                                            <h2><?php echo $products_count; ?></h2>
                                            <p class="mb-0">المنتجات</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-warning text-white text-center">
                                        <div class="card-body">
                                            <h2><?php echo $featured_count; ?></h2>
                                            <p class="mb-0">منتجات مميزة</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-info text-white text-center">
                                        <div class="card-body">
                                            <h2><?php echo $images_count; ?></h2>
                                            <p class="mb-0">الصور</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- تفاصيل إضافية -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6><i class="fas fa-tags"></i> تفاصيل الفئات</h6>
                                        </div>
                                        <div class="card-body">
                                            <?php
                                            $stmt = $db->query("SELECT name FROM categories WHERE status = 'active' ORDER BY name LIMIT 5");
                                            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            ?>
                                            <?php if (empty($categories)): ?>
                                                <p class="text-muted">لا توجد فئات</p>
                                            <?php else: ?>
                                                <ul class="list-unstyled mb-0">
                                                    <?php foreach ($categories as $cat): ?>
                                                        <li><i class="fas fa-tag text-primary"></i> <?php echo htmlspecialchars($cat['name']); ?></li>
                                                    <?php endforeach; ?>
                                                    <?php if ($categories_count > 5): ?>
                                                        <li class="text-muted">... و <?php echo $categories_count - 5; ?> فئات أخرى</li>
                                                    <?php endif; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6><i class="fas fa-box"></i> تفاصيل المنتجات</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row text-center">
                                                <div class="col-6">
                                                    <h4 class="text-warning"><?php echo $featured_count; ?></h4>
                                                    <small>مميزة</small>
                                                </div>
                                                <div class="col-6">
                                                    <h4 class="text-danger"><?php echo $discount_count; ?></h4>
                                                    <small>بخصم</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        <?php endif; ?>
                        
                        <!-- الإجراءات -->
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-cogs"></i> الإجراءات المتاحة</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2 d-md-flex">
                                    <?php if (!$has_sample_data): ?>
                                        <a href="database/install_sample_data.php" class="btn btn-success btn-lg">
                                            <i class="fas fa-download"></i> تثبيت البيانات التجريبية
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($images_count < 10): ?>
                                        <a href="database/create_sample_images.php" class="btn btn-info">
                                            <i class="fas fa-images"></i> إنشاء الصور
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="admin/" class="btn btn-primary">
                                        <i class="fas fa-tachometer-alt"></i> لوحة التحكم
                                    </a>
                                    
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-home"></i> الصفحة الرئيسية
                                    </a>
                                    
                                    <button onclick="location.reload()" class="btn btn-outline-primary">
                                        <i class="fas fa-sync"></i> تحديث
                                    </button>
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
