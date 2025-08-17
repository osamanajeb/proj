<?php
/**
 * تحرير فئة - لوحة التحكم
 * Edit category - Admin panel
 */

$page_title = 'تحرير فئة';
$page_description = 'تحرير معلومات الفئة';

require_once '../config/config.php';
require_once '../classes/Category.php';

// التحقق من صلاحيات المشرف
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . '/login.php');
}

// التحقق من وجود معرف الفئة
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = ['text' => 'معرف الفئة غير صحيح', 'type' => 'error'];
    redirect(SITE_URL . '/admin/categories.php');
}

$category_id = (int)$_GET['id'];

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائن الفئات
$categoryObj = new Category($db);

// الحصول على بيانات الفئة
$category = $categoryObj->getCategoryById($category_id);

if (!$category) {
    $_SESSION['message'] = ['text' => 'الفئة غير موجودة', 'type' => 'error'];
    redirect(SITE_URL . '/admin/categories.php');
}

$error_message = '';
$success_message = '';

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $image = $category['image']; // الاحتفاظ بالصورة الحالية

    // التحقق من صحة البيانات
    if (empty($name)) {
        $error_message = 'اسم الفئة مطلوب';
    } else {
        // معالجة رفع الصورة الجديدة
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = '../uploads/categories/';
            
            // إنشاء المجلد إذا لم يكن موجوداً
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // حذف الصورة القديمة
                    if (!empty($category['image']) && file_exists($upload_dir . $category['image'])) {
                        unlink($upload_dir . $category['image']);
                    }
                    $image = $new_filename;
                } else {
                    $error_message = 'فشل في رفع الصورة';
                }
            } else {
                $error_message = 'نوع الملف غير مدعوم. يرجى رفع صورة بصيغة JPG, PNG أو GIF';
            }
        }

        // تحديث الفئة إذا لم تكن هناك أخطاء
        if (empty($error_message)) {
            $category_data = [
                'name' => $name,
                'description' => $description,
                'image' => $image
            ];

            if ($categoryObj->updateCategory($category_id, $category_data)) {
                $_SESSION['message'] = ['text' => 'تم تحديث الفئة بنجاح', 'type' => 'success'];
                redirect(SITE_URL . '/admin/categories.php');
            } else {
                $error_message = 'حدث خطأ أثناء تحديث الفئة';
            }
        }
    }
}

// تضمين رأس المشرف
include 'includes/admin_header.php';
?>

<div class="edit-category">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-edit"></i> تحرير فئة: <?php echo htmlspecialchars($category['name']); ?></h1>
            <a href="categories.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> العودة للفئات
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-tag"></i> معلومات الفئة</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="name" class="form-label">اسم الفئة <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($category['name']); ?>" 
                                           required>
                                    <div class="invalid-feedback">
                                        يرجى إدخال اسم الفئة
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">وصف الفئة</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" 
                                              placeholder="وصف مختصر للفئة..."><?php echo htmlspecialchars($category['description']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="image" class="form-label">صورة الفئة</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i>
                                        الصيغ المدعومة: JPG, PNG, GIF. الحد الأقصى: 2MB. اتركه فارغاً للاحتفاظ بالصورة الحالية.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> حفظ التغييرات
                                    </button>
                                    <a href="categories.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> إلغاء
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- الصورة الحالية -->
            <div class="card">
                <div class="card-header">
                    <h6><i class="fas fa-image"></i> الصورة الحالية</h6>
                </div>
                <div class="card-body text-center">
                    <div id="currentImage" class="current-image">
                        <?php if (!empty($category['image'])): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/categories/<?php echo $category['image']; ?>" 
                                 alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                 class="img-fluid rounded">
                        <?php else: ?>
                            <div class="no-image">
                                <i class="fas fa-image"></i>
                                <p>لا توجد صورة</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- معاينة الصورة الجديدة -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-eye"></i> معاينة الصورة الجديدة</h6>
                </div>
                <div class="card-body text-center">
                    <div id="imagePreview" class="image-preview">
                        <i class="fas fa-upload"></i>
                        <p>اختر صورة جديدة للمعاينة</p>
                    </div>
                </div>
            </div>

            <!-- إحصائيات الفئة -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-chart-bar"></i> إحصائيات الفئة</h6>
                </div>
                <div class="card-body">
                    <?php
                    $products_count = $categoryObj->getProductsCount($category_id);
                    ?>
                    <div class="stat-item">
                        <span class="stat-label">عدد المنتجات:</span>
                        <span class="stat-value"><?php echo $products_count; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">تاريخ الإنشاء:</span>
                        <span class="stat-value"><?php echo date('Y-m-d', strtotime($category['created_at'])); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">آخر تحديث:</span>
                        <span class="stat-value"><?php echo date('Y-m-d', strtotime($category['updated_at'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- روابط سريعة -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-link"></i> روابط سريعة</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="../category.php?id=<?php echo $category['id']; ?>" 
                           class="btn btn-outline-info btn-sm" target="_blank">
                            <i class="fas fa-eye"></i> عرض الفئة في الموقع
                        </a>
                        <?php if ($products_count > 0): ?>
                            <a href="products.php?category=<?php echo $category['id']; ?>" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-box"></i> عرض منتجات الفئة
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.current-image img {
    max-width: 100%;
    max-height: 200px;
}

.no-image {
    padding: 2rem;
    color: #999;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.no-image i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.image-preview {
    width: 100%;
    height: 200px;
    border: 2px dashed #ddd;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #999;
    background-color: #f8f9fa;
}

.image-preview img {
    max-width: 100%;
    max-height: 100%;
    border-radius: 4px;
}

.image-preview i {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-value {
    font-weight: bold;
    color: #007bff;
}

.form-actions {
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.form-actions .btn {
    margin-left: 0.5rem;
}
</style>

<script>
// معاينة الصورة الجديدة
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('imagePreview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="معاينة الصورة الجديدة">';
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '<i class="fas fa-upload"></i><p>اختر صورة جديدة للمعاينة</p>';
    }
});

// التحقق من صحة النموذج
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php include 'includes/admin_footer.php'; ?>
