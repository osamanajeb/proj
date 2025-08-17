<?php
/**
 * تنبيهات المخزون المنخفض
 * Low Stock Alerts
 */

$page_title = 'تنبيهات المخزون المنخفض';

require_once '../config/config.php';
require_once '../classes/Product.php';

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائن المنتج
$productObj = new Product($db);

// الحصول على المنتجات منخفضة المخزون
function getLowStockProducts($threshold = 10) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT 
            p.id,
            p.name,
            p.price,
            p.discount_price,
            p.stock_quantity,
            c.name as category_name,
            (p.stock_quantity * p.price) as stock_value
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.stock_quantity <= ? 
        AND p.status = 'active'
        ORDER BY p.stock_quantity ASC, p.name ASC
    ");
    $stmt->execute([$threshold]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// الحصول على إحصائيات المخزون
function getStockStatistics() {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_products,
            SUM(CASE WHEN stock_quantity <= 5 THEN 1 ELSE 0 END) as critical_stock,
            SUM(CASE WHEN stock_quantity <= 10 AND stock_quantity > 5 THEN 1 ELSE 0 END) as low_stock,
            SUM(CASE WHEN stock_quantity > 10 THEN 1 ELSE 0 END) as normal_stock,
            SUM(stock_quantity * price) as total_stock_value
        FROM products 
        WHERE status = 'active'
    ");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$threshold = isset($_GET['threshold']) ? (int)$_GET['threshold'] : 10;
$low_stock_products = getLowStockProducts($threshold);
$stock_stats = getStockStatistics();

require_once 'includes/admin_header.php';
?>

<div class="container-fluid">
    <!-- عنوان الصفحة -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-exclamation-triangle text-warning"></i> تنبيهات المخزون المنخفض
        </h1>
        <div class="btn-group">
            <a href="reports.php?report_type=inventory" class="btn btn-outline-primary">
                <i class="fas fa-chart-bar"></i> تقرير المخزون الكامل
            </a>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> طباعة
            </button>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                مخزون حرج (≤ 5)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stock_stats['critical_stock']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                مخزون منخفض (6-10)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stock_stats['low_stock']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                مخزون طبيعي (>10)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stock_stats['normal_stock']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                قيمة المخزون الإجمالية
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo format_price($stock_stats['total_stock_value']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- فلتر العتبة -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter"></i> فلتر العتبة</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="threshold" class="form-label">عتبة المخزون المنخفض</label>
                    <select id="threshold" name="threshold" class="form-select">
                        <option value="5" <?php echo $threshold == 5 ? 'selected' : ''; ?>>5 قطع أو أقل</option>
                        <option value="10" <?php echo $threshold == 10 ? 'selected' : ''; ?>>10 قطع أو أقل</option>
                        <option value="20" <?php echo $threshold == 20 ? 'selected' : ''; ?>>20 قطعة أو أقل</option>
                        <option value="50" <?php echo $threshold == 50 ? 'selected' : ''; ?>>50 قطعة أو أقل</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="fas fa-search"></i> تطبيق
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول المنتجات منخفضة المخزون -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> المنتجات التي تحتاج إعادة تموين (<?php echo count($low_stock_products); ?> منتج)
            </h6>
        </div>
        <div class="card-body">
            <?php if (!empty($low_stock_products)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>رقم المنتج</th>
                            <th>اسم المنتج</th>
                            <th>الفئة</th>
                            <th>السعر</th>
                            <th>الكمية المتبقية</th>
                            <th>قيمة المخزون</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock_products as $product): ?>
                        <tr class="<?php echo $product['stock_quantity'] <= 5 ? 'table-danger' : 'table-warning'; ?>">
                            <td><?php echo $product['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'غير محدد'); ?></td>
                            <td>
                                <?php if ($product['discount_price']): ?>
                                    <span class="text-decoration-line-through text-muted"><?php echo format_price($product['price']); ?></span><br>
                                    <strong class="text-danger"><?php echo format_price($product['discount_price']); ?></strong>
                                <?php else: ?>
                                    <?php echo format_price($product['price']); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $product['stock_quantity'] <= 5 ? 'danger' : 'warning'; ?> fs-6">
                                    <?php echo $product['stock_quantity']; ?> قطعة
                                </span>
                            </td>
                            <td><?php echo format_price($product['stock_value']); ?></td>
                            <td>
                                <?php if ($product['stock_quantity'] <= 5): ?>
                                    <span class="badge bg-danger">حرج</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">منخفض</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-outline-primary" title="تحديث المخزون">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../product.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-outline-info" title="عرض المنتج" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-success text-center">
                <i class="fas fa-check-circle fa-3x mb-3"></i>
                <h5>ممتاز! لا توجد منتجات منخفضة المخزون</h5>
                <p class="mb-0">جميع المنتجات لديها مخزون كافي حسب العتبة المحددة (<?php echo $threshold; ?> قطعة)</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, .btn, .card-header .btn-group {
        display: none !important;
    }
    
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }
}

.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.text-xs {
    font-size: 0.7rem;
}

.shadow {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
}
</style>

<?php require_once 'includes/admin_footer.php'; ?>
