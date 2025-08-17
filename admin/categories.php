<?php
/**
 * إدارة الفئات - لوحة التحكم
 * Categories management - Admin panel
 */

$page_title = 'إدارة الفئات';
$page_description = 'إدارة وتحرير فئات المنتجات';

require_once '../config/config.php';
require_once '../classes/Category.php';

// التحقق من صلاحيات المشرف
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . '/login.php');
}

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائن الفئات
$categoryObj = new Category($db);

// معالجة الحذف
if (isset($_POST['delete_category']) && isset($_POST['category_id'])) {
    $category_id = (int)$_POST['category_id'];
    
    // التحقق من وجود منتجات في هذه الفئة
    $products_count = $categoryObj->getProductsCount($category_id);
    
    if ($products_count > 0) {
        $_SESSION['message'] = ['text' => 'لا يمكن حذف الفئة لأنها تحتوي على ' . $products_count . ' منتج', 'type' => 'error'];
    } else {
        $result = $categoryObj->deleteCategory($category_id);
        
        if ($result) {
            $_SESSION['message'] = ['text' => 'تم حذف الفئة بنجاح', 'type' => 'success'];
        } else {
            $_SESSION['message'] = ['text' => 'حدث خطأ أثناء حذف الفئة', 'type' => 'error'];
        }
    }
    
    redirect(SITE_URL . '/admin/categories.php');
}

// الحصول على الفئات
$categories = $categoryObj->getAllCategories();

// تضمين رأس المشرف
include 'includes/admin_header.php';
?>

<div class="categories-management">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-tags"></i> إدارة الفئات</h1>
            <a href="add-category.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة فئة جديدة
            </a>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($categories); ?></h3>
                    <p>إجمالي الفئات</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-content">
                    <?php
                    $total_products = 0;
                    foreach ($categories as $category) {
                        $total_products += $categoryObj->getProductsCount($category['id']);
                    }
                    ?>
                    <h3><?php echo $total_products; ?></h3>
                    <p>إجمالي المنتجات</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <?php
                    $active_categories = 0;
                    foreach ($categories as $category) {
                        if ($categoryObj->getProductsCount($category['id']) > 0) {
                            $active_categories++;
                        }
                    }
                    ?>
                    <h3><?php echo $active_categories; ?></h3>
                    <p>فئات نشطة</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <?php
                    $empty_categories = count($categories) - $active_categories;
                    ?>
                    <h3><?php echo $empty_categories; ?></h3>
                    <p>فئات فارغة</p>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول الفئات -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-list"></i> قائمة الفئات (<?php echo count($categories); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($categories)): ?>
                <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <h5>لا توجد فئات</h5>
                    <p>لم يتم إنشاء أي فئات بعد</p>
                    <a href="add-category.php" class="btn btn-primary">إضافة فئة جديدة</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>الصورة</th>
                                <th data-sort="name">اسم الفئة</th>
                                <th>الوصف</th>
                                <th data-sort="products">عدد المنتجات</th>
                                <th data-sort="created">تاريخ الإنشاء</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <?php $products_count = $categoryObj->getProductsCount($category['id']); ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($category['image'])): ?>
                                            <img src="<?php echo SITE_URL; ?>/uploads/categories/<?php echo $category['image']; ?>" 
                                                 alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                                 class="category-thumbnail">
                                        <?php else: ?>
                                            <div class="category-placeholder">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td data-value="name">
                                        <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($category['description'])): ?>
                                            <span class="description-preview" title="<?php echo htmlspecialchars($category['description']); ?>">
                                                <?php echo mb_substr(htmlspecialchars($category['description']), 0, 50); ?>
                                                <?php if (mb_strlen($category['description']) > 50): ?>...<?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">لا يوجد وصف</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-value="products">
                                        <span class="badge bg-<?php echo $products_count > 0 ? 'primary' : 'secondary'; ?>">
                                            <?php echo $products_count; ?> منتج
                                        </span>
                                    </td>
                                    <td data-value="created"><?php echo date('Y-m-d', strtotime($category['created_at'])); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="../category.php?id=<?php echo $category['id']; ?>" 
                                               class="btn btn-sm btn-outline-info" target="_blank" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-category.php?id=<?php echo $category['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="تحرير">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', <?php echo $products_count; ?>)" 
                                                    title="حذف" <?php echo $products_count > 0 ? 'disabled' : ''; ?>>
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
                <p>هل أنت متأكد من حذف الفئة <strong id="categoryName"></strong>؟</p>
                <div id="warningMessage" class="alert alert-warning" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i>
                    لا يمكن حذف هذه الفئة لأنها تحتوي على منتجات.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="category_id" id="categoryIdToDelete">
                    <button type="submit" name="delete_category" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash"></i> حذف
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteCategory(id, name, productsCount) {
    document.getElementById('categoryName').textContent = name;
    document.getElementById('categoryIdToDelete').value = id;
    
    const warningMessage = document.getElementById('warningMessage');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    
    if (productsCount > 0) {
        warningMessage.style.display = 'block';
        confirmBtn.style.display = 'none';
    } else {
        warningMessage.style.display = 'none';
        confirmBtn.style.display = 'inline-block';
    }
    
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php include 'includes/admin_footer.php'; ?>
