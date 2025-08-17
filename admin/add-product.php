<?php
/**
 * إضافة منتج جديد - لوحة التحكم
 * Add new product - Admin panel
 */

$page_title = 'إضافة منتج جديد';
$page_description = 'إضافة منتج جديد إلى المتجر';

require_once '../config/config.php';
require_once '../classes/Product.php';
require_once '../classes/Category.php';

// التحقق من صلاحيات المشرف
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . '/login.php');
}

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائنات الفئات
$productObj = new Product($db);
$categoryObj = new Category($db);

// الحصول على الفئات
$categories = $categoryObj->getAllCategories();

$error_message = '';
$success_message = '';

// معالجة إضافة المنتج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $price = (float)$_POST['price'];
    $discount_price = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
    $stock_quantity = (int)$_POST['stock_quantity'];
    $category_id = (int)$_POST['category_id'];
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // التحقق من البيانات
    if (empty($name) || empty($description) || $price <= 0 || $stock_quantity < 0 || empty($category_id)) {
        $error_message = 'يرجى ملء جميع الحقول المطلوبة بشكل صحيح';
    } elseif ($discount_price && $discount_price >= $price) {
        $error_message = 'سعر الخصم يجب أن يكون أقل من السعر الأصلي';
    } else {
        // معالجة رفع الصورة
        $main_image = '';
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/';
            $file_extension = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_extension, $allowed_extensions)) {
                $main_image = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $main_image;
                
                if (!move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_path)) {
                    $error_message = 'حدث خطأ أثناء رفع الصورة';
                }
            } else {
                $error_message = 'نوع الملف غير مدعوم. يرجى رفع صورة بصيغة JPG, PNG أو GIF';
            }
        }
        
        if (empty($error_message)) {
            $product_data = [
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'discount_price' => $discount_price,
                'stock_quantity' => $stock_quantity,
                'category_id' => $category_id,
                'main_image' => $main_image,
                'featured' => $featured
            ];
            
            $result = $productObj->addProduct($product_data);
            
            if ($result) {
                $_SESSION['message'] = ['text' => 'تم إضافة المنتج بنجاح', 'type' => 'success'];
                redirect(SITE_URL . '/admin/products.php');
            } else {
                $error_message = 'حدث خطأ أثناء إضافة المنتج';
            }
        }
    }
}

// تضمين رأس المشرف
include 'includes/admin_header.php';
?>

<div class="add-product">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-plus"></i> إضافة منتج جديد</h1>
            <a href="products.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> العودة للمنتجات
            </a>
        </div>
    </div>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="row">
            <div class="col-lg-8">
                <!-- معلومات المنتج الأساسية -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> معلومات المنتج</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name" class="form-label">اسم المنتج *</label>
                            <input type="text" id="name" name="name" class="form-control" 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label">وصف المنتج *</label>
                            <textarea id="description" name="description" class="form-control" 
                                      rows="5" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id" class="form-label">الفئة *</label>
                            <select id="category_id" name="category_id" class="form-select" required>
                                <option value="">اختر الفئة</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- التسعير والمخزون -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-dollar-sign"></i> التسعير والمخزون</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price" class="form-label">السعر الأصلي (ر.س) *</label>
                                    <input type="number" id="price" name="price" class="form-control" 
                                           step="0.01" min="0" 
                                           value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="discount_price" class="form-label">السعر بعد الخصم (ر.س)</label>
                                    <input type="number" id="discount_price" name="discount_price" class="form-control" 
                                           step="0.01" min="0" 
                                           value="<?php echo isset($_POST['discount_price']) ? $_POST['discount_price'] : ''; ?>">
                                    <small class="form-text text-muted">اتركه فارغاً إذا لم يكن هناك خصم</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="stock_quantity" class="form-label">كمية المخزون *</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" 
                                   min="0" 
                                   value="<?php echo isset($_POST['stock_quantity']) ? $_POST['stock_quantity'] : '0'; ?>" 
                                   required>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- صورة المنتج -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-image"></i> صورة المنتج</h5>
                    </div>
                    <div class="card-body">
                        <div class="image-upload-area">
                            <input type="file" id="main_image" name="main_image" accept="image/*" style="display: none;">
                            <div class="upload-content">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <p>انقر لاختيار صورة أو اسحبها هنا</p>
                                <small class="text-muted">JPG, PNG, GIF - حد أقصى 5MB</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- إعدادات إضافية -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-cog"></i> إعدادات إضافية</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check">
                            <input type="checkbox" id="featured" name="featured" class="form-check-input" 
                                   <?php echo (isset($_POST['featured'])) ? 'checked' : ''; ?>>
                            <label for="featured" class="form-check-label">منتج مميز</label>
                            <small class="form-text text-muted">سيظهر في قسم المنتجات المميزة</small>
                        </div>
                    </div>
                </div>
                
                <!-- أزرار الحفظ -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-success w-100 mb-2">
                            <i class="fas fa-save"></i> حفظ المنتج
                        </button>
                        <a href="products.php" class="btn btn-secondary w-100">
                            <i class="fas fa-times"></i> إلغاء
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// معاينة الصورة
document.getElementById('main_image').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const uploadArea = document.querySelector('.image-upload-area');
            const uploadContent = uploadArea.querySelector('.upload-content');
            
            // إنشاء معاينة الصورة
            const preview = document.createElement('div');
            preview.innerHTML = `
                <img src="${e.target.result}" style="max-width: 100%; max-height: 200px; object-fit: cover; border-radius: 8px;">
                <p class="mt-2 mb-0">${file.name}</p>
                <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removeImage()">
                    <i class="fas fa-trash"></i> إزالة
                </button>
            `;
            
            uploadContent.style.display = 'none';
            uploadArea.appendChild(preview);
        };
        reader.readAsDataURL(file);
    }
});

function removeImage() {
    document.getElementById('main_image').value = '';
    const uploadArea = document.querySelector('.image-upload-area');
    const preview = uploadArea.querySelector('div:last-child');
    const uploadContent = uploadArea.querySelector('.upload-content');
    
    if (preview && preview !== uploadContent) {
        preview.remove();
        uploadContent.style.display = 'block';
    }
}

// التحقق من سعر الخصم
document.getElementById('discount_price').addEventListener('input', function() {
    const originalPrice = parseFloat(document.getElementById('price').value) || 0;
    const discountPrice = parseFloat(this.value) || 0;
    
    if (discountPrice > 0 && discountPrice >= originalPrice) {
        this.setCustomValidity('سعر الخصم يجب أن يكون أقل من السعر الأصلي');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php
// تضمين تذييل المشرف
include 'includes/admin_footer.php';
?>
