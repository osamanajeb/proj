<?php
/**
 * إدارة المنتجات - لوحة التحكم
 * Products management - Admin panel
 */

$page_title = 'إدارة المنتجات';
$page_description = 'إدارة وتحرير المنتجات';

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

// معالجة الحذف
if (isset($_POST['delete_product']) && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $result = $productObj->deleteProduct($product_id);
    
    if ($result) {
        $_SESSION['message'] = ['text' => 'تم حذف المنتج بنجاح', 'type' => 'success'];
    } else {
        $_SESSION['message'] = ['text' => 'حدث خطأ أثناء حذف المنتج', 'type' => 'error'];
    }
    
    redirect(SITE_URL . '/admin/products.php');
}

// الحصول على المنتجات
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : null;
$low_stock = isset($_GET['low_stock']) ? true : false;

if ($search) {
    $products = $productObj->searchProducts($search, $category_filter);
} elseif ($category_filter) {
    $products = $productObj->getProductsByCategory($category_filter);
} else {
    $products = $productObj->getAllProducts();
}

// تصفية المنتجات منخفضة المخزون
if ($low_stock) {
    $products = array_filter($products, function($product) {
        return $product['stock_quantity'] <= 5;
    });
}

// الحصول على الفئات للتصفية
$categories = $categoryObj->getAllCategories();

// تضمين رأس المشرف
include 'includes/admin_header.php';
?>

<div class="products-management">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-box"></i> إدارة المنتجات</h1>
            <a href="add-product.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة منتج جديد
            </a>
        </div>
    </div>
    
    <!-- أدوات التصفية والبحث -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">البحث</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="ابحث عن المنتجات...">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">الفئة</label>
                    <select name="category" class="form-select">
                        <option value="">جميع الفئات</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">التصفية</label>
                    <div class="form-check">
                        <input type="checkbox" name="low_stock" value="1" class="form-check-input" 
                               <?php echo $low_stock ? 'checked' : ''; ?>>
                        <label class="form-check-label">المنتجات منخفضة المخزون</label>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> بحث
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- جدول المنتجات -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-list"></i> قائمة المنتجات (<?php echo count($products); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h5>لا توجد منتجات</h5>
                    <p>لم يتم العثور على منتجات تطابق معايير البحث</p>
                    <a href="add-product.php" class="btn btn-primary">إضافة منتج جديد</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>الصورة</th>
                                <th data-sort="name">اسم المنتج</th>
                                <th data-sort="category">الفئة</th>
                                <th data-sort="price">السعر</th>
                                <th data-sort="stock">المخزون</th>
                                <th data-sort="status">الحالة</th>
                                <th data-sort="created">تاريخ الإضافة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr data-id="<?php echo $product['id']; ?>">
                                    <td>
                                        <img src="../uploads/<?php echo $product['main_image'] ?: 'default.jpg'; ?>"
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="product-image-preview">
                                    </td>
                                    <td data-value="name">
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        <?php if ($product['featured']): ?>
                                            <span class="badge bg-warning ms-1">مميز</span>
                                        <?php endif; ?>
                                        <?php if ($product['discount_price']): ?>
                                            <span class="badge bg-success ms-1">خصم</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-value="category"><?php echo htmlspecialchars($product['category_name']); ?></td>
                                    <td data-value="price">
                                        <?php if ($product['discount_price']): ?>
                                            <span class="text-success fw-bold"><?php echo format_price($product['discount_price']); ?></span>
                                            <br>
                                            <small class="text-muted text-decoration-line-through"><?php echo format_price($product['price']); ?></small>
                                        <?php else: ?>
                                            <span class="fw-bold"><?php echo format_price($product['price']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-value="stock">
                                        <?php if ($product['stock_quantity'] == 0): ?>
                                            <span class="badge bg-danger">نفد المخزون</span>
                                        <?php elseif ($product['stock_quantity'] <= 5): ?>
                                            <span class="badge bg-warning"><?php echo $product['stock_quantity']; ?> قطعة</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><?php echo $product['stock_quantity']; ?> قطعة</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-value="status">
                                        <?php if ($product['status'] == 'active'): ?>
                                            <span class="badge bg-success">نشط</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">غير نشط</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-value="created"><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="../product.php?id=<?php echo $product['id']; ?>" 
                                               class="btn btn-sm btn-outline-info" target="_blank" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-product.php?id=<?php echo $product['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="تحرير">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteProduct(<?php echo $product['id']; ?>)" title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- نموذج تأكيد الحذف -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تأكيد الحذف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من حذف هذا المنتج؟ لا يمكن التراجع عن هذا الإجراء.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="product_id" id="deleteProductId">
                    <button type="submit" name="delete_product" class="btn btn-danger">حذف</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteProduct(productId) {
    document.getElementById('deleteProductId').value = productId;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// تحسين البحث المباشر
document.querySelector('input[name="search"]').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.data-table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>

<?php
// تضمين تذييل المشرف
include 'includes/admin_footer.php';
?>
