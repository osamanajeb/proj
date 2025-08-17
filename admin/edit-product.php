<?php
/**
 * صفحة تحرير المنتج - لوحة تحكم المشرف
 * Edit product page - Admin panel
 */

$page_title = 'تحرير المنتج';

require_once '../config/config.php';
require_once '../classes/Product.php';
require_once '../classes/Category.php';

// إزالة التحقق من تسجيل دخول المشرف للسماح بالوصول المباشر
// if (!is_admin_logged_in()) {
//     redirect(SITE_URL . '/admin/login.php');
// }

// التحقق من وجود معرف المنتج
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect(SITE_URL . '/admin/products.php');
}

$product_id = (int)$_GET['id'];

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائنات الفئات
$productObj = new Product($db);
$categoryObj = new Category($db);

// الحصول على بيانات المنتج
$product = $productObj->getProductById($product_id);

if (!$product) {
    $_SESSION['message'] = ['text' => 'المنتج غير موجود', 'type' => 'error'];
    redirect(SITE_URL . '/admin/products.php');
}

// الحصول على الفئات
$categories = $categoryObj->getAllCategories();

$error_message = '';
$success_message = '';

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $price = (float)$_POST['price'];
    $discount_price = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
    $stock_quantity = (int)$_POST['stock_quantity'];
    $category_id = (int)$_POST['category_id'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // التحقق من البيانات المطلوبة
    if (empty($name) || empty($description) || $price <= 0 || $category_id <= 0) {
        $error_message = 'جميع الحقول مطلوبة والسعر يجب أن يكون أكبر من صفر';
    } elseif ($discount_price !== null && $discount_price >= $price) {
        $error_message = 'سعر الخصم يجب أن يكون أقل من السعر الأصلي';
    } elseif ($discount_price !== null && $discount_price < 0) {
        $error_message = 'سعر الخصم لا يمكن أن يكون سالباً';
    } else {
        // تحضير بيانات المنتج
        $product_data = [
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'discount_price' => $discount_price,
            'stock_quantity' => $stock_quantity,
            'category_id' => $category_id,
            'featured' => $is_featured
        ];
        
        // معالجة رفع الصورة الرئيسية
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_product_image($_FILES['main_image']);
            if ($upload_result['success']) {
                $product_data['main_image'] = $upload_result['filename'];
                
                // حذف الصورة القديمة
                if (!empty($product['main_image'])) {
                    $old_image_path = '../uploads/' . $product['main_image'];
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
            } else {
                $error_message = $upload_result['message'];
            }
        }
        
        if (empty($error_message)) {
            // تحديث المنتج
            $result = $productObj->updateProduct($product_id, $product_data);
            
            if ($result) {
                $_SESSION['message'] = ['text' => 'تم تحديث المنتج بنجاح', 'type' => 'success'];
                redirect(SITE_URL . '/admin/products.php');
            } else {
                $error_message = 'حدث خطأ أثناء تحديث المنتج';
            }
        }
    }
}

// تضمين رأس المشرف
include 'includes/admin_header.php';
?>

<div class="container-fluid">
    <!-- رأس الصفحة -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">تحرير المنتج</h1>
            <p class="text-muted">تحديث بيانات المنتج</p>
        </div>
        <div>
            <a href="products.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> العودة للمنتجات
            </a>
        </div>
    </div>

    <!-- عرض الرسائل -->
    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <!-- نموذج تحرير المنتج -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-edit"></i> تحديث بيانات المنتج</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="name" class="form-label">اسم المنتج *</label>
                                    <input type="text" id="name" name="name" class="form-control" 
                                           value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="category_id" class="form-label">الفئة *</label>
                                    <select id="category_id" name="category_id" class="form-select" required>
                                        <option value="">اختر الفئة</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo $category['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">وصف المنتج *</label>
                            <textarea id="description" name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="price" class="form-label">السعر *</label>
                                    <div class="input-group">
                                        <input type="number" id="price" name="price" class="form-control" 
                                               step="0.01" min="0" value="<?php echo $product['price']; ?>" required>
                                        <span class="input-group-text"><?php echo CURRENCY_SYMBOL; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="discount_price" class="form-label">السعر بعد الخصم</label>
                                    <div class="input-group">
                                        <input type="number" id="discount_price" name="discount_price" class="form-control"
                                               step="0.01" min="0" value="<?php echo $product['discount_price'] ?? ''; ?>"
                                               placeholder="اتركه فارغاً إذا لم يكن هناك خصم">
                                        <span class="input-group-text"><?php echo CURRENCY_SYMBOL; ?></span>
                                    </div>
                                    <small class="form-text text-muted">يجب أن يكون أقل من السعر الأصلي</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="stock_quantity" class="form-label">الكمية المتوفرة *</label>
                                    <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" 
                                           min="0" value="<?php echo $product['stock_quantity']; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <div class="form-check mt-4">
                                        <input type="checkbox" id="is_featured" name="is_featured" class="form-check-input"
                                               value="1" <?php echo (isset($product['featured']) && $product['featured']) ? 'checked' : ''; ?>>
                                        <label for="is_featured" class="form-check-label">منتج مميز</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="main_image" class="form-label">الصورة الرئيسية</label>
                            <input type="file" id="main_image" name="main_image" class="form-control" accept="image/*">
                            <small class="form-text text-muted">اترك فارغاً للاحتفاظ بالصورة الحالية</small>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> تحديث المنتج
                            </button>
                            <a href="products.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- معاينة المنتج -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-eye"></i> معاينة المنتج</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($product['main_image'])): ?>
                        <div class="product-preview-image mb-3">
                            <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($product['main_image']); ?>"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="img-fluid rounded">
                        </div>
                    <?php else: ?>
                        <div class="no-image-placeholder mb-3">
                            <i class="fas fa-image"></i>
                            <p>لا توجد صورة</p>
                        </div>
                    <?php endif; ?>
                    
                    <h6><?php echo htmlspecialchars($product['name']); ?></h6>
                    <p class="text-muted small"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                    <div class="price">
                        <?php if (!empty($product['discount_price'])): ?>
                            <span class="text-decoration-line-through text-muted"><?php echo format_price($product['price']); ?></span>
                            <strong class="text-danger"><?php echo format_price($product['discount_price']); ?></strong>
                            <small class="badge bg-success">
                                خصم <?php echo round((($product['price'] - $product['discount_price']) / $product['price']) * 100); ?>%
                            </small>
                        <?php else: ?>
                            <strong><?php echo format_price($product['price']); ?></strong>
                        <?php endif; ?>
                    </div>
                    <p class="stock">المخزون: <?php echo $product['stock_quantity']; ?> قطعة</p>
                    
                    <?php if (isset($product['featured']) && $product['featured']): ?>
                        <span class="badge bg-warning">منتج مميز</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.product-preview-image img {
    max-height: 200px;
    width: 100%;
    object-fit: cover;
}

.no-image-placeholder {
    height: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 5px;
    color: #6c757d;
}

.no-image-placeholder i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.form-actions {
    border-top: 1px solid #dee2e6;
    padding-top: 1rem;
    margin-top: 1rem;
}
</style>

<script>
// تحديث معاينة الأسعار عند التغيير
document.addEventListener('DOMContentLoaded', function() {
    const priceInput = document.getElementById('price');
    const discountPriceInput = document.getElementById('discount_price');

    function updatePricePreview() {
        const price = parseFloat(priceInput.value) || 0;
        const discountPrice = parseFloat(discountPriceInput.value) || 0;
        const priceContainer = document.querySelector('.price');

        if (discountPrice > 0 && discountPrice < price) {
            const discountPercent = Math.round(((price - discountPrice) / price) * 100);
            priceContainer.innerHTML = `
                <span class="text-decoration-line-through text-muted">${price.toFixed(2)} <?php echo CURRENCY_SYMBOL; ?></span>
                <strong class="text-danger">${discountPrice.toFixed(2)} <?php echo CURRENCY_SYMBOL; ?></strong>
                <small class="badge bg-success">خصم ${discountPercent}%</small>
            `;
        } else {
            priceContainer.innerHTML = `<strong>${price.toFixed(2)} <?php echo CURRENCY_SYMBOL; ?></strong>`;
        }
    }

    // إضافة مستمعي الأحداث
    priceInput.addEventListener('input', updatePricePreview);
    discountPriceInput.addEventListener('input', updatePricePreview);

    // التحقق من صحة سعر الخصم
    discountPriceInput.addEventListener('blur', function() {
        const price = parseFloat(priceInput.value) || 0;
        const discountPrice = parseFloat(this.value) || 0;

        if (discountPrice > 0 && discountPrice >= price) {
            alert('سعر الخصم يجب أن يكون أقل من السعر الأصلي');
            
        }
    });
});
</script>

<?php include 'includes/admin_footer.php'; ?>
